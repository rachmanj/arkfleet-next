<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\DocumentType;
use App\Models\Equipment;
use App\Models\EquipmentDocument;
use App\Models\UnitModel;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $model = UnitModel::query()->first();
        $dept = Department::query()->where('sap_code', 'OPR')->first()
            ?? Department::query()->first();
        $stnk = DocumentType::query()->where('code', 'STNK')->first();

        if ($model && $dept) {
            $second = Equipment::query()->updateOrCreate(
                ['unit_code' => 'DT-002'],
                [
                    'description' => 'Sample dump truck for IPA/doc testing',
                    'unit_model_id' => $model->id,
                    'department_id' => $dept->id,
                    'project_code' => '001H',
                    'acquisition_cost' => 800_000_000,
                    'in_service_date' => now()->subYear()->toDateString(),
                    'is_active' => true,
                ],
            );

            $ex001 = Equipment::query()->where('unit_code', 'EX-001')->first();

            if ($ex001 && $stnk) {
                EquipmentDocument::query()->updateOrCreate(
                    [
                        'equipment_id' => $ex001->id,
                        'document_type_id' => $stnk->id,
                        'document_number' => 'STNK-EX001-DEMO',
                    ],
                    [
                        'expiry_date' => now()->addDays(14),
                        'is_active' => true,
                    ],
                );
            }
        }
    }
}
