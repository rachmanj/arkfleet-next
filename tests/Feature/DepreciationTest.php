<?php

namespace Tests\Feature;

use App\Models\AssetClass;
use App\Models\Equipment;
use App\Models\FixedAsset;
use App\Models\User;
use App\Services\Depreciation\DepreciationCalculator;
use App\Services\Depreciation\DepreciationRunService;
use Carbon\Carbon;
use Database\Seeders\AssetClassSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepreciationTest extends TestCase
{
    use RefreshDatabase;

    private function userWithView(): User
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(MasterDataSeeder::class);
        $this->seed(AssetClassSeeder::class);

        $user = User::factory()->create();
        $user->givePermissionTo('view');

        return $user;
    }

    private function sampleAsset(): FixedAsset
    {
        $this->seed(MasterDataSeeder::class);
        $this->seed(AssetClassSeeder::class);

        $equipment = Equipment::query()->where('unit_code', 'EX-001')->firstOrFail();
        $assetClass = AssetClass::query()->where('code', 'MP-K2')->firstOrFail();

        return FixedAsset::create([
            'equipment_id' => $equipment->id,
            'asset_class_id' => $assetClass->id,
            'acquisition_cost' => 1_000_000,
            'in_service_date' => Carbon::create(2024, 1, 1),
            'salvage_value' => 100_000,
            'status' => 'active',
        ]);
    }

    public function test_straight_line_monthly_depreciation(): void
    {
        $asset = $this->sampleAsset();
        $asset->update(['book_method' => 'straight_line', 'book_useful_life_months' => 12]);

        $calculator = app(DepreciationCalculator::class);
        $amount = $calculator->calculateMonthly(
            $asset,
            'book',
            1_000_000,
            0,
            Carbon::create(2024, 1, 31),
        );

        $this->assertSame(75_000.0, $amount);
    }

    public function test_depreciation_run_is_idempotent(): void
    {
        $this->sampleAsset();
        $service = app(DepreciationRunService::class);

        $first = $service->runPeriod(2024, 6);
        $second = $service->runPeriod(2024, 6);

        $this->assertSame($first->id, $second->id);
        $this->assertGreaterThan(0, $second->entry_count);
        $this->assertDatabaseCount('depreciation_entries', $second->entry_count);
    }

    public function test_fixed_assets_index_requires_view_permission(): void
    {
        $user = $this->userWithView();
        $this->sampleAsset();

        $this->actingAs($user)
            ->get(route('fixed-assets.index'))
            ->assertOk();
    }

    public function test_deferred_tax_report_accessible(): void
    {
        $user = $this->userWithView();
        $this->sampleAsset();
        app(DepreciationRunService::class)->runPeriod(2024, 6);

        $this->actingAs($user)
            ->get(route('depreciation.deferred-tax'))
            ->assertOk();
    }
}
