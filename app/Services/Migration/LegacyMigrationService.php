<?php

namespace App\Services\Migration;

use App\Models\AssetCategory;
use App\Models\Department;
use App\Models\DocumentType;
use App\Models\Equipment;
use App\Models\EquipmentDocument;
use App\Models\Manufacture;
use App\Models\PlantGroup;
use App\Models\PlantType;
use App\Models\Project;
use App\Models\Supplier;
use App\Models\UnitModel;
use App\Models\Unitstatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\Models\Role;

class LegacyMigrationService
{
    protected array $idMaps = [];

    protected array $projectCodeByLegacyId = [];

    protected array $unitModelManufactureByLegacyId = [];

    public function isConfigured(): bool
    {
        return filled(config('legacy_migration.connection'))
            && filled(config('database.connections.'.config('legacy_migration.connection').'.database'));
    }

    public function plan(): array
    {
        $plan = [];

        foreach ($this->orderedEntities() as $key => $config) {
            $plan[] = [
                'entity' => $key,
                'legacy_table' => $config['legacy_table'],
                'target' => is_string($config['target'] ?? null) ? $config['target'] : ($config['target'] ?? 'handler'),
                'upsert_by' => $config['upsert_by'] ?? null,
                'notes' => $config['notes'] ?? null,
                'legacy_count' => $this->safeCount($config['legacy_table']),
            ];
        }

        return $plan;
    }

    public function dryRun(): array
    {
        return [
            'mode' => 'dry-run',
            'configured' => $this->isConfigured(),
            'connection' => config('legacy_migration.connection'),
            'plan' => $this->plan(),
            'warnings' => [
                'Review mappings against arkfleet_db before --execute.',
                'Run on staging with a legacy DB snapshot first.',
                'Equipment financial fields remain null; fixed_assets capitalization is a separate step.',
                'IPA transfer history (movings/moving_details) is not auto-migrated.',
            ],
        ];
    }

