<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    private function authenticatedApiUser(): User
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(MasterDataSeeder::class);

        $user = User::factory()->create();
        $user->givePermissionTo('view');

        Sanctum::actingAs($user, ['api:read']);

        return $user;
    }

    public function test_equipment_index_requires_sanctum_token(): void
    {
        $this->getJson('/api/v1/equipment')->assertUnauthorized();
    }

    public function test_equipment_index_returns_paginated_json(): void
    {
        $this->authenticatedApiUser();

        $this->getJson('/api/v1/equipment')
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    public function test_projects_index_returns_projects(): void
    {
        $this->authenticatedApiUser();

        Project::create([
            'code' => 'TST',
            'sap_code' => 'TST',
            'name' => 'Test Project',
            'is_active' => true,
            'is_selectable' => true,
        ]);

        $this->getJson('/api/v1/projects')
            ->assertOk()
            ->assertJsonFragment(['code' => 'TST']);
    }

    public function test_equipment_show_returns_single_record(): void
    {
        $this->authenticatedApiUser();

        $equipment = Equipment::query()->where('unit_code', 'EX-001')->firstOrFail();

        $this->getJson("/api/v1/equipment/{$equipment->id}")
            ->assertOk()
            ->assertJsonPath('data.unit_code', 'EX-001');
    }

    public function test_api_token_ui_is_accessible(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $user = User::factory()->create();
        $user->givePermissionTo('view');

        $this->actingAs($user)
            ->get(route('settings.api-keys.index'))
            ->assertOk();
    }

    public function test_user_can_create_api_token(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $user = User::factory()->create();
        $user->givePermissionTo('view');

        $this->actingAs($user)
            ->post(route('settings.api-keys.store'), ['name' => 'Test integration'])
            ->assertRedirect();

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'Test integration',
        ]);
    }
}
