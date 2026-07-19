<?php

namespace Tests\Feature;

use App\Models\DocumentType;
use App\Models\Equipment;
use App\Models\EquipmentDocument;
use App\Models\EquipmentPhoto;
use App\Models\Project;
use App\Models\UnitNoHistory;
use App\Models\User;
use Database\Seeders\DocumentTypeSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EquipmentShowTest extends TestCase
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

    public function test_equipment_show_requires_view_permission(): void
    {
        $user = $this->userWithView();
        $equipment = Equipment::query()->firstOrFail();
        $equipment->update([
            'serial_no' => 'SN-TEST-001',
            'engine_model' => 'Cummins QSB',
            'machine_no' => 'MN-100',
            'nomor_polisi' => 'B 1234 XX',
            'remarks' => 'Identity fields present',
        ]);

        $this->actingAs($user)
            ->get(route('equipment.show', $equipment))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Masters/Equipment/Show')
                ->has('equipment')
                ->has('movingLines')
                ->where('equipment.id', $equipment->id)
                ->where('equipment.serial_no', 'SN-TEST-001')
                ->where('equipment.engine_model', 'Cummins QSB')
                ->where('equipment.machine_no', 'MN-100')
            );
    }

    public function test_equipment_show_includes_grouped_documents(): void
    {
        $user = $this->userWithView();
        $equipment = Equipment::query()->firstOrFail();

        $stnk = DocumentType::query()->where('code', 'STNK')->firstOrFail();
        $insurance = DocumentType::query()->where('code', 'INSURANCE')->firstOrFail();

        EquipmentDocument::create([
            'equipment_id' => $equipment->id,
            'document_type_id' => $stnk->id,
            'document_number' => 'STNK-TEST-001',
            'issued_date' => now()->subYear(),
            'expiry_date' => now()->addMonth(),
            'is_active' => true,
        ]);

        EquipmentDocument::create([
            'equipment_id' => $equipment->id,
            'document_type_id' => $insurance->id,
            'document_number' => 'INS-TEST-001',
            'issued_date' => now()->subMonths(2),
            'amount' => 1500000,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('equipment.show', $equipment))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('equipment.documents', 2)
                ->where('equipment.documents', fn ($documents) => collect($documents)
                    ->pluck('document_number')
                    ->contains('STNK-TEST-001') && collect($documents)
                    ->pluck('document_number')
                    ->contains('INS-TEST-001'))
            );
    }

    public function test_user_can_upload_and_delete_equipment_photo(): void
    {
        Storage::fake('public');

        $user = $this->userWithView();
        $equipment = Equipment::query()->firstOrFail();

        $this->actingAs($user)
            ->post(route('equipment.photos.store', $equipment), [
                'file' => UploadedFile::fake()->image('unit.jpg'),
                'description' => 'Front view',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $photo = EquipmentPhoto::query()->where('equipment_id', $equipment->id)->first();
        $this->assertNotNull($photo);
        Storage::disk('public')->assertExists($photo->file_path);

        $this->actingAs($user)
            ->delete(route('equipment.photos.destroy', $photo))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('equipment_photos', ['id' => $photo->id]);
    }

    public function test_user_can_store_unit_number_history_without_renaming_equipment(): void
    {
        $user = $this->userWithView();
        $equipment = Equipment::query()->firstOrFail();
        $originalUnitCode = $equipment->unit_code;

        $this->actingAs($user)
            ->post(route('equipment.unit-no-history.store', $equipment), [
                'date' => now()->toDateString(),
                'new_unit_code' => 'EX-999',
                'remarks' => 'Renumbered for project transfer',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('unit_no_histories', [
            'equipment_id' => $equipment->id,
            'old_unit_code' => $originalUnitCode,
            'new_unit_code' => 'EX-999',
            'created_by' => $user->id,
        ]);

        $equipment->refresh();
        $this->assertSame($originalUnitCode, $equipment->unit_code);
    }

    public function test_equipment_show_loads_moving_lines_for_equipment(): void
    {
        $user = $this->userWithView();
        $equipment = Equipment::query()->firstOrFail();
        $toProject = Project::query()->where('code', '001H')->firstOrFail();

        $this->actingAs($user)->post(route('movings.cart.add'), [
            'equipment_id' => $equipment->id,
            'to_project_code' => $toProject->code,
        ]);

        $this->actingAs($user)->post(route('movings.submit'), [
            'from_project_code' => '000H',
            'to_project_code' => $toProject->code,
        ]);

        $this->actingAs($user)
            ->get(route('equipment.show', $equipment))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('movingLines', 1)
                ->where('movingLines.0.equipment_id', $equipment->id)
            );
    }

    public function test_equipment_payreq_summary_proxies_external_service(): void
    {
        config(['services.payreq.api_url' => 'http://192.168.32.17/payreq-x-v3']);

        Http::fake([
            '*/api/realization-details/sum-by-unit*' => Http::response([
                'status' => 'success',
                'data' => [
                    'unit_no' => 'EX-001',
                    'type_sums' => [
                        ['type' => 'fuel', 'total_amount' => '1,000,000.00'],
                    ],
                    'grand_total' => '1,000,000.00',
                ],
            ]),
        ]);

        $user = $this->userWithView();
        $equipment = Equipment::query()->where('unit_code', 'EX-001')->firstOrFail();

        $this->actingAs($user)
            ->getJson(route('equipment.payreq-summary', $equipment))
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.grand_total', '1,000,000.00');
    }
}
