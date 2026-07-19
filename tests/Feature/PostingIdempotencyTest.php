<?php

namespace Tests\Feature;

use App\Models\SapPostingLog;
use App\Services\Sap\PostingService;
use App\Services\Sap\SapService;
use App\Support\SapPostingGate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PostingIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_posting_service_refuses_duplicate_successful_post(): void
    {
        $sap = Mockery::mock(SapService::class);
        $sap->shouldReceive('post')->never();

        $service = new PostingService($sap);

        SapPostingLog::create([
            'document_type' => 'ap_invoice',
            'idempotency_key' => 'test-key-1',
            'status' => 'success',
            'doc_entry' => 10,
            'doc_num' => 20,
        ]);

        $log = $service->postWithIdempotency(
            'ap_invoice',
            'test-key-1',
            fn () => ['DocEntry' => 99, 'DocNum' => 99],
        );

        $this->assertSame(10, $log->doc_entry);
        $this->assertDatabaseCount('sap_posting_logs', 1);
    }

    public function test_sap_posting_gate_requires_uat_sign_off(): void
    {
        config([
            'sap.posting.uat_signed_off' => false,
            'loans.sap_posting_enabled' => true,
            'depreciation.sap_posting_enabled' => true,
        ]);

        $this->assertFalse(SapPostingGate::loanPostingEnabled());
        $this->assertFalse(SapPostingGate::depreciationPostingEnabled());

        config(['sap.posting.uat_signed_off' => true]);

        $this->assertTrue(SapPostingGate::loanPostingEnabled());
        $this->assertTrue(SapPostingGate::depreciationPostingEnabled());
    }

    public function test_failed_post_allows_retry(): void
    {
        $sap = Mockery::mock(SapService::class);
        $sap->shouldReceive('post')->once()->andReturn(['DocEntry' => 5, 'DocNum' => 15]);

        $service = new PostingService($sap);

        SapPostingLog::create([
            'document_type' => 'journal_entry',
            'idempotency_key' => 'retry-key',
            'status' => 'failed',
            'error_payload' => ['message' => 'timeout'],
        ]);

        $log = $service->postWithIdempotency(
            'journal_entry',
            'retry-key',
            fn (SapService $s) => $s->post('JournalEntries', []),
        );

        $this->assertSame('success', $log->status);
        $this->assertSame(5, $log->doc_entry);
        $this->assertDatabaseCount('sap_posting_logs', 1);
    }
}
