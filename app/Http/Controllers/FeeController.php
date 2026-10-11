<?php

namespace App\Http\Controllers;

use App\Models\Fee;
use App\Models\Payment;
use App\Services\FeePaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FeeController extends Controller
{
    public function paymentReviews(Request $request)
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'accepted', 'rejected', 'all'])],
        ]);
        $status = $validated['status'] ?? 'pending';

        $payments = Payment::with(['fee.student', 'reviewer'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $counts = Payment::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.fees.payments', compact('payments', 'counts', 'status'));
    }

    public function index()
    {
        $fees = Fee::with(['student', 'payments'])->latest()->paginate(20);

        return view('admin.fees.index', compact('fees'));
    }

    public function show(Fee $fee)
    {
        return view('admin.fees.receipt', compact('fee'));
    }

    public function showPaymentProof(Payment $payment): BinaryFileResponse
    {
        abort_unless($payment->proof_path && Storage::disk('local')->exists($payment->proof_path), 404);

        return response()->file(Storage::disk('local')->path($payment->proof_path));
    }

    public function showPaymentReceipt(Payment $payment)
    {
        abort_unless($payment->status === 'accepted', 404);

        $payment->load(['fee.student.bed.room', 'reviewer']);

        return view('admin.fees.payment-receipt', compact('payment'));
    }

    public function submitPayment(Request $request, FeePaymentService $paymentService): RedirectResponse
    {
        $validated = $request->validate([
            'fee_id' => ['required', 'integer', 'exists:fees,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', Rule::in(['esewa', 'khalti', 'bank_transfer', 'other'])],
            'proof' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remark' => ['required', 'string', 'max:1000'],
        ]);

        $fee = Auth::user()->fees()->whereKey($validated['fee_id'])->firstOrFail();

        $paymentService->submit(
            $fee,
            Auth::user(),
            (string) $validated['amount'],
            $request->file('proof'),
            $validated['method'],
            $validated['remark']
        );

        return redirect()->route('student.fees')->with('success', 'Payment proof submitted. Your payment is awaiting admin review.');
    }

    public function reviewPayment(Request $request, Payment $payment): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['accepted', 'rejected'])],
            'admin_remark' => ['required', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($payment, $validated) {
            $fee = Fee::query()->whereKey($payment->fee_id)->lockForUpdate()->firstOrFail();
            $lockedPayment = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($lockedPayment->status !== 'pending') {
                throw ValidationException::withMessages([
                    'status' => 'This payment has already been reviewed.',
                ]);
            }

            if ($validated['status'] === 'accepted' && $fee->status === 'paid') {
                throw ValidationException::withMessages([
                    'status' => 'This fee has already been paid.',
                ]);
            }

            $lockedPayment->update([
                'status' => $validated['status'],
                'admin_remark' => $validated['admin_remark'],
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
            ]);

            if ($validated['status'] === 'accepted') {
                $fee->update(['status' => 'paid']);
            }
        });

        return back()->with('success', 'Payment '.$validated['status'].' successfully.');
    }
}
