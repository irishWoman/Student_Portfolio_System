<?php

namespace App\Services;

use App\Models\CourseEvidenceRecord;
use App\Models\Plo;
use App\Models\PloAttainmentSnapshot;
use App\Models\Project;
use App\Models\Student;
use App\Support\Enums\AttainmentLevel;
use Illuminate\Support\Collection;

/**
 * MATRIX AND DASHBOARD ASSEMBLY
 * =============================================================================
 * Turns stored snapshots into the two grids the printed portfolio uses:
 *
 *   1. the Year 1–4 PLO and Competency Matrix (I / D / A / P per year, with
 *      representative evidence), and
 *   2. the PLO Attainment Dashboard (numeric level per year, current standing,
 *      and a flag with the reason spelled out).
 *
 * It lives apart from PloAttainmentService because that service answers "what
 * is this student's attainment?", while this one answers "how does that get
 * printed?" — different question, different rate of change. The export, the
 * on-screen matrix and the PDF all read from here, so the three can never drift
 * apart.
 * =============================================================================
 */
class PortfolioMatrixService
{
    public function __construct(protected PloAttainmentService $attainment) {}

    /**
     * The Section 3 matrix, across every year the student has on record.
     *
     * @return array<int, array{plo: Plo, years: array<int, string>, evidence: string}>
     */
    public function matrix(Student $student): array
    {
        $snapshots = $this->snapshotsByPloAndYear($student);
        $evidence = $this->representativeEvidence($student);
        $rows = [];

        foreach (Plo::orderBy('number')->get() as $plo) {
            $years = [];

            for ($year = 1; $year <= 4; $year++) {
                $years[$year] = $this->matrixCell($snapshots, $plo->id, $year, $student->year_level);
            }

            $rows[$plo->number] = [
                'plo' => $plo,
                'years' => $years,
                'evidence' => $evidence[$plo->id] ?? '—',
            ];
        }

        return $rows;
    }

    /**
     * The attainment dashboard: numeric level per year, current standing, flag.
     *
     * @return array<int, array{plo: Plo, years: array<int, string>, current: string, flag: string}>
     */
    public function dashboard(Student $student): array
    {
        $snapshots = $this->snapshotsByPloAndYear($student);
        $cumulative = $this->attainment->cumulativeFor($student);
        $rows = [];

        foreach (Plo::orderBy('number')->get() as $plo) {
            $years = [];

            for ($year = 1; $year <= 4; $year++) {
                $snapshot = $snapshots[$plo->id][$year] ?? null;

                $years[$year] = match (true) {
                    $snapshot && $snapshot->direct_score !== null => (string) (int) round((float) $snapshot->direct_score),
                    $year > $student->year_level => 'Pending',
                    default => '—',
                };
            }

            $row = $cumulative[$plo->number] ?? ['score' => null, 'evidence' => 0, 'flag' => 'under_assessed'];

            $rows[$plo->number] = [
                'plo' => $plo,
                'years' => $years,
                'current' => $this->currentLevel($row['score'], $student),
                'flag' => $this->flagText($row['flag'], $row['evidence'], $student),
            ];
        }

        return $rows;
    }

    /**
     * Section 5 / transcript view: best stage reached per competency area,
     * with the evidence that earned it and the year it was earned.
     *
     * @return Collection<int, array{area: string, level: ?string, evidence: string, year: string}>
     */
    public function competencyTranscript(Student $student): Collection
    {
        $portfolioIds = $student->portfolios()->pluck('id');

        return \App\Models\TechnicalCompetencyRecord::query()
            ->with(['competency.category', 'portfolio.academicYear'])
            ->whereIn('portfolio_id', $portfolioIds)
            ->get()
            ->groupBy(fn ($record) => $record->competency->category->name)
            ->map(function ($records, $area) {
                // The best stage the student reached in this area is what the
                // transcript reports; earlier, lower claims are superseded.
                $best = $records
                    ->filter(fn ($r) => $r->effectiveStage() !== null)
                    ->sortByDesc(fn ($r) => $r->effectiveStage()->rank())
                    ->first();

                return [
                    'area' => $area,
                    'level' => $best?->effectiveStage()?->label(),
                    'evidence' => $best?->evidence_note ?: ($best?->competency->name ?? '—'),
                    'year' => $best ? 'Y'.$best->portfolio->year_level : '—',
                ];
            })
            ->values();
    }

