<?php

namespace App\Jobs;

use App\Services\Sap\SapProjectSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncProjectsFromSapJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ?int $triggeredBy = null,
    ) {}

    public function handle(SapProjectSyncService $syncService): void
    {
        $syncService->sync($this->triggeredBy);
    }
}
