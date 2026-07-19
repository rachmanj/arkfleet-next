<?php

namespace App\Services\Depreciation;

use App\Models\DepreciationRun;
use App\Services\Sap\PostingService;
use App\Services\Sap\SapService;
use RuntimeException;

class DepreciationPostingService
{
    public function __construct(
        protected PostingService $postingService,
        protected SapService $sapService,
    ) {}

    public function isEnabled(): bool
    {
        return \App\Support\SapPostingGate::depreciationPostingEnabled();
    }

    public function buildJournalPreview(DepreciationRun $run): array
    {
        $run->load(['entries.fixedAsset.assetClass', 'entries.fixedAsset.equipment']);

        $lines = [];
        $total = 0;

        foreach ($run->entries as $entry) {
            if ($entry->book_type !== 'book') {
                continue;
            }

            $amount = (float) $entry->depreciation_amount;
            $total += $amount;

            $expenseGl = $entry->fixedAsset->sapDepreciationGl()
                ?? config('depreciation.journal.default_depreciation_gl');
            $accumulatedGl = $entry->fixedAsset->sapAccumulatedGl()
                ?? config('depreciation.journal.default_accumulated_gl');

            $unitCode = $entry->fixedAsset->equipment?->unit_code ?? $entry->fixed_asset_id;
            $memo = config('depreciation.journal.memo_prefix')." {$run->periodLabel()} — {$unitCode}";

            $lines[] = [
                'AccountCode' => $expenseGl,
                'Debit' => $amount,
                'Credit' => 0,
                'LineMemo' => $memo,
            ];
            $lines[] = [
                'AccountCode' => $accumulatedGl,
                'Debit' => 0,
                'Credit' => $amount,
                'LineMemo' => $memo,
            ];
        }

        return [
            'ReferenceDate' => sprintf('%04d-%02d-01', $run->period_year, $run->period_month),
            'Memo' => config('depreciation.journal.memo_prefix').' '.$run->periodLabel(),
            'JournalEntryLines' => $lines,
            'total_debit' => round($total, 2),
            'total_credit' => round($total, 2),
            'line_pairs' => count($lines) / 2,
        ];
    }

    public function postToSap(DepreciationRun $run, ?int $userId = null): array
    {
        if (! $this->isEnabled()) {
            throw new RuntimeException('SAP depreciation posting is disabled. Requires SAP_POSTING_UAT_SIGNED_OFF=true and SAP_DEPRECIATION_POSTING_ENABLED=true.');
        }

        if ($run->status !== 'confirmed') {
            throw new RuntimeException('Depreciation run must be confirmed before posting to SAP.');
        }

        $preview = $this->buildJournalPreview($run);

        if ($preview['total_debit'] <= 0) {
            throw new RuntimeException('No book depreciation entries to post.');
        }

        $idempotencyKey = "depreciation-run-{$run->id}";

        $log = $this->postingService->postWithIdempotency(
            documentType: 'journal_entry',
            idempotencyKey: $idempotencyKey,
            postCallback: function (SapService $sap) use ($preview) {
                $sap->ensureSession();

                return $sap->post('JournalEntries', [
                    'ReferenceDate' => $preview['ReferenceDate'],
                    'Memo' => $preview['Memo'],
                    'JournalEntryLines' => $preview['JournalEntryLines'],
                ]);
            },
            userId: $userId,
        );

        if ($log->status === 'success') {
            $run->update([
                'status' => 'posted',
                'posted_at' => now(),
            ]);
        }

        return [
            'log' => $log,
            'preview' => $preview,
        ];
    }
}
