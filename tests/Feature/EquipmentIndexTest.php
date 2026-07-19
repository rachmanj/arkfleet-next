<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EquipmentIndexTest extends TestCase
{
    use RefreshDatabase;

    private function userWithView(): User
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(MasterDataSeeder::class);

        $user = User::factory()->create();
        $user->givePermissionTo('view');

        return $user;
    }

    public function test_equipment_index_filters_by_column_combination(): void
    {
        $user = $this->userWithView();

        Equipment::query()->where('unit_code', 'EX-001')->update([
            'description' => 'Sample excavator for IPA testing',
            'project_code' => '000H',
            'acquisition_cost' => 1_200_000_000,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('equipment.index', [
                'unit_code' => 'EX',
                'project_code' => '000H',
                'acquisition_cost_min' => 1_000_000_000,
                'is_active' => 1,
            ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Masters/Equipment/Index')
                ->where('filters.unit_code', 'EX')
                ->where('filters.project_code', '000H')
                ->where('filters.acquisition_cost_min', '1000000000')
                ->where('filters.is_active', '1')
                ->has('equipment.data', 1)
                ->where('equipment.data.0.unit_code', 'EX-001')
            );
    }
}
