<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Services\Sap\SapProjectSyncService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_selectable_and_active_scopes(): void
    {
        Project::create([
            'code' => 'AAA',
            'sap_code' => 'AAA',
            'name' => 'Visible Active',
            'is_active' => true,
            'is_selectable' => true,
        ]);

        Project::create([
            'code' => 'BBB',
            'sap_code' => 'BBB',
            'name' => 'Hidden Active',
            'is_active' => true,
            'is_selectable' => false,
        ]);

        $this->assertCount(1, Project::selectable()->active()->get());
    }

    public function test_projects_index_requires_view_permission(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $user = User::factory()->create();
        $user->givePermissionTo('view');

        $this->actingAs($user)
            ->get(route('masters.projects.index'))
            ->assertOk();
    }

    public function test_project_sync_preserves_is_selectable(): void
    {
        $project = Project::create([
            'code' => '000H',
            'sap_code' => '000H',
            'name' => 'Head Office',
            'is_active' => true,
            'is_selectable' => false,
        ]);

        $sapService = Mockery::mock(\App\Services\Sap\SapService::class);
        $sapService->shouldReceive('getProjects')->andReturn([
            ['Code' => '000H', 'Name' => 'Updated Name', 'Active' => 'tYES'],
        ]);

        $this->app->instance(\App\Services\Sap\SapService::class, $sapService);

        app(SapProjectSyncService::class)->sync();

        $project->refresh();

        $this->assertSame('Updated Name', $project->name);
        $this->assertFalse($project->is_selectable);
        $this->assertNotNull($project->synced_at);
    }
}
