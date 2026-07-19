<?php

namespace App\Console\Commands;

use App\Services\Migration\LegacyMigrationService;
use Illuminate\Console\Command;

class LegacyMigrateCommand extends Command
{
    protected $signature = 'legacy:migrate
                            {--execute : Run import (default is dry-run only)}
                            {--fresh : Truncate equipment/master tables before import}
                            {--force : Skip confirmation prompt}';

    protected $description = 'Legacy arkfleet_db → v2 data migration (dry-run by default)';

    public function handle(LegacyMigrationService $service): int
    {
        if (! $this->option('execute')) {
            $report = $service->dryRun();

            $this->info('Legacy migration DRY RUN — no data written.');
            $this->newLine();

            foreach ($report['plan'] as $step) {
                $count = $step['legacy_count'] === null ? 'unavailable' : $step['legacy_count'];
                $this->line("• {$step['entity']}: {$step['legacy_table']} → {$step['target']} ({$count} rows)");
                if ($step['notes']) {
                    $this->comment("  {$step['notes']}");
                }
            }

            $this->newLine();
            foreach ($report['warnings'] as $warning) {
                $this->warn($warning);
            }

            $this->newLine();
            $this->line('Re-run with --execute after validating mappings. Add --fresh to truncate equipment/master tables first.');

            return self::SUCCESS;
        }

        if (! $service->isConfigured()) {
            $this->error('Legacy DB not configured. Set LEGACY_DB_* in .env');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('Execute legacy import into v2 database? Ensure you have a backup.')) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        $result = $service->execute(fresh: (bool) $this->option('fresh'));

        $this->info('Legacy migration complete.'.($result['fresh'] ? ' (fresh truncate applied)' : ''));
        $this->newLine();

        foreach ($result['results'] as $entity => $stats) {
            $errors = count($stats['errors'] ?? []);
            $this->line(sprintf(
                '%s: imported %d, skipped %d, failed %d / %d legacy rows%s',
                $entity,
                $stats['imported'] ?? 0,
                $stats['skipped'] ?? 0,
                $stats['failed'] ?? 0,
                $stats['legacy_rows'] ?? 0,
                $errors > 0 ? " ({$errors} errors)" : '',
            ));

            foreach (array_slice($stats['errors'] ?? [], 0, 3) as $error) {
                $this->comment("  - {$error}");
            }
        }

        return self::SUCCESS;
    }
}
