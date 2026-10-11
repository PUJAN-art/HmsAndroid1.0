<?php

namespace App\Services;

use App\Models\Fee;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class FeePaymentService
{
    public function submit(
        Fee $fee,
        User $student,
        string $submittedAmount,
        UploadedFile $proof,
        string $method,
        string $remark
    ): Payment {
        $proofPath = null;

        try {
            return DB::transaction(function () use ($fee, $student, $submittedAmount, $proof, $method, $remark, &$proofPath) {
                $lockedFee = Fee::query()->whereKey($fee->id)->lockForUpdate()->firstOrFail();

                if ($lockedFee->student_id !== $student->id) {
                    abort(404);
                }

                if ($lockedFee->status === 'paid') {
                    throw ValidationException::withMessages([
                        'fee_id' => 'This fee has already been paid.',
                    ]);
                }

                if (number_format((float) $submittedAmount, 2, '.', '') !== number_format((float) $lockedFee->amount, 2, '.', '')) {
                    throw ValidationException::withMessages([
                        'amount' => 'The submitted amount must match the payable fee amount.',
                    ]);
                }

                if ($lockedFee->payments()->where('status', 'pending')->exists()) {
                    throw ValidationException::withMessages([
                        'fee_id' => 'A payment for this fee is already awaiting review.',
                    ]);
                }

                $proofPath = $proof->store('payment-proofs', 'local');

                if ($proofPath === false) {
                    throw new \RuntimeException('The payment proof could not be saved.');
                }

                return $lockedFee->payments()->create([
                    'amount' => $lockedFee->amount,
                    'date' => now()->toDateString(),
                    'method' => $method,
                    'status' => 'pending',
                    'proof_path' => $proofPath,
                    'student_remark' => $remark,
                ]);
            });
        } catch (Throwable $exception) {
            if ($proofPath !== null) {
                Storage::disk('local')->delete($proofPath);
            }

            throw $exception;
        }
    }
}
