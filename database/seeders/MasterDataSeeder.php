<?php

namespace Database\Seeders;

use App\Models\AssetCategory;
use App\Models\Department;
use App\Models\Manufacture;
use App\Models\PlantGroup;
use App\Models\PlantType;
use App\Models\Project;
use App\Models\Supplier;
use App\Models\UnitModel;
use App\Models\Unitstatus;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        UnitModel::query()->upsert([
            ['name' => 'Excavator 20T', 'code' => 'EX20', 'is_active' => true],
            ['name' => 'Dump Truck 40T', 'code' => 'DT40', 'is_active' => true],
        ], ['name'], ['code', 'is_active']);

        Manufacture::query()->upsert([
            ['name' => 'Komatsu', 'is_active' => true],
            ['name' => 'Caterpillar', 'is_active' => true],
        ], ['name'], ['is_active']);

        PlantType::query()->upsert([
            ['name' => 'Heavy Equipment', 'is_active' => true],
            ['name' => 'Light Vehicle', 'is_active' => true],
        ], ['name'], ['is_active']);

        PlantGroup::query()->upsert([
            ['name' => 'Mining', 'is_active' => true],
            ['name' => 'Hauling', 'is_active' => true],
        ], ['name'], ['is_active']);

        AssetCategory::query()->upsert([
            ['name' => 'Mobile Plant', 'code' => 'MP', 'is_active' => true],
            ['name' => 'Support Equipment', 'code' => 'SE', 'is_active' => true],
        ], ['name'], ['code', 'is_active']);

        Unitstatus::query()->upsert([
            ['name' => 'Active', 'color' => 'green', 'is_active' => true],
            ['name' => 'Standby', 'color' => 'gold', 'is_active' => true],
            ['name' => 'Under Repair', 'color' => 'red', 'is_active' => true],
        ], ['name'], ['color', 'is_active']);

        Supplier::query()->upsert([
            ['code' => 'SUP001', 'name' => 'PT Ark Fleet Supplier', 'is_active' => true],
        ], ['code'], ['name', 'is_active']);

        Project::query()->updateOrCreate(
            ['code' => '000H'],
            ['sap_code' => '000H', 'name' => 'Head Office', 'is_active' => true, 'is_selectable' => true],
        );

        Project::query()->updateOrCreate(
            ['code' => '001H'],
            ['sap_code' => '001H', 'name' => 'Project Alpha', 'is_active' => true, 'is_selectable' => true],
        );

        Department::query()->updateOrCreate(
            ['sap_code' => 'OPR'],
            [
                'department_name' => 'Operation & Production',
                'akronim' => 'OPR',
                'is_active' => true,
                'is_selectable' => true,
            ],
        );

        $model = UnitModel::query()->first();
        $dept = Department::query()->where('sap_code', 'OPR')->first()
            ?? Department::query()->first();

        if ($model && $dept) {
            \App\Models\Equipment::query()->updateOrCreate(
                ['unit_code' => 'EX-001'],
                [
                    'description' => 'Sample excavator for IPA testing',
                    'unit_model_id' => $model->id,
                    'department_id' => $dept->id,
                    'project_code' => '000H',
                    'acquisition_cost' => 1_200_000_000,
                    'acquisition_date' => now()->subYears(2)->toDateString(),
                    'in_service_date' => now()->subYears(2)->startOfMonth()->toDateString(),
                    'salvage_value' => 60_000_000,
                    'useful_life_months' => 96,
                    'is_active' => true,
                ],
            );
        }
    }
}
