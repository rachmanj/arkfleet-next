<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\Unitstatus;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EquipmentRfuUpdateTest extends TestCase
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

    private function activeStatusId(): int
    {
        return Unitstatus::query()->where('name', 'Active')->value('id');
    }

    private function standbyStatusId(): int
    {
        return Unitstatus::query()->where('name', 'Standby')->value('id');
    }

    public function test_active_bd_unit_can_be_updated_to_rfu(): void
    {
        $user = $this->userWithView();

        $equipment = Equipment::query()->where('unit_code', 'EX-001')->firstOrFail();
        $equipment->update([
            'unitstatus_id' => $this->activeStatusId(),
            'is_rfu' => false,
        ]);

        $this->actingAs($user)
            ->post(route('equipment.update-rfu'), [
                'equipments' => [$equipment->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue($equipment->fresh()->is_rfu);
    }

    public function test_active_rfu_unit_can_be_updated_to_bd(): void
    {
        $user = $this->userWithView();

        $equipment = Equipment::query()->where('unit_code', 'EX-001')->firstOrFail();
        $equipment->update([
            'unitstatus_id' => $this->activeStatusId(),
            'is_rfu' => true,
        ]);

        $this->actingAs($user)
            ->post(route('equipment.update-bd'), [
                'equipments' => [$equipment->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertFalse($equipment->fresh()->is_rfu);
    }

    public function test_non_active_unit_is_ignored_during_bulk_update(): void
    {
        $user = $this->userWithView();

        $equipment = Equipment::query()->where('unit_code', 'EX-001')->firstOrFail();
        $equipment->update([
            'unitstatus_id' => $this->standbyStatusId(),
            'is_rfu' => false,
        ]);

        $this->actingAs($user)
            ->post(route('equipment.update-rfu'), [
                'equipments' => [$equipment->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('success', '0 equipment(s) updated to RFU.');

        $this->assertFalse($equipment->fresh()->is_rfu);
    }

    public function test_empty_selection_fails_validation(): void
    {
        $user = $this->userWithView();

        $this->actingAs($user)
            ->post(route('equipment.update-rfu'), [
                'equipments' => [],
            ])
            ->assertSessionHasErrors('equipments');
    }

    public function test_equipment_index_includes_rfu_and_bd_candidates(): void
    {
        $user = $this->userWithView();
        $activeId = $this->activeStatusId();

        $bdUnit = Equipment::query()->where('unit_code', 'EX-001')->firstOrFail();
        $bdUnit->update(['unitstatus_id' => $activeId, 'is_rfu' => false]);

        $rfuUnit = Equipment::query()->updateOrCreate(
            ['unit_code' => 'RFU-TEST'],
            [
                'description' => 'RFU test unit',
                'unitstatus_id' => $activeId,
                'is_rfu' => true,
                'is_active' => true,
            ],
        );

        $this->actingAs($user)
            ->get(route('equipment.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Masters/Equipment/Index')
                ->has('rfuCandidates', 1)
                ->has('bdCandidates', 1)
                ->where('rfuCandidates.0.value', $bdUnit->id)
                ->where('bdCandidates.0.value', $rfuUnit->id)
            );
    }
}
