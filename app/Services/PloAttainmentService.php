<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Plo;
use App\Models\PloAttainmentSnapshot;
use App\Models\Portfolio;
use App\Models\Student;
use App\Support\Enums\AssessmentType;
use Illuminate\Support\Collection;

/**
 * PLO ATTAINMENT COMPUTATION
 * =============================================================================
 * The rule the whole system is judged on (specification Section VII):
 *
 *     PLO attainment = weighted mean of VALID DIRECT assessment evidence
 *
 * "Valid" means all three of:
 *   1. the score was awarded by a faculty evaluator, not self-claimed
 *   2. the evaluator validated it
 *   3. the supporting evidence was rated at or above the minimum quality
 *
 * Weights come from config('portfolio.assessment.weights'): a capstone or OJT
 * artifact counts double an ordinary course output, because it demonstrates
 * integrated rather than isolated competence.
 *
 * Indirect evidence (self-assessment, exit survey, employer and alumni
 * feedback) is computed separately and reported beside the direct figure. It
 * is never averaged into it. Mixing the two is the most common way an OBE
 * dashboard ends up overstating attainment.
 * =============================================================================
 */
class PloAttainmentService
{
    /**
     * Recompute and store every PLO snapshot for one portfolio.
     *
     * @return Collection<int, PloAttainmentSnapshot> keyed by PLO number
     */
    public function recomputeForPortfolio(Portfolio $portfolio): Collection
    {
        $portfolio->loadMissing([
            'assessments.ploScores',
            'assessments.assessable',
            'courseEvidence.plos',
            'evidenceFiles.plos',
        ]);

        return Plo::orderBy('number')->get()->mapWithKeys(function (Plo $plo) use ($portfolio) {
            $direct = $this->directScoreFor($portfolio, $plo);
            $indirect = $this->indirectScoreFor($portfolio, $plo);

            $snapshot = PloAttainmentSnapshot::updateOrCreate(
                [
                    'student_id' => $portfolio->student_id,
                    'academic_year_id' => $portfolio->academic_year_id,
                    'plo_id' => $plo->id,
                ],
                [
                    'year_level' => $portfolio->year_level,
                    'direct_score' => $direct['score'],
                    'indirect_score' => $indirect,
                    'evidence_count' => $direct['total'],
                    'valid_evidence_count' => $direct['valid'],
                    'flag' => $this->flag($direct['score'], $direct['valid'], $portfolio->year_level),
                    'computed_at' => now(),
                ]
            );

            return [$plo->number => $snapshot];
        });
    }

    /**
     * Weighted mean of valid direct evidence for one PLO.
     *
     * @return array{score: ?float, valid: int, total: int}
     */
    public function directScoreFor(Portfolio $portfolio, Plo $plo): array
    {
        $weightedSum = 0.0;
        $weightTotal = 0.0;
        $total = 0;
        $valid = 0;

        // --- Source 1: assessed artifacts (assessment_plo_scores) ------------
        foreach ($portfolio->assessments as $assessment) {
            $type = $assessment->type instanceof AssessmentType
                ? $assessment->type
                : AssessmentType::tryFrom((string) $assessment->type);

            if (! $type || ! $type->isDirect()) {
                continue;
            }

            foreach ($assessment->ploScores as $score) {
                if ($score->plo_id !== $plo->id) {
                    continue;
                }

                $total++;

                // Self-claims and unvalidated rows are excluded by design.
                if ($score->is_self_assessment || ! $score->is_validated) {
                    continue;
                }

                // Evidence quality gate: the artifact backing this score must be
                // rated adequate or strong. Artifacts with no attached file are
                // allowed through only when the evaluator scored them directly.
                if (! $this->evidenceQualityPasses($portfolio, $assessment->assessable_type, $assessment->assessable_id)) {
                    continue;
                }

                $weight = $type->weight();
                $weightedSum += $score->level * $weight;
                $weightTotal += $weight;
                $valid++;
            }
        }

        // --- Source 2: Section 4 course-evidence rows validated by faculty ----
        foreach ($portfolio->courseEvidence as $record) {
            if (! $record->plos->contains('id', $plo->id)) {
                continue;
            }

            $total++;

            if ($record->validated_level === null) {
                continue;
            }

            $weight = AssessmentType::Course->weight();
            $weightedSum += $record->validated_level * $weight;
            $weightTotal += $weight;
            $valid++;
        }

        return [
            'score' => $weightTotal > 0 ? round($weightedSum / $weightTotal, 2) : null,
            'valid' => $valid,
            'total' => $total,
        ];
    }

