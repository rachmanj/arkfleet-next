<?php

namespace Tests\Feature;

use App\Services\Migration\LegacyMigrationService;
use Tests\TestCase;

class LegacyMigrationTest extends TestCase
{
    public function test_dry_run_returns_ordered_plan(): void
    {
        $report = app(LegacyMigrationService::class)->dryRun();

        $this->assertSame('dry-run', $report['mode']);
        $this->assertArrayHasKey('plan', $report);

        $entities = collect($report['plan'])->pluck('entity')->all();

        $this->assertSame([
            'manufactures',
            'plant_types',
            'plant_groups',
            'asset_categories',
            'unitstatuses',
            'suppliers',
            'unit_models',
            'document_types',
            'departments',
            'projects',
            'users',
            'user_roles',
            'equipment',
            'equipment_documents',
        ], $entities);
    }

    public function test_legacy_connection_is_configured(): void
    {
        $service = app(LegacyMigrationService::class);

        $this->assertTrue($service->isConfigured());
    }
}