    // -------------------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------------------

    /** @return array<int, array<int, PloAttainmentSnapshot>> keyed [plo_id][year_level] */
    protected function snapshotsByPloAndYear(Student $student): array
    {
        $grouped = [];

        foreach (PloAttainmentSnapshot::where('student_id', $student->id)->get() as $snapshot) {
            // If a student repeats a year level, the later snapshot wins.
            $existing = $grouped[$snapshot->plo_id][$snapshot->year_level] ?? null;

            if (! $existing || $snapshot->computed_at?->greaterThan($existing->computed_at)) {
                $grouped[$snapshot->plo_id][$snapshot->year_level] = $snapshot;
            }
        }

        return $grouped;
    }

    /**
     * One matrix cell, in the printed form: "A (Y3)" where evidence exists,
     * "Pending" for years not yet reached, an em dash where the outcome was not
     * targeted that year.
     */
    protected function matrixCell(array $snapshots, int $ploId, int $year, int $studentYear): string
    {
        $snapshot = $snapshots[$ploId][$year] ?? null;

        if ($snapshot && $snapshot->direct_score !== null) {
            $level = AttainmentLevel::tryFrom((int) round((float) $snapshot->direct_score));

            return $level ? $level->matrixCell($year) : '—';
        }

        return $year > $studentYear ? 'Pending' : '—';
    }

    /**
     * Representative evidence per PLO: the highest-rated validated output the
     * student offered for it, which is what an assessor wants to see named in
     * the matrix rather than a count.
     *
     * @return array<int, string> keyed by plo_id
     */
    protected function representativeEvidence(Student $student): array
    {
        $portfolioIds = $student->portfolios()->pluck('id');
        $evidence = [];

        $records = CourseEvidenceRecord::with(['plos', 'course'])
            ->whereIn('portfolio_id', $portfolioIds)
            ->orderByDesc('validated_level')
            ->get();

        foreach ($records as $record) {
            foreach ($record->plos as $plo) {
                if (count($evidence[$plo->id] ?? []) >= 2) {
                    continue;
                }

                $evidence[$plo->id][] = trim(($record->course?->code ? $record->course->code.' ' : '').$record->output_title);
            }
        }

        // Outcomes with no course row fall back to the design project or
        // capstone, which is usually where the softer PLOs (teamwork, ethics,
        // sustainability) are actually demonstrated.
        $projects = Project::whereIn('portfolio_id', $portfolioIds)
            ->whereIn('kind', ['design_project', 'capstone'])
            ->pluck('title');

        if ($projects->isNotEmpty()) {
            foreach (Plo::pluck('id') as $ploId) {
                if (! isset($evidence[$ploId])) {
                    $evidence[$ploId] = [$projects->first()];
                }
            }
        }

        return collect($evidence)->map(fn ($titles) => implode('; ', $titles))->all();
    }

    /** "3 — Proficient (in progress)" */
    protected function currentLevel(?float $score, Student $student): string
    {
        if ($score === null) {
            return 'No data';
        }

        $level = AttainmentLevel::tryFrom((int) round($score));
        $text = (int) round($score).' — '.($level?->label() ?? '—');

        return $student->standing === 'graduated' ? $text : $text.' (in progress)';
    }

    /** Flag text with the reason attached, as the printed dashboard shows it. */
    protected function flagText(string $flag, int $evidenceCount, Student $student): string
    {
        $year = 'Y'.$student->year_level;

        return match ($flag) {
            'on_track' => 'On track',
            'watch' => $evidenceCount <= 1
                ? 'Watch — single-source validation in '.$year
                : 'Watch — below expected level for '.$year,
            'at_risk' => 'Needs intervention — evidence below expected level',
            default => 'Under-assessed — needs more '.$year.' evidence',
        };
    }
}
