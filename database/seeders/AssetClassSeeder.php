<?php

namespace Database\Seeders;

use App\Models\AssetClass;
use Illuminate\Database\Seeder;

class AssetClassSeeder extends Seeder
{
    public function run(): void
    {
        $classes = [
            [
                'code' => 'MP-K1',
                'name' => 'Mobile Plant — Kelompok 1',
                'book_method' => 'declining_balance',
                'book_useful_life_months' => 48,
                'book_residual_rate' => 0.05,
                'tax_group' => 'kelompok_1',
                'tax_method' => 'straight_line',
                'tax_useful_life_months' => 48,
                'tax_rate' => 0.22,
                'sap_depreciation_gl' => '71201001',
                'sap_accumulated_gl' => '19201001',
            ],
            [
                'code' => 'MP-K2',
                'name' => 'Mobile Plant — Kelompok 2',
                'book_method' => 'straight_line',
                'book_useful_life_months' => 96,
                'book_residual_rate' => 0.05,
                'tax_group' => 'kelompok_2',
                'tax_method' => 'straight_line',
                'tax_useful_life_months' => 96,
                'tax_rate' => 0.22,
                'sap_depreciation_gl' => '71201001',
                'sap_accumulated_gl' => '19201001',
            ],
            [
                'code' => 'BLDG',
                'name' => 'Building — Bangunan',
                'book_method' => 'straight_line',
                'book_useful_life_months' => 240,
                'book_residual_rate' => 0,
                'tax_group' => 'bangunan',
                'tax_method' => 'straight_line',
                'tax_useful_life_months' => 240,
                'tax_rate' => 0.22,
                'sap_depreciation_gl' => '71201002',
                'sap_accumulated_gl' => '19201002',
            ],
        ];

        foreach ($classes as $class) {
            AssetClass::query()->updateOrCreate(
                ['code' => $class['code']],
                array_merge($class, ['is_active' => true]),
            );
        }
    }
}
