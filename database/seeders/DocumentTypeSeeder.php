<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['code' => 'STNK', 'name' => 'STNK (Vehicle Registration)', 'alert_days_before' => 30],
            ['code' => 'KIR', 'name' => 'KIR (Roadworthiness Test)', 'alert_days_before' => 30],
            ['code' => 'INSURANCE', 'name' => 'Insurance Policy', 'alert_days_before' => 45],
            ['code' => 'SIO', 'name' => 'SIO / Operator License', 'alert_days_before' => 30],
            ['code' => 'CONTRACT', 'name' => 'Lease / Contract', 'alert_days_before' => 60],
        ];

        foreach ($types as $type) {
            DocumentType::query()->updateOrCreate(['code' => $type['code']], $type);
        }
    }
}
