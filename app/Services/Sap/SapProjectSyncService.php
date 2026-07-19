<?php

namespace App\Services\Sap;

use App\Models\Department;
use App\Models\Project;
use App\Models\SapSyncRun;
use Illuminate\Support\Facades\DB;

class SapProjectSyncService
{
    public function __construct(
        protected SapService $sapService,
    ) {}

    public function sync(?int $triggeredBy = null): array
    {
        $run = SapSyncRun::query()->create([
            'entity_type' => 'projects',
            'status' => 'running',
            'started_at' => now(),
            'triggered_by' => $triggeredBy,
        ]);

        $created = 0;
        $updated = 0;
        $failed = 0;
        $errors = [];

        try {
            $sapProjects = $this->sapService->getProjects();

            DB::transaction(function () use ($sapProjects, &$created, &$updated, &$failed, &$errors) {
                foreach ($sapProjects as $sapProject) {
                    try {
                        $sapCode = $sapProject['Code'] ?? null;

                        if (! $sapCode) {
                            $failed++;

                            continue;
                        }

                        $project = Project::withTrashed()->where('sap_code', $sapCode)->first();

                        $payload = [
                            'code' => $sapCode,
                            'sap_code' => $sapCode,
                            'name' => $sapProject['Name'] ?? $sapCode,
                            'is_active' => SapService::sapYesNo($sapProject['Active'] ?? 'tYES'),
                            'synced_at' => now(),
                        ];

                        if ($project) {
                            if ($project->trashed()) {
                                $project->restore();
                            }

                            $project->update(array_merge($payload, [
                                'is_selectable' => $project->is_selectable,
                            ]));
                            $updated++;
                        } else {
                            Project::create(array_merge($payload, [
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
