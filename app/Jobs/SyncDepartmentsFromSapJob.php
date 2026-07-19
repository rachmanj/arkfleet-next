<?php

namespace App\Jobs;

use App\Services\Sap\SapDepartmentSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncDepartmentsFromSapJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ?int $triggeredBy = null,
    ) {}

    public function handle(SapDepartmentSyncService $syncService): void
    {
        $syncService->sync($this->triggeredBy);
    }
}
