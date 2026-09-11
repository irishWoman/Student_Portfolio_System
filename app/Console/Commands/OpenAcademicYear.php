<?php

namespace App\Console\Commands;

use App\Models\AcademicYear;
use App\Models\Program;
use App\Services\DeadlineService;
use App\Services\PortfolioProvisioningService;
use Illuminate\Console\Command;

/**
 * Start-of-year setup in one command:
 *   php artisan portfolio:open-year 2027-2028
 *
 * Marks the year current, creates every active student's portfolio shell, and
 * generates the default deadline schedule for all four year levels.
 */
class OpenAcademicYear extends Command
{
    protected $signature = 'portfolio:open-year {label : e.g. 2027-2028}';

    protected $description = 'Open an academic year: portfolios and deadline schedules for every student';

    public function handle(PortfolioProvisioningService $provisioning, DeadlineService $deadlines): int
    {
        $year = AcademicYear::with('terms')->where('label', $this->argument('label'))->first();

        if (! $year) {
            $this->error("Academic year {$this->argument('label')} does not exist. Add it first.");

            return self::FAILURE;
        }

        AcademicYear::where('is_current', true)->update(['is_current' => false]);
        $year->update(['is_current' => true]);

        $count = $provisioning->openYearForAllStudents($year);
        $this->info("Opened {$count} portfolio(s).");

        foreach (Program::where('is_active', true)->get() as $program) {
            foreach ([1, 2, 3, 4] as $level) {
                $deadlines->generateDefaultSet($year, $program, $level);
            }
            $this->line("  Deadline schedules generated for {$program->code}.");
        }

        return self::SUCCESS;
    }
}
