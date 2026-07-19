<?php

namespace App\Jobs;

use App\Services\Sap\SapBusinessPartnerSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncBusinessPartnersFromSapJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ?int $triggeredBy = null,
        public array $options = [],
    ) {}

    public function handle(SapBusinessPartnerSyncService $syncService): void
    {
        $syncService->sync($this->triggeredBy, $this->options);
    }
}