    public function execute(bool $fresh = false): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Legacy DB is not configured. Set LEGACY_DB_* environment variables.');
        }

        $this->idMaps = [];
        $this->projectCodeByLegacyId = [];
        $this->unitModelManufactureByLegacyId = [];

        if ($fresh) {
            $this->truncateFreshTables();
        }

        $this->preloadProjectCodes();
        $this->preloadUnitModelManufactures();

        $results = [];

        foreach (array_keys($this->orderedEntities()) as $entity) {
            $results[$entity] = $this->migrateEntity($entity);
        }

        return [
            'mode' => 'execute',
            'fresh' => $fresh,
            'results' => $results,
        ];
    }

    protected function migrateEntity(string $entity): array
    {
        return DB::transaction(fn () => match ($entity) {
            'manufactures' => $this->migrateSimpleMaster($entity, Manufacture::class, fn ($row) => [
                'name' => $row->name,
                'is_active' => true,
            ]),
            'plant_types' => $this->migrateSimpleMaster($entity, PlantType::class, fn ($row) => [
                'name' => $row->name,
                'is_active' => true,
            ]),
            'plant_groups' => $this->migrateSimpleMaster($entity, PlantGroup::class, fn ($row) => [
                'name' => $row->name,
                'is_active' => true,
            ]),
            'asset_categories' => $this->migrateSimpleMaster($entity, AssetCategory::class, fn ($row) => [
                'name' => $row->name,
                'is_active' => true,
            ]),
            'unitstatuses' => $this->migrateSimpleMaster($entity, Unitstatus::class, fn ($row) => [
                'name' => $row->name,
                'is_active' => true,
            ]),
            'suppliers' => $this->migrateSuppliers(),
            'unit_models' => $this->migrateUnitModels(),
            'document_types' => $this->migrateDocumentTypes(),
            'departments' => $this->migrateDepartments(),
            'projects' => $this->migrateProjects(),
            'users' => $this->migrateUsers(),
            'user_roles' => $this->migrateUserRoles(),
            'equipment' => $this->migrateEquipment(),
            'equipment_documents' => $this->migrateEquipmentDocuments(),
            default => throw new RuntimeException("Unknown migration entity [{$entity}]"),
        });
    }

    protected function migrateSimpleMaster(string $entity, string $modelClass, callable $transform): array
    {
        $stats = $this->emptyStats();

        $this->legacyQuery($this->entityConfig($entity)['legacy_table'])
            ->orderBy('id')
            ->chunk($this->chunkSize(), function (Collection $rows) use ($entity, $modelClass, $transform, &$stats) {
                foreach ($rows as $row) {
                    try {
                        $payload = $transform($row);
                        $record = $modelClass::query()->updateOrCreate(
                            ['name' => $payload['name']],
                            $payload,
                        );

                        $this->rememberId($entity, (int) $row->id, (int) $record->id);
                        $stats['imported']++;
                    } catch (\Throwable $e) {
                        $stats['failed']++;
                        $stats['errors'][] = "legacy id {$row->id}: {$e->getMessage()}";
                    }
                }
            });

        $stats['legacy_rows'] = $this->safeCount($this->entityConfig($entity)['legacy_table']) ?? 0;

        return $stats;
    }

    protected function migrateSuppliers(): array
    {
        $stats = $this->emptyStats();

        $this->legacyQuery('suppliers')
            ->orderBy('id')
            ->chunk($this->chunkSize(), function (Collection $rows) use (&$stats) {
                foreach ($rows as $row) {
                    try {
                        $code = 'LEG-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT);
                        $record = Supplier::query()->updateOrCreate(
                            ['name' => $row->name],
                            [
                                'code' => $code,
                                'name' => $row->name,
                                'is_active' => true,
                            ],
                        );

                        $this->rememberId('suppliers', (int) $row->id, (int) $record->id);
                        $stats['imported']++;
                    } catch (\Throwable $e) {
                        $stats['failed']++;
                        $stats['errors'][] = "legacy id {$row->id}: {$e->getMessage()}";
                    }
                }
            });

        $stats['legacy_rows'] = $this->safeCount('suppliers') ?? 0;

        return $stats;
    }

    protected function migrateUnitModels(): array
    {
        $stats = $this->emptyStats();

        $this->legacyQuery('unit_models')
            ->orderBy('id')
            ->chunk($this->chunkSize(), function (Collection $rows) use (&$stats) {
                foreach ($rows as $row) {
                    try {
                        $name = trim((string) ($row->model_no ?: $row->description ?: 'Model '.$row->id));
                        $record = UnitModel::query()->updateOrCreate(
                            ['name' => $name],
                            [
                                'code' => $row->model_no ?: null,
                                'is_active' => true,
                            ],
                        );

                        $this->rememberId('unit_models', (int) $row->id, (int) $record->id);

                        if ($row->manufacture_id) {
                            $this->unitModelManufactureByLegacyId[(int) $row->id] = (int) $row->manufacture_id;
                        }

                        $stats['imported']++;
                    } catch (\Throwable $e) {
                        $stats['failed']++;
                        $stats['errors'][] = "legacy id {$row->id}: {$e->getMessage()}";
                    }
                }
            });

        $stats['legacy_rows'] = $this->safeCount('unit_models') ?? 0;

        return $stats;
    }

    protected function migrateDocumentTypes(): array
    {
        $stats = $this->emptyStats();
        $codeMap = config('legacy_migration.document_type_codes', []);
        $alertDays = config('legacy_migration.document_type_alert_days', []);

        $this->legacyQuery('document_types')
            ->orderBy('id')
            ->chunk($this->chunkSize(), function (Collection $rows) use ($codeMap, $alertDays, &$stats) {
                foreach ($rows as $row) {
                    try {
                        $code = $codeMap[$row->name] ?? Str::upper(Str::slug($row->name, '_'));
                        $record = DocumentType::query()->updateOrCreate(
                            ['code' => $code],
                            [
                                'name' => $row->name,
                                'alert_days_before' => $alertDays[$code] ?? 30,
                                'is_active' => true,
                            ],
                        );

                        $this->rememberId('document_types', (int) $row->id, (int) $record->id);
                        $stats['imported']++;
                    } catch (\Throwable $e) {
                        $stats['failed']++;
                        $stats['errors'][] = "legacy id {$row->id}: {$e->getMessage()}";
                    }
                }
            });

        $stats['legacy_rows'] = $this->safeCount('document_types') ?? 0;

        return $stats;
    }

    protected function migrateDepartments(): array
    {
        $stats = $this->emptyStats();

        $this->legacyQuery('departments')
            ->orderBy('id')
            ->chunk($this->chunkSize(), function (Collection $rows) use (&$stats) {
                foreach ($rows as $row) {
                    try {
                        $sapCode = filled($row->akronim)
                            ? $row->akronim
                            : Str::upper(Str::slug($row->department_name, ''));

                        $record = Department::query()->updateOrCreate(
                            ['department_name' => $row->department_name],
                            [
                                'akronim' => $row->akronim,
                                'sap_code' => $sapCode ?: null,
                                'is_active' => true,
                                'is_selectable' => true,
                            ],
                        );

                        $this->rememberId('departments', (int) $row->id, (int) $record->id);
                        $stats['imported']++;
                    } catch (\Throwable $e) {
                        $stats['failed']++;
                        $stats['errors'][] = "legacy id {$row->id}: {$e->getMessage()}";
                    }
                }
            });

        $stats['legacy_rows'] = $this->safeCount('departments') ?? 0;

        return $stats;
    }

    protected function migrateProjects(): array
    {
        $stats = $this->emptyStats();

        $this->legacyQuery('projects')
            ->orderBy('id')
            ->chunk($this->chunkSize(), function (Collection $rows) use (&$stats) {
                foreach ($rows as $row) {
                    try {
                        $description = collect([$row->location, $row->address, $row->city])
                            ->filter()
                            ->implode(' | ');

                        $isActive = (int) $row->isActive === 1;

                        $record = Project::query()->updateOrCreate(
                            ['code' => $row->project_code],
                            [
                                'name' => $row->bowheer,
                                'description' => $description ?: null,
                                'is_active' => $isActive,
                                'is_selectable' => $isActive,
                            ],
                        );

                        $this->rememberId('projects', (int) $row->id, (int) $record->id);
                        $this->projectCodeByLegacyId[(int) $row->id] = $row->project_code;
                        $stats['imported']++;
                    } catch (\Throwable $e) {
                        $stats['failed']++;
                        $stats['errors'][] = "legacy id {$row->id}: {$e->getMessage()}";
                    }
                }
            });

        $stats['legacy_rows'] = $this->safeCount('projects') ?? 0;

        return $stats;
    }

    protected function migrateUsers(): array
    {
        $stats = $this->emptyStats();

        $this->legacyQuery('users')
            ->orderBy('id')
            ->chunk($this->chunkSize(), function (Collection $rows) use (&$stats) {
                foreach ($rows as $row) {
                    if (! (int) $row->is_active) {
                        $stats['skipped']++;

                        continue;
                    }

                    try {
                        $username = trim((string) $row->username);
                        $email = $this->resolveUserEmail($row, $username);

                        $record = User::query()->updateOrCreate(
                            ['username' => $username],
                            [
                                'name' => $row->name ?: $username,
                                'email' => $email,
                                'password' => $row->password,
                            ],
                        );

                        $this->rememberId('users', (int) $row->id, (int) $record->id);
                        $stats['imported']++;
                    } catch (\Throwable $e) {
                        $stats['failed']++;
                        $stats['errors'][] = "legacy id {$row->id}: {$e->getMessage()}";
                    }
                }
            });

        $stats['legacy_rows'] = $this->safeCount('users') ?? 0;

        return $stats;
    }

    protected function migrateUserRoles(): array
    {
        $stats = $this->emptyStats();
        $roleMap = config('legacy_migration.role_map', []);

        $legacyRoles = $this->legacyQuery('model_has_roles as mhr')
            ->join('roles as r', 'r.id', '=', 'mhr.role_id')
            ->where('mhr.model_type', 'like', '%User%')
            ->select(['mhr.model_id', 'r.name as role_name'])
            ->get()
            ->groupBy('model_id');

        foreach ($legacyRoles as $legacyUserId => $roles) {
            $v2UserId = $this->idMaps['users'][(int) $legacyUserId] ?? null;

            if (! $v2UserId) {
                $stats['skipped']++;

                continue;
            }

            $user = User::query()->find($v2UserId);

            if (! $user) {
                $stats['skipped']++;

                continue;
            }

            $mappedRoles = $roles
                ->map(fn ($row) => $roleMap[$row->role_name] ?? $row->role_name)
                ->unique()
                ->filter(fn ($name) => Role::query()->where('name', $name)->exists())
                ->values()
                ->all();

            if ($mappedRoles === []) {
                $stats['skipped']++;

                continue;
            }

            try {
                $user->syncRoles($mappedRoles);
                $stats['imported']++;
            } catch (\Throwable $e) {
                $stats['failed']++;
                $stats['errors'][] = "user legacy id {$legacyUserId}: {$e->getMessage()}";
            }
        }

        $stats['legacy_rows'] = $legacyRoles->count();

        return $stats;
    }

    protected function migrateEquipment(): array
    {
        $stats = $this->emptyStats();

        $this->legacyQuery('equipments')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->chunk($this->chunkSize(), function (Collection $rows) use (&$stats) {
                foreach ($rows as $row) {
                    try {
                        $payload = [
                            'unit_code' => $row->unit_no,
                            'description' => $row->description,
                            'serial_no' => $row->serial_no,
                            'chasis_no' => $row->chasis_no,
                            'engine_model' => $row->engine_model,
                            'machine_no' => $row->machine_no,
                            'nomor_polisi' => $row->nomor_polisi,
                            'bahan_bakar' => $row->bahan_bakar,
                            'warna' => $row->warna,
                            'capacity' => $row->capacity,
                            'remarks' => $row->remarks,
                            'unit_model_id' => $this->mapId('unit_models', $row->unitmodel_id),
                            'manufacture_id' => $this->resolveManufactureId($row),
                            'plant_type_id' => $this->mapId('plant_types', $row->plant_type_id),
                            'plant_group_id' => $this->mapId('plant_groups', $row->plant_group_id),
                            'asset_category_id' => $this->mapId('asset_categories', $row->asset_category_id),
                            'unitstatus_id' => $this->mapId('unitstatuses', $row->unitstatus_id),
                            'project_code' => $this->resolveProjectCode($row->current_project_id),
                            'in_service_date' => $row->active_date,
                            'acquisition_date' => $row->active_date,
                            'is_rfu' => (bool) ($row->is_rfu ?? true),
                            'is_active' => true,
                        ];

                        $record = Equipment::query()->updateOrCreate(
                            ['unit_code' => $row->unit_no],
                            $payload,
                        );

                        $this->rememberId('equipment', (int) $row->id, (int) $record->id);
                        $stats['imported']++;
                    } catch (\Throwable $e) {
                        $stats['failed']++;
                        $stats['errors'][] = "legacy id {$row->id}: {$e->getMessage()}";
                    }
                }
            });

        $stats['legacy_rows'] = $this->legacyQuery('equipments')->whereNull('deleted_at')->count();

        return $stats;
    }

    protected function migrateEquipmentDocuments(): array
    {
        $stats = $this->emptyStats();

        $this->legacyQuery('documents')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->chunk($this->chunkSize(), function (Collection $rows) use (&$stats) {
                foreach ($rows as $row) {
                    $equipmentId = $this->mapId('equipment', $row->equipment_id);
                    $documentTypeId = $this->mapId('document_types', $row->document_type_id);

                    if (! $equipmentId || ! $documentTypeId) {
                        $stats['skipped']++;
                        $stats['errors'][] = "legacy id {$row->id}: missing equipment or document type mapping";

                        continue;
                    }

                    try {
                        $exists = EquipmentDocument::query()
                            ->where('equipment_id', $equipmentId)
                            ->where('document_type_id', $documentTypeId)
                            ->where('document_number', $row->document_no)
                            ->where('issued_date', $row->document_date)
                            ->exists();

                        if ($exists) {
                            $stats['skipped']++;

                            continue;
                        }

                        EquipmentDocument::query()->create([
                            'equipment_id' => $equipmentId,
                            'document_type_id' => $documentTypeId,
                            'document_number' => $row->document_no,
                            'issued_date' => $row->document_date,
                            'expiry_date' => $row->due_date,
                            'file_path' => $row->filename,
                            'notes' => $this->buildDocumentNotes($row),
                            'extend_count' => $row->extended_doc_id ? 1 : 0,
                            'is_active' => true,
                        ]);

                        $stats['imported']++;
                    } catch (\Throwable $e) {
                        $stats['failed']++;
                        $stats['errors'][] = "legacy id {$row->id}: {$e->getMessage()}";
                    }
                }
            });

        $stats['legacy_rows'] = $this->legacyQuery('documents')->whereNull('deleted_at')->count();

        return $stats;
    }

    protected function buildDocumentNotes(object $row): ?string
    {
        $parts = array_filter([
            filled($row->remarks ?? null) ? (string) $row->remarks : null,
            filled($row->amount ?? null) ? 'Amount: '.$row->amount : null,
        ]);

        return $parts === [] ? null : implode(' | ', $parts);
    }

    protected function resolveUserEmail(object $row, string $username): string
    {
        if (filled($row->email) && filter_var($row->email, FILTER_VALIDATE_EMAIL)) {
            $existing = User::query()->where('email', $row->email)->where('username', '!=', $username)->exists();

            if (! $existing) {
                return $row->email;
            }
        }

        $slug = Str::slug($username, '.');
        $candidate = ($slug ?: 'user').'@legacy.arkfleet.local';
        $suffix = 1;

        while (User::query()->where('email', $candidate)->where('username', '!=', $username)->exists()) {
            $candidate = ($slug ?: 'user').'.'.$suffix.'@legacy.arkfleet.local';
            $suffix++;
        }

        return $candidate;
    }

    protected function resolveManufactureId(object $row): ?int
    {
        if ($row->unitmodel_id) {
            $legacyManufactureId = $this->unitModelManufactureByLegacyId[(int) $row->unitmodel_id] ?? null;

            if ($legacyManufactureId) {
                return $this->mapId('manufactures', $legacyManufactureId);
            }
        }

        return null;
    }

    protected function resolveProjectCode(mixed $legacyProjectId): ?string
    {
        if (! $legacyProjectId) {
            return null;
        }

        return $this->projectCodeByLegacyId[(int) $legacyProjectId]
            ?? $this->legacyQuery('projects')->where('id', $legacyProjectId)->value('project_code');
    }

    protected function preloadProjectCodes(): void
    {
        $this->legacyQuery('projects')
            ->select(['id', 'project_code'])
            ->orderBy('id')
            ->each(function ($row) {
                $this->projectCodeByLegacyId[(int) $row->id] = $row->project_code;
            });
    }

    protected function preloadUnitModelManufactures(): void
    {
        $this->legacyQuery('unit_models')
            ->select(['id', 'manufacture_id'])
            ->whereNotNull('manufacture_id')
            ->orderBy('id')
            ->each(function ($row) {
                $this->unitModelManufactureByLegacyId[(int) $row->id] = (int) $row->manufacture_id;
            });
    }

    protected function truncateFreshTables(): void
    {
        $tables = config('legacy_migration.truncate_on_fresh', []);

        if ($tables === []) {
            return;
        }

        Model::unguarded(function () use ($tables) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            foreach ($tables as $table) {
                DB::table($table)->truncate();
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        });
    }

    protected function mapId(string $entity, mixed $legacyId): ?int
    {
        if ($legacyId === null) {
            return null;
        }

        return $this->idMaps[$entity][(int) $legacyId] ?? null;
    }

    protected function rememberId(string $entity, int $legacyId, int $v2Id): void
    {
        $this->idMaps[$entity][$legacyId] = $v2Id;
    }

    protected function orderedEntities(): array
    {
        $order = config('legacy_migration.order', []);
        $entities = config('legacy_migration.entities', []);
        $ordered = [];

        foreach ($order as $key) {
            if (isset($entities[$key])) {
                $ordered[$key] = $entities[$key];
            }
        }

        return $ordered;
    }

    protected function entityConfig(string $entity): array
    {
        return config("legacy_migration.entities.{$entity}")
            ?? throw new RuntimeException("Missing migration config for [{$entity}]");
    }

    protected function legacyQuery(string $table)
    {
        return DB::connection(config('legacy_migration.connection'))->table($table);
    }

    protected function chunkSize(): int
    {
        return max(50, (int) config('legacy_migration.chunk_size', 200));
    }

    protected function safeCount(string $table): ?int
    {
        try {
            return $this->legacyQuery($table)->count();
        } catch (\Throwable) {
            return null;
        }
    }

    protected function emptyStats(): array
    {
        return [
            'legacy_rows' => 0,
            'imported' => 0,
            'skipped' => 0,
            'failed' => 0,
            'errors' => [],
        ];
    }
}
