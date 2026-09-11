<?php

namespace App\Services;

use App\Models\Portfolio;
use App\Models\SectionEntry;

/**
 * COMPLETION SCORING
 * -----------------------------------------------------------------------------
 * Turns "how much of this section is filled in?" into a percentage the student
 * can see on the dashboard. Every section type answers the question
 * differently, so the logic lives here rather than in twelve components.
 *
 * Completion is about *effort recorded*, not quality. A section can be 100%
 * complete and still be returned by an evaluator.
 */
class PortfolioCompletionService
{
    public function recomputeSection(SectionEntry $entry): int
    {
        $definition = $entry->definition();
        $portfolio = $entry->portfolio;

        $percent = match ($definition['key'] ?? '') {
            // --- Field-driven sections: share of required fields answered ----
            'student_profile', 'personal_development_plan',
            'research_investigation', 'professional_competency' => $this->fieldCompletion($entry),

            // --- Table-driven sections: each has its own definition of done ---
            'plo_competency_matrix' => $this->ratio(
                $portfolio->assessments()->where('type', 'self_assessment')->count()
                    + $portfolio->courseEvidence()->whereNotNull('claimed_level')->count(),
                5
            ),
            'course_based_evidence' => $this->ratio(
                $portfolio->courseEvidence()->count(),
                config('portfolio.minimum_evidence.technical_artifacts', 3)
            ),
            'technical_competency' => $this->ratio($portfolio->technicalCompetencies()->count(), 6),
            'engineering_design' => $this->designCompletion($portfolio),
            'industry_ojt' => $this->ojtCompletion($portfolio),
            'capstone' => $this->capstoneCompletion($portfolio),
            'professional_development' => $this->ratio($portfolio->professionalDevelopment()->count(), 2),
            'reflection' => $this->reflectionCompletion($portfolio),
            default => 0,
        };

        $entry->update(['completion_percent' => $percent]);

        return $percent;
    }

    /** Recompute every section, then roll up to the portfolio figure. */
    public function recomputePortfolio(Portfolio $portfolio): int
    {
        $portfolio->loadMissing('sectionEntries');

        $values = $portfolio->sectionEntries->map(fn ($entry) => $this->recomputeSection($entry));
        $overall = $values->isEmpty() ? 0 : (int) round($values->avg());

        $portfolio->update(['completion_percent' => $overall]);

        return $overall;
    }

    // --- Per-type helpers ----------------------------------------------------

    /** Share of the section's *required* fields that have an answer. */
    protected function fieldCompletion(SectionEntry $entry): int
    {
        $fields = collect($entry->definition()['fields'] ?? [])
            ->filter(fn ($f) => $f['required'] ?? false);

        if ($fields->isEmpty()) {
            return 100;
        }

        $answered = $fields->filter(fn ($f) => filled($entry->answer($f['name'])))->count();

        return (int) round($answered / $fields->count() * 100);
    }

    protected function designCompletion(Portfolio $portfolio): int
    {
        $project = $portfolio->projects()->where('kind', 'design_project')->first();

        return $project ? $project->designCompletion() : 0;
    }

    protected function ojtCompletion(Portfolio $portfolio): int
    {
        $ojt = $portfolio->ojtRecord;

        if (! $ojt) {
            return 0;
        }

        // Three equal parts: the record itself, the hours, the supervisor form.
        $parts = [
            filled($ojt->company_name) && filled($ojt->responsibilities) ? 100 : 40,
            $ojt->hoursProgress(),
            $ojt->supervisorEvaluation?->submitted_at ? 100 : 0,
        ];

        return (int) round(array_sum($parts) / count($parts));
    }

    protected function capstoneCompletion(Portfolio $portfolio): int
    {
        $capstone = $portfolio->capstoneRecord;

        if (! $capstone) {
            return 0;
        }

        $fields = array_keys(\App\Models\CapstoneRecord::CRITERIA_PLO_MAP);
        $filled = collect($fields)->filter(fn ($f) => filled($capstone->{$f}))->count();

        return (int) round($filled / count($fields) * 100);
    }

    /** One reflection per major project is the requirement. */
    protected function reflectionCompletion(Portfolio $portfolio): int
    {
        $projects = $portfolio->projects()->count();
        $needed = max(1, $projects);
        $complete = $portfolio->reflections->filter(fn ($r) => $r->isComplete())->count();

        return $this->ratio($complete, $needed);
    }

    protected function ratio(int $have, int $need): int
    {
        if ($need <= 0) {
            return 100;
        }

        return (int) min(100, round($have / $need * 100));
    }
}
