<?php

namespace App\Services\Sap;

use App\Models\Department;
use App\Models\SapSyncRun;
use Illuminate\Support\Facades\DB;

class SapDepartmentSyncService
{
    public function __construct(
        protected SapService $sapService,
    ) {}

    public function sync(?int $triggeredBy = null): array
    {
        $run = SapSyncRun::query()->create([
            'entity_type' => 'departments',
            'status' => 'running',
            'started_at' => now(),
            'triggered_by' => $triggeredBy,
        ]);

        $created = 0;
        $updated = 0;
        $failed = 0;
        $errors = [];

        try {
            $centers = $this->sapService->getProfitCenters();

            DB::transaction(function () use ($centers, &$created, &$updated, &$failed, &$errors) {
                foreach ($centers as $center) {
                    try {
                        $sapCode = $center['CenterCode'] ?? null;

                        if (! $sapCode) {
                            $failed++;

                            continue;
                        }

                        $department = Department::withTrashed()->where('sap_code', $sapCode)->first();

                        $payload = [
                            'department_name' => $center['CenterName'] ?? $sapCode,
                            'sap_code' => $sapCode,
                            'is_active' => SapService::sapYesNo($center['Active'] ?? 'tYES'),
                            'synced_at' => now(),
                        ];

                        if ($department) {
                            if ($department->trashed()) {
                                $department->restore();
                            }

                            $department->update(array_merge($payload, [
                                'is_selectable' => $department->is_selectable,
                                'akronim' => $department->akronim,
                                'parent_id' => $department->parent_id,
                            ]));
                            $updated++;
                        } else {
                            Department::create(array_merge($payload, [
                                'is_selectable' => true,
                            ]));
                            $created++;
                        }
                    } catch (\Throwable $exception) {
                        $failed++;
                        $errors[] = $exception->getMessage();
                    }
                }
            });

            $run->update([
                'status' => 'completed',
                'created_count' => $created,
                'updated_count' => $updated,
                'failed_count' => $failed,
                'error_summary' => $errors !== [] ? implode('; ', array_slice($errors, 0, 5)) : null,
                'finished_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            $run->update([
                'status' => 'failed',
                'failed_count' => $failed + 1,
                'error_summary' => $exception->getMessage(),
                'finished_at' => now(),
            ]);

            throw $exception;
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'failed' => $failed,
            'run_id' => $run->id,
        ];
    }
}
