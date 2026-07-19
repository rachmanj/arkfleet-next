<?php

namespace App\Console\Commands;

use App\Services\Sap\SapBusinessPartnerSyncService;
use Illuminate\Console\Command;

class SapSyncBusinessPartnersCommand extends Command
{
    protected $signature = 'sap:sync-business-partners {--card-type=}';

    protected $description = 'Sync business partners from SAP B1 Service Layer';

    public function handle(SapBusinessPartnerSyncService $syncService): int
    {
        $result = $syncService->sync(null, [
            'card_type' => $this->option('card-type'),
            'active_only' => true,
        ]);

        $this->info("Business partners sync complete: {$result['created']} created, {$result['updated']} updated, {$result['failed']} failed.");

        return self::SUCCESS;
    }
}
