<?php

return [
    'connection' => env('LEGACY_DB_CONNECTION', 'legacy'),

    'chunk_size' => (int) env('LEGACY_MIGRATION_CHUNK_SIZE', 200),

    'role_map' => [
        'superadmin' => 'Admin',
        'admin' => 'admin',
        'user' => 'user',
    ],

    'document_type_codes' => [
        'BPKB' => 'BPKB',
        'STNK' => 'STNK',
        'Purchase Order' => 'PO',
        'Faktur' => 'FAKTUR',
        'Polis Asuransi' => 'INSURANCE',
        'Other Payment' => 'OTHER_PAYMENT',
        'KIR' => 'KIR',
        'Warranty' => 'WARRANTY',
        'BAST' => 'BAST',
        'Invoice' => 'INVOICE',
    ],

    'document_type_alert_days' => [
        'STNK' => 30,
        'KIR' => 30,
        'INSURANCE' => 45,
        'PO' => 30,
        'FAKTUR' => 30,
        'INVOICE' => 30,
        'BPKB' => 60,
        'WARRANTY' => 45,
        'BAST' => 30,
        'OTHER_PAYMENT' => 30,
    ],

    'order' => [
        'manufactures',
        'plant_types',
        'plant_groups',
        'asset_categories',
        'unitstatuses',
        'suppliers',
        'unit_models',
        'document_types',
        'departments',
        'projects',
        'users',
        'user_roles',
        'equipment',
        'equipment_documents',
    ],

    'entities' => [
        'manufactures' => [
            'legacy_table' => 'manufactures',
            'target' => \App\Models\Manufacture::class,
            'upsert_by' => ['name'],
            'notes' => '129 legacy rows; upsert by unique name.',
        ],
        'plant_types' => [
            'legacy_table' => 'plant_types',
            'target' => \App\Models\PlantType::class,
            'upsert_by' => ['name'],
            'notes' => '4 legacy rows.',
        ],
        'plant_groups' => [
            'legacy_table' => 'plant_groups',
            'target' => \App\Models\PlantGroup::class,
            'upsert_by' => ['name'],
            'notes' => '59 legacy rows; legacy plant_type_id dropped (v2 has no FK).',
        ],
        'asset_categories' => [
            'legacy_table' => 'asset_categories',
            'target' => \App\Models\AssetCategory::class,
            'upsert_by' => ['name'],
            'notes' => '2 legacy rows.',
        ],
        'unitstatuses' => [
            'legacy_table' => 'unitstatuses',
            'target' => \App\Models\Unitstatus::class,
            'upsert_by' => ['name'],
            'notes' => '4 legacy rows.',
        ],
        'suppliers' => [
            'legacy_table' => 'suppliers',
            'target' => \App\Models\Supplier::class,
            'upsert_by' => ['name'],
            'notes' => '106 legacy rows; code generated from legacy id.',
        ],
        'unit_models' => [
            'legacy_table' => 'unit_models',
            'target' => \App\Models\UnitModel::class,
            'upsert_by' => ['name'],
            'notes' => '396 legacy rows; model_no -> name, manufacture_id used for equipment only.',
        ],
        'document_types' => [
            'legacy_table' => 'document_types',
            'target' => \App\Models\DocumentType::class,
            'upsert_by' => ['code'],
            'notes' => '10 legacy types mapped to stable codes (STNK, KIR, PO, etc.).',
        ],
        'departments' => [
            'legacy_table' => 'departments',
            'target' => \App\Models\Department::class,
            'upsert_by' => ['department_name'],
            'notes' => '9 legacy rows; sap_code defaults to akronim.',
        ],
        'projects' => [
            'legacy_table' => 'projects',
            'target' => \App\Models\Project::class,
            'upsert_by' => ['code'],
            'notes' => '17 legacy rows; project_code -> code, bowheer -> name.',
        ],
        'users' => [
            'legacy_table' => 'users',
            'target' => \App\Models\User::class,
            'upsert_by' => ['username'],
            'notes' => '14 legacy users; bcrypt passwords preserved; inactive users skipped.',
        ],
        'user_roles' => [
            'legacy_table' => 'model_has_roles',
            'target' => null,
            'notes' => 'Map legacy Spatie roles to v2 roles via username.',
        ],
        'equipment' => [
            'legacy_table' => 'equipments',
            'target' => \App\Models\Equipment::class,
            'upsert_by' => ['unit_code'],
            'notes' => '989 legacy rows; identity fields (serial/engine/machine/etc), FK remap, active_date -> in_service_date.',
        ],
        'equipment_documents' => [
            'legacy_table' => 'documents',
            'target' => \App\Models\EquipmentDocument::class,
            'upsert_by' => null,
            'notes' => '555 legacy documents; equipment_id and document_type_id remapped.',
        ],
    ],

    'truncate_on_fresh' => [
        'equipment_documents',
        'equipment',
        'unit_models',
        'manufactures',
        'plant_groups',
        'plant_types',
        'asset_categories',
        'unitstatuses',
        'suppliers',
    ],
];
