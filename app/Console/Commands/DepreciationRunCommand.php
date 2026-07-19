<?php

namespace App\Console\Commands;

use App\Services\Depreciation\DepreciationRunService;
use Illuminate\Console\Command;

class DepreciationRunCommand extends Command
{
    protected $signature = 'depreciation:run {year} {month} {--book=all : book, tax, or all}';

    protected $description = 'Run depreciation for a period (idempotent per asset/book/period)';

    public function handle(DepreciationRunService $service): int
    {
        $year = (int) $this->argument('year');
        $month = (int) $this->argument('month');
        $book = $this->option('book');

        if (! in_array($book, ['all', 'book', 'tax'], true)) {
            $this->error('Invalid --book option. Use all, book, or tax.');

            return self::FAILURE;
        }

        $run = $service->runPeriod($year, $month, $book);

        $this->info("Depreciation run {$run->periodLabel()} — {$run->entry_count} entries.");
        $this->line("Book total: {$run->total_book_depreciation}");
        $this->line("Tax total: {$run->total_tax_depreciation}");

        return self::SUCCESS;
    }
}
