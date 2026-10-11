<?php

namespace Tests\Feature;

use App\Models\Fee;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FeePaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_submit_payment_proof_through_the_api(): void
    {
        Storage::fake('local');

        $student = User::factory()->create(['role' => 'student']);
        $fee = $this->createFee($student);

        $response = $this->actingAs($student, 'sanctum')
            ->post('/api/student/fees/'.$fee->id.'/payments', [
                'amount' => '1250.00',
                'method' => 'bank_transfer',
                'proof' => $this->fakeProof(),
                'remark' => 'Fee transfer made today.',
            ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('payment.amount_paid', 1250)
            ->assertJsonPath('payment.status', 'pending')
            ->assertJsonPath('payment.method', 'bank_transfer')
            ->assertJsonPath('payment.student_remark', 'Fee transfer made today.');

        $payment = Payment::sole();
        $this->assertSame('pending', $payment->status);
        $this->assertSame('pending', $fee->fresh()->status);
        Storage::disk('local')->assertExists($payment->proof_path);

        $proofUrl = $response->json('payment.proof_url');
        $this->actingAs($student, 'sanctum')->get($proofUrl)->assertOk();

        $otherStudent = User::factory()->create(['role' => 'student']);
        $this->actingAs($otherStudent, 'sanctum')->get($proofUrl)->assertNotFound();

        $this->actingAs($student, 'sanctum')
            ->post('/api/student/fees/'.$fee->id.'/payments', [
                'amount' => '1250.00',
                'method' => 'bank_transfer',
                'proof' => $this->fakeProof(),
                'remark' => 'Duplicate submission.',
            ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('fee_id');
    }

    public function test_student_cannot_submit_an_amount_that_differs_from_the_fee(): void
    {
        Storage::fake('local');

        $student = User::factory()->create(['role' => 'student']);
        $fee = $this->createFee($student);

        $this->actingAs($student, 'sanctum')
            ->post('/api/student/fees/'.$fee->id.'/payments', [
                'amount' => '1250.01',
                'method' => 'esewa',
                'proof' => $this->fakeProof(),
                'remark' => 'Fee transfer.',
            ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('amount');

        $this->assertSame(0, Payment::count());
    }

    public function test_student_cannot_pay_another_students_fee(): void
    {
        Storage::fake('local');

        $student = User::factory()->create(['role' => 'student']);
        $otherStudent = User::factory()->create(['role' => 'student']);
        $fee = $this->createFee($otherStudent);

        $this->actingAs($student, 'sanctum')
            ->post('/api/student/fees/'.$fee->id.'/payments', [
                'amount' => '1250.00',
                'method' => 'esewa',
                'proof' => $this->fakeProof(),
                'remark' => 'Fee transfer.',
            ], ['Accept' => 'application/json'])
            ->assertNotFound();

        $this->assertSame(0, Payment::count());
        Storage::disk('local')->assertDirectoryEmpty('payment-proofs');
    }

    public function test_non_students_cannot_submit_payments_through_the_fee_api_alias(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $fee = $this->createFee($admin);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/fees/'.$fee->id.'/payments')
            ->assertForbidden();
    }

    public function test_admin_acceptance_marks_fee_as_paid(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $admin = User::factory()->create(['role' => 'admin']);
        $fee = $this->createFee($student);
        $payment = $fee->payments()->create([
            'amount' => $fee->amount,
            'date' => now()->toDateString(),
            'method' => 'bank_transfer',
            'status' => 'pending',
            'student_remark' => 'Transferred.',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.fees.payments.review', $payment), [
                'status' => 'accepted',
                'admin_remark' => 'Statement verified.',
                'payment_id' => $payment->id,
            ])
            ->assertRedirect();

        $this->assertSame('paid', $fee->fresh()->status);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'accepted',
            'admin_remark' => 'Statement verified.',
            'reviewed_by' => $admin->id,
        ]);
    }

    public function test_admin_can_view_and_print_a_receipt_for_an_accepted_payment(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'name' => 'Receipt Student',
            'student_id_number' => 'STU-123',
        ]);
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Receipt Admin']);
        $fee = $this->createFee($student);
        $fee->update(['status' => 'paid']);
        $payment = $fee->payments()->create([
            'amount' => $fee->amount,
            'date' => now()->toDateString(),
            'method' => 'bank_transfer',
            'status' => 'accepted',
            'transaction_id' => 'BANK-REF-123',
            'student_remark' => 'Transfer completed.',
            'admin_remark' => 'Verified against bank statement.',
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.fees.payments.receipt', $payment))
            ->assertOk()
            ->assertSee('Payment Receipt')
            ->assertSee('PAY-'.str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT))
            ->assertSee('Receipt Student')
            ->assertSee('STU-123')
            ->assertSee('Bank transfer')
            ->assertSee('BANK-REF-123')
            ->assertSee('Rs 1,250.00')
            ->assertSee('Verified against bank statement.')
            ->assertSee('Print receipt');
    }

    public function test_receipt_is_only_available_for_accepted_payments(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $admin = User::factory()->create(['role' => 'admin']);
        $fee = $this->createFee($student);
        $payment = $fee->payments()->create([
            'amount' => $fee->amount,
            'date' => now()->toDateString(),
            'method' => 'bank_transfer',
            'status' => 'pending',
            'student_remark' => 'Awaiting review.',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.fees.payments.receipt', $payment))
            ->assertNotFound();

        $this->actingAs($student)
            ->get(route('admin.fees.payments.receipt', $payment))
            ->assertForbidden();
    }

    public function test_admin_has_a_dedicated_payment_review_page_with_status_filters(): void
    {
        Storage::fake('local');

        $student = User::factory()->create(['role' => 'student', 'name' => 'Payment Review Student']);
        $admin = User::factory()->create(['role' => 'admin']);
        $fee = $this->createFee($student);
        $proofPath = $this->fakeProof()->store('payment-proofs', 'local');
        $pendingPayment = $fee->payments()->create([
            'amount' => $fee->amount,
            'date' => now()->toDateString(),
            'method' => 'bank_transfer',
            'status' => 'pending',
            'student_remark' => 'Transfer complete.',
            'proof_path' => $proofPath,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.fees.payments.index'))
            ->assertOk()
            ->assertSee('Fee Payment Reviews')
            ->assertSee('Payment Review Student')
            ->assertSee('Transfer complete.')
            ->assertSee(route('admin.fees.payments.proof', $pendingPayment), false)
            ->assertDontSee(route('admin.fees.payments.receipt', $pendingPayment), false);

        $this->actingAs($admin)
            ->get(route('admin.fees.payments.index', ['status' => 'accepted']))
            ->assertOk()
            ->assertSee('No accepted payment submissions found.');
    }

    public function test_student_cannot_access_the_admin_payment_review_page(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)
            ->get(route('admin.fees.payments.index'))
            ->assertForbidden();
    }

    public function test_admin_rejection_keeps_fee_due_for_resubmission(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $admin = User::factory()->create(['role' => 'admin']);
        $fee = $this->createFee($student);
        $payment = $fee->payments()->create([
            'amount' => $fee->amount,
            'date' => now()->toDateString(),
            'method' => 'bank_transfer',
            'status' => 'pending',
            'student_remark' => 'Transferred.',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.fees.payments.review', $payment), [
                'status' => 'rejected',
                'admin_remark' => 'Please upload a readable statement.',
                'payment_id' => $payment->id,
            ])
            ->assertRedirect();

        $this->assertSame('pending', $fee->fresh()->status);
        $this->assertSame('rejected', $payment->fresh()->status);
    }

    private function createFee(User $student): Fee
    {
        return Fee::create([
            'student_id' => $student->id,
            'amount' => '1250.00',
            'due_date' => now()->addDays(5)->toDateString(),
            'status' => 'pending',
            'type' => 'monthly',
        ]);
    }

    private function fakeProof(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            'statement.png',
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADUlEQVR4nGP4z8AAAAMBAQDJ/pLvAAAAAElFTkSuQmCC')
        );
    }
}
