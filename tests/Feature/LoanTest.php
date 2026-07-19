<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\SapBusinessPartner;
use App\Models\User;
use App\Services\Loans\LoanInstallmentService;
use App\Services\Loans\LoanPostingService;
use App\Services\Sap\PostingService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class LoanTest extends TestCase
{
    use RefreshDatabase;

    private function userWithView(): User
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $user = User::factory()->create();
        $user->givePermissionTo('view');

        return $user;
    }

    private function sampleLoan(): Loan
    {
        SapBusinessPartner::create([
            'card_code' => 'V001',
            'card_name' => 'Test Vendor',
            'card_type' => 'S',
            'is_active' => true,
        ]);

        return Loan::create([
            'vendor_card_code' => 'V001',
            'contract_number' => 'TEST-001',
            'principal_amount' => 1_000_000,
            'term_months' => 6,
            'status' => 'draft',
        ]);
    }

    public function test_loans_index_requires_view_permission(): void
    {
        $user = $this->userWithView();
        $this->sampleLoan();

        $this->actingAs($user)
            ->get(route('loans.index'))
            ->assertOk();
    }

    public function test_create_draft_installments_from_parsed_rows(): void
    {
        $loan = $this->sampleLoan();
        $service = app(LoanInstallmentService::class);

        $count = $service->createDraftInstallments($loan, [
            [
                'installment_no' => 1,
                'due_date' => '2026-08-01',
                'principal_amount' => 100_000,
                'interest_amount' => 10_000,
                'total_installments' => 6,
            ],
        ]);

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('loan_installments', [
            'loan_id' => $loan->id,
            'installment_no' => 1,
            'status' => 'draft',
        ]);
        $loan->refresh();
        $this->assertSame('active', $loan->status);
    }

    public function test_confirm_installment_moves_to_confirmed(): void
    {
        $loan = $this->sampleLoan();
        $installment = LoanInstallment::create([
            'loan_id' => $loan->id,
            'installment_no' => 1,
            'total_installments' => 6,
            'due_date' => now(),
            'principal_amount' => 100_000,
            'interest_amount' => 10_000,
            'total_amount' => 110_000,
            'status' => 'draft',
        ]);

        app(LoanInstallmentService::class)->confirmInstallment($installment);

        $this->assertSame('confirmed', $installment->fresh()->status);
    }

    public function test_ap_invoice_posting_refuses_duplicate(): void
    {
        config([
            'sap.posting.uat_signed_off' => true,
            'loans.sap_posting_enabled' => true,
        ]);

        $loan = $this->sampleLoan();
        $installment = LoanInstallment::create([
            'loan_id' => $loan->id,
            'installment_no' => 1,
            'total_installments' => 6,
            'due_date' => now(),
            'principal_amount' => 100_000,
            'interest_amount' => 10_000,
            'total_amount' => 110_000,
            'status' => 'confirmed',
            'sap_doc_entry' => 123,
            'sap_doc_num' => 456,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('already posted');

        app(LoanPostingService::class)->postApInvoice($installment);
    }

    public function test_ap_invoice_preview_has_principal_and_interest_lines(): void
    {
        $loan = $this->sampleLoan();
        $installment = LoanInstallment::create([
            'loan_id' => $loan->id,
            'installment_no' => 1,
            'total_installments' => 6,
            'due_date' => now(),
            'principal_amount' => 100_000,
            'interest_amount' => 10_000,
            'total_amount' => 110_000,
            'status' => 'confirmed',
        ]);

        $preview = app(LoanPostingService::class)->buildApInvoicePreview($installment);

        $this->assertCount(2, $preview['DocumentLines']);
        $this->assertSame(110_000.0, $preview['total']);
    }

    public function test_schedule_locks_after_successful_ap_post(): void
    {
        config([
            'sap.posting.uat_signed_off' => true,
            'loans.sap_posting_enabled' => true,
        ]);

        $loan = $this->sampleLoan();
        $installment = LoanInstallment::create([
            'loan_id' => $loan->id,
            'installment_no' => 1,
            'total_installments' => 6,
            'due_date' => now(),
            'principal_amount' => 100_000,
            'interest_amount' => 10_000,
            'total_amount' => 110_000,
            'status' => 'confirmed',
        ]);

        $postingService = Mockery::mock(PostingService::class);
        $postingService->shouldReceive('postWithIdempotency')
            ->once()
            ->andReturn(new \App\Models\SapPostingLog([
                'status' => 'success',
                'doc_entry' => 100,
                'doc_num' => 200,
            ]));

        $service = new LoanPostingService($postingService);
        $service->postApInvoice($installment);

        $loan->refresh();
        $this->assertSame('locked', $loan->status);
        $this->assertNotNull($loan->schedule_locked_at);
        $this->assertSame('posted', $installment->fresh()->status);
    }
}
