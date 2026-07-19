<?php

namespace App\Services\Sap;

use App\Models\SapBusinessPartner;
use App\Models\SapSyncRun;
use Illuminate\Support\Facades\DB;

class SapBusinessPartnerSyncService
{
    public function __construct(
        protected SapService $sapService,
    ) {}

    public function sync(?int $triggeredBy = null, array $options = []): array
    {
        $run = SapSyncRun::query()->create([
            'entity_type' => 'business_partners',
            'status' => 'running',
            'started_at' => now(),
            'triggered_by' => $triggeredBy,
            'metadata' => $options,
        ]);

        $created = 0;
        $updated = 0;
        $failed = 0;
        $errors = [];

        try {
            $partners = $this->sapService->getBusinessPartners([
                'card_type' => $options['card_type'] ?? null,
                'active_only' => $options['active_only'] ?? true,
            ]);

            DB::transaction(function () use ($partners, &$created, &$updated, &$failed, &$errors) {
                foreach (array_chunk($partners, 100) as $chunk) {
                    foreach ($chunk as $partner) {
                        try {
                            $cardCode = $partner['CardCode'] ?? null;

                            if (! $cardCode) {
                                $failed++;

                                continue;
                            }

                            $payload = [
                                'card_code' => $cardCode,
                                'card_name' => $partner['CardName'] ?? $cardCode,
                                'card_type' => SapService::mapCardType($partner['CardType'] ?? 'C'),
                                'is_active' => SapService::sapYesNo($partner['Valid'] ?? 'tYES')
                                    && ! SapService::sapYesNo($partner['Frozen'] ?? 'tNO'),
                                'federal_tax_id' => $partner['FederalTaxID'] ?? null,
                                'currency' => $partner['Currency'] ?? null,
                                'credit_limit' => $partner['CreditLimit'] ?? null,
                                'balance' => $partner['CurrentAccountBalance'] ?? null,
                                'metadata' => $partner,
                                'last_synced_at' => now(),
                            ];

                            $existing = SapBusinessPartner::withTrashed()->where('card_code', $cardCode)->first();

                            if ($existing) {
                                if ($existing->trashed()) {
                                    $existing->restore();
                                }

                                $existing->update($payload);
                                $updated++;
                            } else {
                                SapBusinessPartner::create($payload);
                                $created++;
                            }
                        } catch (\Throwable $exception) {
                            $failed++;
                            $errors[] = $exception->getMessage();
                        }
                    }
                }
            });

            $run->update([
                'status' => 'completed',
                'created_count' => $created,
                'updated_count' => $updated,
                'failed_count' => $failed,
                'error_summary' => $errors !== [] ? implode('; ', array_slice($errors, 0, 5)) : null,
                'finished_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            $run->update([
                'status' => 'failed',
                'failed_count' => $failed + 1,
                'error_summary' => $exception->getMessage(),
                'finished_at' => now(),
            ]);

            throw $exception;
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'failed' => $failed,
            'run_id' => $run->id,
        ];
    }
}
