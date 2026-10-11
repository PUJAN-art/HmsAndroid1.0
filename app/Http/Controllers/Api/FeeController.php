<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Fee;
use App\Models\Payment;
use App\Services\FeePaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FeeController extends Controller
{
    // ----------------------------------------------------------------
    // Shared: build a fee record array from a loaded Fee model.
    // Pass $withPayments = true to include the payment receipts.
    // ----------------------------------------------------------------
    private function feeResource(Fee $fee, bool $withPayments = false): array
    {
        $data = [
            'id' => $fee->id,
            'type' => $fee->type,
            'amount' => (float) $fee->amount,
            'due_date' => $fee->due_date,
            'status' => $fee->status,
            'created_at' => $fee->created_at?->toDateString(),
        ];

        if ($withPayments) {
            $data['receipts'] = $fee->payments->map(fn ($p) => $this->receiptResource($p))->values();
        }

        return $data;
    }

    // ----------------------------------------------------------------
    // Shared: build a receipt (payment) array from a loaded Payment.
    // ----------------------------------------------------------------
    private function receiptResource(\App\Models\Payment $payment): array
    {
        return [
            'receipt_id' => $payment->id,
            'fee_id' => $payment->fee_id,
            'amount_paid' => (float) $payment->amount,
            'payment_date' => $payment->date?->toDateString(),
            'method' => $payment->method,
            'transaction_id' => $payment->transaction_id,
            'status' => $payment->status,
            'proof_url' => $payment->proof_path ? route('api.student.fees.payments.proof', $payment) : null,
            'student_remark' => $payment->student_remark,
            'admin_remark' => $payment->admin_remark,
            'issued_at' => $payment->created_at?->toDateTimeString(),
        ];
    }

    public function showPaymentProof(Request $request, Payment $payment): BinaryFileResponse
    {
        abort_unless(
            $payment->fee()->where('student_id', $request->user()->id)->exists()
                && $payment->proof_path
                && Storage::disk('local')->exists($payment->proof_path),
            404
        );

        return response()->file(Storage::disk('local')->path($payment->proof_path));
    }

    public function submitPayment(Request $request, Fee $fee, FeePaymentService $paymentService): JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', Rule::in(['esewa', 'khalti', 'bank_transfer', 'other'])],
            'proof' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remark' => ['required', 'string', 'max:1000'],
        ]);

        /** @var \App\Models\User $student */
        $student = $request->user();
        $payment = $paymentService->submit(
            $fee,
            $student,
            (string) $validated['amount'],
            $request->file('proof'),
            $validated['method'],
            $validated['remark']
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Payment proof submitted and is awaiting admin review.',
            'payment' => $this->receiptResource($payment),
        ], 201);
    }

    // ----------------------------------------------------------------
    // GET /api/fees/my   (requires auth:sanctum, students only)
    //
    // Returns:
    //  - summary  : total fees, paid amount, due amount, fee counts
    //  - fees[]   : each fee record with its receipts embedded
    //
    // Optional query params:
    //   ?status=pending|paid|overdue   — filter by fee status
    //   ?type=monthly|penalty|mess     — filter by fee type
    // ----------------------------------------------------------------
    public function myFees(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        if ($user->role !== 'student') {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied. This endpoint is for students only.',
            ], 403);
        }

        // Load all fees with their payments in one query
        $feesQuery = Fee::with('payments')
            ->where('student_id', $user->id);

        if ($request->filled('status')) {
            $feesQuery->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $feesQuery->where('type', $request->type);
        }

        $fees = $feesQuery->orderBy('due_date', 'desc')->get();

        // Build summary from the full unfiltered set for accurate totals
        $allFees = Fee::where('student_id', $user->id)->get();

        $totalAmount = (float) $allFees->sum('amount');
        $paidAmount = (float) Fee::where('student_id', $user->id)
            ->where('status', 'paid')
            ->sum('amount');
        $pendingAmount = (float) Fee::where('student_id', $user->id)
            ->where('status', 'pending')
            ->sum('amount');
        $overdueAmount = (float) Fee::where('student_id', $user->id)
            ->where('status', 'overdue')
            ->sum('amount');

        return response()->json([
            'status' => 'success',
            'summary' => [
                'total_fees' => $allFees->count(),
                'paid_count' => $allFees->where('status', 'paid')->count(),
                'pending_count' => $allFees->where('status', 'pending')->count(),
                'overdue_count' => $allFees->where('status', 'overdue')->count(),
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'pending_amount' => $pendingAmount,
                'overdue_amount' => $overdueAmount,
                'due_amount' => round($pendingAmount + $overdueAmount, 2),
            ],
            'total' => $fees->count(),
            'fees' => $fees->map(fn ($fee) => $this->feeResource($fee, withPayments: true)),
        ]);
    }

    // ----------------------------------------------------------------
    // GET /api/fees/receipts   (requires auth:sanctum, students only)
    //
    // Returns a flat list of all payment receipts for the student,
    // each receipt linked back to its parent fee record.
    //
    // Optional query params:
    //   ?method=cash|online   — filter by payment method
    //   ?fee_id=5             — receipts for a specific fee only
    // ----------------------------------------------------------------
    public function myReceipts(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        if ($user->role !== 'student') {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied. This endpoint is for students only.',
            ], 403);
        }

        // Join through fees to scope payments to this student only
        $paymentsQuery = Payment::with('fee')
            ->whereHas('fee', fn ($q) => $q->where('student_id', $user->id));

        if ($request->filled('method')) {
            $paymentsQuery->where('method', $request->method);
        }

        if ($request->filled('fee_id')) {
            $paymentsQuery->where('fee_id', $request->fee_id);
        }

        $payments = $paymentsQuery->orderBy('date', 'desc')->get();

        $totalPaid = (float) $payments->where('status', 'accepted')->sum('amount');

        return response()->json([
            'status' => 'success',
            'total_paid' => $totalPaid,
            'total' => $payments->count(),
            'receipts' => $payments->map(function ($payment) {
                $receipt = $this->receiptResource($payment);
                // Embed parent fee summary for context
                $receipt['fee'] = [
                    'fee_id' => $payment->fee->id,
                    'type' => $payment->fee->type,
                    'amount' => (float) $payment->fee->amount,
                    'due_date' => $payment->fee->due_date,
                    'status' => $payment->fee->status,
                ];

                return $receipt;
            }),
        ]);
    }
}
