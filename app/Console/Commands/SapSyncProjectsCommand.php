<?php

namespace App\Console\Commands;

use App\Services\Sap\SapProjectSyncService;
use Illuminate\Console\Command;

class SapSyncProjectsCommand extends Command
{
    protected $signature = 'sap:sync-projects';

    protected $description = 'Sync projects from SAP B1 Service Layer';

    public function handle(SapProjectSyncService $syncService): int
    {
        $result = $syncService->sync();

        $this->info("Projects sync complete: {$result['created']} created, {$result['updated']} updated, {$result['failed']} failed.");

        return self::SUCCESS;
    }
}
