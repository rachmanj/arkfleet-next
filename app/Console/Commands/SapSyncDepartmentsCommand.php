<?php

namespace App\Console\Commands;

use App\Services\Sap\SapDepartmentSyncService;
use Illuminate\Console\Command;

class SapSyncDepartmentsCommand extends Command
{
    protected $signature = 'sap:sync-departments';

    protected $description = 'Sync departments (profit centers) from SAP B1 Service Layer';

    public function handle(SapDepartmentSyncService $syncService): int
    {
        $result = $syncService->sync();

        $this->info("Departments sync complete: {$result['created']} created, {$result['updated']} updated, {$result['failed']} failed.");

        return self::SUCCESS;
    }
}
