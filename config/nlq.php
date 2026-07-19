<?php

return [
    'enabled' => env('NLQ_ENABLED', true),

    'max_rows' => (int) env('NLQ_MAX_ROWS', 100),

    'sources' => [
        'equipment' => [
            'model' => \App\Models\Equipment::class,
            'label' => 'Equipment register',
            'columns' => [
                'id', 'unit_code', 'description', 'project_code',
                'acquisition_cost', 'in_service_date', 'is_active',
            ],
            'filters' => ['unit_code', 'project_code', 'is_active'],
        ],
        'projects' => [
            'model' => \App\Models\Project::class,
            'label' => 'Projects',
            'columns' => ['id', 'code', 'name', 'is_active', 'is_selectable'],
            'filters' => ['code', 'is_active', 'is_selectable'],
        ],
        'fixed_assets' => [
            'model' => \App\Models\FixedAsset::class,
            'label' => 'Fixed assets',
            'columns' => [
                'id', 'equipment_id', 'acquisition_cost', 'in_service_date',
                'salvage_value', 'status',
            ],
            'filters' => ['status'],
            'relations' => [
                'equipment' => ['unit_code'],
            ],
        ],
        'depreciation_entries' => [
            'model' => \App\Models\DepreciationEntry::class,
            'label' => 'Depreciation entries',
            'columns' => [
                'id', 'fixed_asset_id', 'book_type', 'period_date',
                'depreciation_amount', 'accumulated_depreciation', 'closing_nbv',
            ],
            'filters' => ['book_type', 'period_date', 'fixed_asset_id'],
        ],
        'loan_installments' => [
            'model' => \App\Models\LoanInstallment::class,
            'label' => 'Loan installments',
            'columns' => [
                'id', 'loan_id', 'installment_no', 'due_date',
                'principal_amount', 'interest_amount', 'total_amount', 'status',
            ],
            'filters' => ['status', 'loan_id', 'due_date'],
        ],
    ],
];
