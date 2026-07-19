<?php

namespace App\Console\Commands;

use App\Services\Sap\SapService;
use Illuminate\Console\Command;

class SapPingCommand extends Command
{
    protected $signature = 'sap:ping';

    protected $description = 'Smoke-test SAP B1 Service Layer connectivity and session login';

    public function handle(SapService $sapService): int
    {
        if (! $sapService->isConfigured()) {
            $this->error('SAP B1 is not configured. Set SAP_BASE_URL, SAP_COMPANY_DB, SAP_USERNAME, and SAP_PASSWORD.');

            return self::FAILURE;
        }

        try {
            $sapService->ensureSession();
            $this->info('SAP B1 login successful.');
            $this->line('Session cookies: '.$sapService->sessionCookieCount());

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error('SAP B1 login failed: '.$exception->getMessage());

            return self::FAILURE;
        }
    }
}