    /** Mean of indirect evidence, reported separately from the direct figure. */
    public function indirectScoreFor(Portfolio $portfolio, Plo $plo): ?float
    {
        $mean = $portfolio->indirectAssessments()
            ->where('plo_id', $plo->id)
            ->avg('level');

        return $mean ? round((float) $mean, 2) : null;
    }

    /**
     * Cumulative attainment across every year the student has completed.
     * This is the figure that appears on the Graduate Competency Profile.
     *
     * @return array<int, array{score: ?float, evidence: int, flag: string}>
     */
    public function cumulativeFor(Student $student): array
    {
        $snapshots = PloAttainmentSnapshot::where('student_id', $student->id)
            ->with('plo')
            ->get()
            ->groupBy('plo_id');

        $result = [];

        foreach (Plo::orderBy('number')->get() as $plo) {
            $rows = $snapshots->get($plo->id, collect())->filter(fn ($s) => $s->direct_score !== null);

            // Later years carry more weight: a Year 4 demonstration says more
            // about a graduating student than a Year 1 one. Weight = year level.
            $weighted = $rows->sum(fn ($s) => (float) $s->direct_score * $s->year_level);
            $weights = $rows->sum(fn ($s) => $s->year_level);
            $evidence = $rows->sum('valid_evidence_count');

            $score = $weights > 0 ? round($weighted / $weights, 2) : null;

            $result[$plo->number] = [
                'score' => $score,
                'evidence' => $evidence,
                'flag' => $this->flag($score, $evidence, $student->year_level),
            ];
        }

        return $result;
    }

    /**
     * Cohort roll-up for the chair's dashboard: mean per PLO per year level,
     * plus the share of students at or above target.
     */
    public function cohortSummary(AcademicYear $year, ?int $yearLevel = null): array
    {
        $query = PloAttainmentSnapshot::where('academic_year_id', $year->id)
            ->whereNotNull('direct_score');

        if ($yearLevel) {
            $query->where('year_level', $yearLevel);
        }

        $target = (float) config('portfolio.assessment.target', 3.0);
        $rows = $query->get()->groupBy('plo_id');
        $summary = [];

        foreach (Plo::orderBy('number')->get() as $plo) {
            $group = $rows->get($plo->id, collect());
            $count = $group->count();
            $atTarget = $group->filter(fn ($s) => (float) $s->direct_score >= $target)->count();

            $summary[$plo->number] = [
                'plo' => $plo,
                'mean' => $count ? round($group->avg('direct_score'), 2) : null,
                'students' => $count,
                'at_target' => $atTarget,
                'at_target_percent' => $count ? (int) round($atTarget / $count * 100) : 0,
                'meets_target' => $count ? round($group->avg('direct_score'), 2) >= $target : false,
            ];
        }

        return $summary;
    }

    // -------------------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------------------

    /**
     * True when the artifact behind a score has at least one piece of evidence
     * rated at or above the minimum quality, or has no attached file at all
     * (in which case the evaluator's direct observation is the evidence).
     */
    protected function evidenceQualityPasses(Portfolio $portfolio, ?string $type, ?int $id): bool
    {
        if (! $type || ! $id) {
            return true;
        }

        $files = $portfolio->evidenceFiles
            ->where('attachable_type', $type)
            ->where('attachable_id', $id);

        if ($files->isEmpty()) {
            return true;
        }

        return $files->contains(fn ($file) => $file->countsTowardAttainment());
    }

    /**
     * Traffic light for one PLO.
     *
     *   under_assessed - not enough valid evidence to judge either way
     *   on_track       - at or above the expected level for the year
     *   watch          - within one level of expectation
     *   at_risk        - more than a level below expectation
     */
    protected function flag(?float $score, int $validCount, int $yearLevel): string
    {
        $minEvidence = (int) config('portfolio.assessment.min_evidence_count', 2);

        if ($score === null || $validCount < $minEvidence) {
            return 'under_assessed';
        }

        $expected = min(4, max(1, $yearLevel));  // Y1 expects 1, Y4 expects 4
        $target = min((float) config('portfolio.assessment.target', 3.0), (float) $expected);

        return match (true) {
            $score >= $target => 'on_track',
            $score >= $target - 1 => 'watch',
            default => 'at_risk',
        };
    }
}
