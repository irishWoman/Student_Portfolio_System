<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\CqiAction;
use App\Models\CurriculumCourse;
use App\Models\Plo;
use App\Models\Program;
use Illuminate\Support\Collection;

/**
 * CONTINUOUS QUALITY IMPROVEMENT
 * -----------------------------------------------------------------------------
 * Specification Section XI. For every PLO below target the department has to
 * answer nine questions. This service answers the first four automatically
 * from data already in the system:
 *
 *   1. what the gap is            -> cohort mean vs target
 *   2. where it is taught         -> courses whose CLOs map to the PLO
 *   3. where it is assessed       -> assessments that produced scores for it
 *   4. whether evidence exists    -> valid evidence count
 *
 * The remaining five (cause, intervention, owner, target date, reassessment)
 * are judgement calls, so the chair writes those into the generated draft.
 */
class CqiService
{
    public function __construct(protected PloAttainmentService $attainment) {}

    /**
     * Draft a CQI row for every PLO below target. Existing open actions are
     * updated rather than duplicated, so the table stays one row per gap.
     */
    public function generateDrafts(Program $program, AcademicYear $year): Collection
    {
        $target = (float) config('portfolio.assessment.target', 3.0);
        $summary = $this->attainment->cohortSummary($year);
        $created = collect();

        foreach ($summary as $number => $row) {
            $mean = $row['mean'];

            // Above target with enough students behind it: nothing to fix.
            if ($mean !== null && $mean >= $target && $row['students'] > 0) {
                continue;
            }

            $plo = $row['plo'];

            $action = CqiAction::updateOrCreate(
                [
                    'program_id' => $program->id,
                    'academic_year_id' => $year->id,
                    'plo_id' => $plo->id,
                    'status' => 'open',
                ],
                [
                    'gap_description' => $this->describeGap($plo, $mean, $row, $target),
                    'evidence_summary' => $this->describeEvidence($row),
                    'possible_cause' => $this->suggestCause($mean, $row),
                    'baseline_score' => $mean,
                ]
            );

            $created->push($action);
        }

        return $created;
    }

    /** Courses whose CLOs map to this PLO: where the competency is taught. */
    public function coursesTeaching(Plo $plo): Collection
    {
        return CurriculumCourse::whereHas('learningOutcomes.plos', fn ($q) => $q->where('plos.id', $plo->id))
            ->orderBy('year_level')
            ->get();
    }

    // --- Draft text generation ----------------------------------------------

    protected function describeGap(Plo $plo, ?float $mean, array $row, float $target): string
    {
        if ($mean === null) {
            return $plo->code().' ('.$plo->title.') has no valid direct evidence this year, so attainment cannot be reported.';
        }

        return sprintf(
            '%s (%s) attained a cohort mean of %.2f against a target of %.2f. %d of %d students reached target.',
            $plo->code(), $plo->title, $mean, $target, $row['at_target'], $row['students']
        );
    }

    protected function describeEvidence(array $row): string
    {
        if ($row['students'] === 0) {
            return 'No student produced valid direct evidence for this PLO in this academic year.';
        }

        return sprintf(
            '%d students contributed valid direct evidence; %d%% reached the target level.',
            $row['students'], $row['at_target_percent']
        );
    }

    /**
     * A first guess at the cause, phrased as a prompt rather than a verdict.
     * The chair replaces it after looking at the courses.
     */
    protected function suggestCause(?float $mean, array $row): string
    {
        return match (true) {
            $mean === null => 'Likely an assessment-coverage problem rather than a learning problem: no course is currently producing evidence mapped to this PLO. Check the CLO-to-PLO mapping first.',
            $row['students'] < 5 => 'Evidence base is thin. Confirm whether the competency is assessed in enough courses before treating this as a learning gap.',
            $row['at_target_percent'] < 50 => 'Majority of students below target. Review where the competency is taught and whether the assessment activities require the student to demonstrate it independently.',
            default => 'A minority of students fall below target. Consider targeted intervention rather than curriculum change.',
        };
    }
}
