<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Department;
use App\Models\DocumentType;
use App\Models\Equipment;
use App\Models\EquipmentDocument;
use App\Models\Project;
use App\Models\User;
use App\Services\Operations\IpaTransferService;
use Database\Seeders\DocumentTypeSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationsTest extends TestCase
{
    use RefreshDatabase;

    private function userWithView(): User
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(MasterDataSeeder::class);
        $this->seed(DocumentTypeSeeder::class);

        $user = User::factory()->create();
        $user->givePermissionTo('view');

        return $user;
    }

    public function test_movings_index_requires_view_permission(): void
    {
        $user = $this->userWithView();

        $this->actingAs($user)
            ->get(route('movings.index'))
            ->assertOk();
    }

    public function test_ipa_transfer_updates_equipment_and_clears_cart(): void
    {
        $user = $this->userWithView();
        $equipment = Equipment::query()->where('unit_code', 'EX-001')->firstOrFail();
        $toProject = Project::query()->where('code', '001H')->firstOrFail();
        $service = app(IpaTransferService::class);

        $moving = $service->createIpa($user->id, [
            'ipa_no' => 'IPA-TEST-001',
            'ipa_date' => now()->toDateString(),
            'from_project_code' => '000H',
            'to_project_code' => $toProject->code,
            'tujuan_row_1' => 'Site Manager',
            'cc_row_1' => 'HO Jakarta',
        ]);

        $service->addEquipment($moving, $user->id, [
            'equipment_id' => $equipment->id,
            'to_project_code' => $toProject->code,
        ]);

        $transfer = $service->submitIpa($moving);

        $this->assertStringStartsWith('IPA-', $transfer->transfer_number);
        $this->assertSame('SUBMITTED', $transfer->status);
        $this->assertSame(1, $transfer->line_count);
        $this->assertDatabaseMissing('cart_items', ['ipa_transfer_id' => $moving->id]);

        $equipment->refresh();
        $this->assertSame($toProject->code, $equipment->project_code);
    }

    public function test_document_extend_increments_extend_count(): void
    {
        $user = $this->userWithView();
        $equipment = Equipment::query()->firstOrFail();
        $documentType = DocumentType::query()->firstOrFail();

        $document = EquipmentDocument::create([
            'equipment_id' => $equipment->id,
            'document_type_id' => $documentType->id,
            'document_number' => 'STNK-001',
            'expiry_date' => now()->addDays(10),
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('documents.extend', $document), ['extend_days' => 30])
            ->assertRedirect();

        $document->refresh();

        $this->assertSame(1, $document->extend_count);
        $this->assertTrue($document->expiry_date->greaterThan(now()->addDays(35)));
    }

    public function test_dashboard_shows_expiring_document_stats(): void
    {
        $user = $this->userWithView();
        $equipment = Equipment::query()->firstOrFail();
        $documentType = DocumentType::query()->firstOrFail();

        EquipmentDocument::create([
            'equipment_id' => $equipment->id,
            'document_type_id' => $documentType->id,
            'expiry_date' => now()->addDays(5),
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard')
                ->where('stats.expiring_documents', 1));
    }

    public function test_reports_index_is_accessible(): void
    {
        $user = $this->userWithView();

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertOk();
    }
}
