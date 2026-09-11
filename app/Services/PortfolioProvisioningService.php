<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Portfolio;
use App\Models\SectionEntry;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

/**
 * PORTFOLIO PROVISIONING
 * -----------------------------------------------------------------------------
 * Creates the yearly portfolio shell for a student: the portfolio row plus one
 * section entry for every section required at their year level.
 *
 * Called when a student first opens the portfolio for the current academic
 * year, and by `php artisan portfolio:open-year` at the start of each AY.
 * It is idempotent, so running it twice is harmless.
 */
class PortfolioProvisioningService
{
    public function ensureFor(Student $student, AcademicYear $year, ?int $yearLevel = null): Portfolio
    {
        return DB::transaction(function () use ($student, $year, $yearLevel) {
            $portfolio = Portfolio::firstOrCreate(
                ['student_id' => $student->id, 'academic_year_id' => $year->id],
                ['year_level' => $yearLevel ?? $student->year_level]
            );

            $this->syncSectionEntries($portfolio);

            return $portfolio->fresh('sectionEntries');
        });
    }

    /**
     * Add any section the year level requires but the portfolio does not have
     * yet. Existing entries are never touched, so a student who is promoted
     * mid-cycle keeps everything already written.
     */
    public function syncSectionEntries(Portfolio $portfolio): void
    {
        foreach (config('portfolio.sections') as $number => $definition) {
            if (! in_array($portfolio->year_level, $definition['years'], true)) {
                continue;
            }

            SectionEntry::firstOrCreate(
                ['portfolio_id' => $portfolio->id, 'section_number' => $number],
                ['section_key' => $definition['key'], 'payload' => []]
            );
        }
    }

    /** Open the new academic year for every active student in one call. */
    public function openYearForAllStudents(AcademicYear $year): int
    {
        $count = 0;

        Student::where('standing', 'active')->chunkById(200, function ($students) use ($year, &$count) {
            foreach ($students as $student) {
                $this->ensureFor($student, $year);
                $count++;
            }
        });

        return $count;
    }
}
