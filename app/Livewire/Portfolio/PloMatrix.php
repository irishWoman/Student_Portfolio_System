<?php

namespace App\Livewire\Portfolio;

use App\Models\Assessment;
use App\Models\AssessmentPloScore;
use App\Models\Plo;
use App\Models\Portfolio;
use App\Support\Enums\AssessmentType;
use App\Support\Enums\AttainmentLevel;
use Livewire\Component;

/**
 * SECTION 3 — PLO AND COMPETENCY MATRIX
 * -----------------------------------------------------------------------------
 * The student claims a level for each of the 14 PLOs this year and names the
 * evidence behind the claim. The claim is stored as a self-assessment, which
 * the specification treats as INDIRECT evidence: it never counts toward
 * attainment on its own. The evaluator's validated level is what counts.
 *
 * Showing the student both columns side by side is intentional. Seeing your
 * own claim next to your evaluator's judgement is where the honest
 * self-assessment habit actually forms.
 */
class PloMatrix extends Component
{
    public Portfolio $portfolio;

    public bool $canEdit = true;

    /** plo_id => level (1-4) */
    public array $claims = [];

    /** plo_id => short evidence note */
    public array $notes = [];

    public function mount(Portfolio $portfolio, bool $canEdit = true): void
    {
        $this->portfolio = $portfolio;
        $this->canEdit = $canEdit;

        // Load any claim already made this year.
        $assessment = $this->selfAssessment();

        foreach (Plo::orderBy('number')->get() as $plo) {
            $score = $assessment?->ploScores->firstWhere('plo_id', $plo->id);
            $this->claims[$plo->id] = $score?->level;
            $this->notes[$plo->id] = $score?->validator_note;
        }
    }

    /** One self-assessment record per portfolio holds all 14 claims. */
    protected function selfAssessment(): ?Assessment
    {
        return $this->portfolio->assessments()
            ->with('ploScores')
            ->where('type', AssessmentType::SelfAssessment->value)
            ->first();
    }

    public function save(): void
    {
        if (! $this->canEdit) {
            return;
        }

        $assessment = $this->portfolio->assessments()->firstOrCreate(
            ['type' => AssessmentType::SelfAssessment->value],
            ['title' => 'Student self-assessment of PLOs', 'assessed_on' => now()]
        );

        foreach ($this->claims as $ploId => $level) {
            if (! $level) {
                continue;
            }

            AssessmentPloScore::updateOrCreate(
                ['assessment_id' => $assessment->id, 'plo_id' => $ploId, 'is_self_assessment' => true],
                ['level' => (int) $level, 'is_validated' => false, 'validator_note' => $this->notes[$ploId] ?? null]
            );
        }

        $this->dispatch('section-saved', section: 3);
    }

    /** The evaluator's validated level per PLO, for the comparison column. */
    public function validatedLevels(): array
    {
        return AssessmentPloScore::query()
            ->whereHas('assessment', fn ($q) => $q->where('portfolio_id', $this->portfolio->id))
            ->countable()
            ->get()
            ->groupBy('plo_id')
            ->map(fn ($rows) => round($rows->avg('level'), 2))
            ->all();
    }

    public function render()
    {
        return view('livewire.portfolio.plo-matrix', [
            'plos' => Plo::orderBy('number')->get(),
            'levels' => AttainmentLevel::cases(),
            'validated' => $this->validatedLevels(),
            'expected' => AttainmentLevel::expectedForYear($this->portfolio->year_level),
        ]);
    }
}
