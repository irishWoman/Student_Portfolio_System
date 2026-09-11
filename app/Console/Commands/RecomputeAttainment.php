<?php

namespace App\Console\Commands;

use App\Models\AcademicYear;
use App\Models\Portfolio;
use App\Services\PloAttainmentService;
use Illuminate\Console\Command;

/**
 * Recompute every PLO attainment snapshot for an academic year.
 *
 *   php artisan portfolio:recompute-attainment
 *   php artisan portfolio:recompute-attainment --year=2026-2027
 */
class RecomputeAttainment extends Command
{
    protected $signature = 'portfolio:recompute-attainment {--year= : Academic year label}';

    protected $description = 'Recompute PLO attainment snapshots for every portfolio in an academic year';

    public function handle(PloAttainmentService $attainment): int
    {
        $year = $this->option('year')
            ? AcademicYear::where('label', $this->option('year'))->firstOrFail()
            : AcademicYear::current();

        if (! $year) {
            $this->error('No academic year found.');

            return self::FAILURE;
        }

        $portfolios = Portfolio::where('academic_year_id', $year->id)->get();
        $bar = $this->output->createProgressBar($portfolios->count());

        foreach ($portfolios as $portfolio) {
            $attainment->recomputeForPortfolio($portfolio);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Recomputed {$portfolios->count()} portfolio(s) for {$year->label}.");

        return self::SUCCESS;
    }
}
