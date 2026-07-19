<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FreshInstallTest extends TestCase
{
    use RefreshDatabase;

    public function test_fresh_migrate_and_seed_succeeds(): void
    {
        $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

        $this->assertDatabaseHas('users', ['email' => 'admin@arkfleet.local']);
        $this->assertDatabaseHas('equipment', ['unit_code' => 'EX-001']);
        $this->assertDatabaseHas('loans', ['contract_number' => 'LN-2024-001']);
        $this->assertDatabaseHas('asset_classes', ['code' => 'MP-K2']);
        $this->assertDatabaseCount('document_types', 5);
    }

    public function test_legacy_migrate_dry_run_succeeds(): void
    {
        $this->artisan('legacy:migrate')->assertSuccessful();
    }
}
