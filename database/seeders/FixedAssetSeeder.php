<?php

namespace Database\Seeders;

use App\Models\AssetClass;
use App\Models\Equipment;
use App\Models\FixedAsset;
use Illuminate\Database\Seeder;

class FixedAssetSeeder extends Seeder
{
    public function run(): void
    {
        $equipment = Equipment::query()->where('unit_code', 'EX-001')->first();
        $assetClass = AssetClass::query()->where('code', 'MP-K2')->first();

        if (! $equipment || ! $assetClass) {
            return;
        }

        FixedAsset::query()->updateOrCreate(
            ['equipment_id' => $equipment->id],
            [
                'asset_class_id' => $assetClass->id,
                'acquisition_cost' => $equipment->acquisition_cost ?? 1_200_000_000,
                'acquisition_date' => $equipment->acquisition_date ?? now()->subYears(2),
                'in_service_date' => $equipment->in_service_date ?? now()->subYears(2)->startOfMonth(),
                'salvage_value' => $equipment->salvage_value ?? 60_000_000,
                'status' => 'active',
            ],
        );
    }
}
