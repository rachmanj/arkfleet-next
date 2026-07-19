<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\User;
use App\Services\AI\NlqQueryService;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class NlqTest extends TestCase
{
    use RefreshDatabase;

    public function test_validate_spec_rejects_unknown_source(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(NlqQueryService::class)->validateSpec(['source' => 'users']);
    }

    public function test_execute_equipment_query_with_filters(): void
    {
        $this->seed(MasterDataSeeder::class);

        $result = app(NlqQueryService::class)->execute([
            'source' => 'equipment',
            'columns' => ['unit_code', 'project_code', 'is_active'],
            'filters' => [
                ['column' => 'unit_code', 'operator' => 'like', 'value' => 'EX'],
            ],
            'limit' => 10,
        ]);

        $this->assertGreaterThan(0, $result['count']);
        $this->assertSame('equipment', $result['spec']['source']);
        $this->assertSame('EX-001', $result['rows'][0]['unit_code']);
    }

    public function test_nlq_page_requires_view_permission(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $user = User::factory()->create();
        $user->givePermissionTo('view');

        $this->actingAs($user)
            ->get(route('reports.nlq'))
            ->assertOk();
    }

    public function test_catalog_lists_allowlisted_sources(): void
    {
        $catalog = app(NlqQueryService::class)->catalog();

        $sources = collect($catalog)->pluck('source')->all();

        $this->assertContains('equipment', $sources);
        $this->assertContains('projects', $sources);
        $this->assertNotContains('users', $sources);
    }
}
