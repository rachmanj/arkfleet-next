<?php

namespace Tests\Feature;

use App\Services\Sap\SapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SapServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sap_service_reports_unconfigured_when_env_missing(): void
    {
        config([
            'services.sap.base_url' => '',
            'services.sap.company_db' => '',
            'services.sap.username' => '',
            'services.sap.password' => '',
        ]);

        $service = app(SapService::class);

        $this->assertFalse($service->isConfigured());
    }

    public function test_sap_ping_command_fails_when_not_configured(): void
    {
        config([
            'services.sap.base_url' => '',
            'services.sap.company_db' => '',
            'services.sap.username' => '',
            'services.sap.password' => '',
        ]);

        $this->artisan('sap:ping')
            ->expectsOutputToContain('SAP B1 is not configured')
            ->assertFailed();
    }

    public function test_sap_service_is_registered_as_singleton(): void
    {
        $first = app(SapService::class);
        $second = app(SapService::class);

        $this->assertSame($first, $second);
    }
}
