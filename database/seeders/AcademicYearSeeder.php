<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Program;
use App\Models\Term;
use App\Services\DeadlineService;
use Illuminate\Database\Seeder;

/**
 * Academic calendar. Seeds the year before the current one (so there is history
 * to look at) and the current one, then generates the default deadline
 * schedules for all four year levels.
 *
 * `portfolio_due_on` is set two weeks before the year ends: the portfolio is
 * meant to close before the academic year does, with room left for evaluators
 * to return work that needs fixing.
 */
class AcademicYearSeeder extends Seeder
{
    public function run(): void
    {
        // Four years, so a graduating student's matrix has real Year 1-4 columns
        // rather than placeholder dashes.
        $years = [
            ['label' => '2023-2024', 'start' => '2023-08-14', 'end' => '2024-06-30', 'current' => false],
            ['label' => '2024-2025', 'start' => '2024-08-12', 'end' => '2025-06-30', 'current' => false],
            ['label' => '2025-2026', 'start' => '2025-08-11', 'end' => '2026-06-30', 'current' => false],
            ['label' => '2026-2027', 'start' => '2026-08-10', 'end' => '2027-06-30', 'current' => true],
        ];

        foreach ($years as $data) {
            $year = AcademicYear::updateOrCreate(
                ['label' => $data['label']],
                [
                    'starts_on' => $data['start'],
                    'ends_on' => $data['end'],
                    'portfolio_due_on' => \Illuminate\Support\Carbon::parse($data['end'])->subWeeks(2),
                    'is_current' => $data['current'],
                ]
            );

            $start = \Illuminate\Support\Carbon::parse($data['start']);

            // Two semesters plus summer, following the university's calendar shape.
            $terms = [
                ['kind' => 'first_semester', 'start' => $start, 'end' => $start->copy()->addMonths(4)->endOfMonth()],
                ['kind' => 'second_semester', 'start' => $start->copy()->addMonths(5), 'end' => $start->copy()->addMonths(9)->endOfMonth()],
                ['kind' => 'summer', 'start' => $start->copy()->addMonths(10), 'end' => \Illuminate\Support\Carbon::parse($data['end'])],
            ];

            foreach ($terms as $index => $term) {
                Term::updateOrCreate(
                    ['academic_year_id' => $year->id, 'kind' => $term['kind']],
                    [
                        'starts_on' => $term['start'],
                        'ends_on' => $term['end'],
                        'is_current' => $data['current'] && $index === 0,
                    ]
                );
            }
        }

        // Deadline schedules for the current year.
        $current = AcademicYear::where('is_current', true)->with('terms')->first();
        $program = Program::where('code', 'BSCPE')->first();

        if ($current && $program) {
            $deadlines = app(DeadlineService::class);

            foreach ([1, 2, 3, 4] as $level) {
                $deadlines->generateDefaultSet($current, $program, $level);
            }
        }
    }
}
