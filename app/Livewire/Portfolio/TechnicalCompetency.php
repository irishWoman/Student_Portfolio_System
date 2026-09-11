<?php

namespace App\Livewire\Portfolio;

use App\Models\CompetencyCategory;
use App\Models\Portfolio;
use App\Models\TechnicalCompetencyRecord;
use App\Services\PortfolioCompletionService;
use App\Support\Enums\CompetencyStage;
use Livewire\Component;

/**
 * SECTION 5 — TECHNICAL COMPETENCY PORTFOLIO
 * -----------------------------------------------------------------------------
 * A grid of every competency in the catalogue, grouped by category. The student
 * sets a stage and writes one line of evidence. Competencies left untouched are
 * simply not claimed, which is a valid answer in first and second year.
 */
class TechnicalCompetency extends Component
{
    public Portfolio $portfolio;

    public bool $canEdit = true;

    /** competency_id => stage value */
    public array $stages = [];

    /** competency_id => evidence note */
    public array $notes = [];

    public function mount(Portfolio $portfolio, bool $canEdit = true): void
    {
        $this->portfolio = $portfolio;
        $this->canEdit = $canEdit;

        foreach ($portfolio->technicalCompetencies as $record) {
            $this->stages[$record->competency_id] = $record->claimed_stage?->value;
            $this->notes[$record->competency_id] = $record->evidence_note;
        }
    }

    public function save(): void
    {
        if (! $this->canEdit) {
            return;
        }

        foreach ($this->stages as $competencyId => $stage) {
            if (! $stage) {
                continue;
            }

            TechnicalCompetencyRecord::updateOrCreate(
                ['portfolio_id' => $this->portfolio->id, 'competency_id' => $competencyId],
                ['claimed_stage' => $stage, 'evidence_note' => $this->notes[$competencyId] ?? null]
            );
        }

        app(PortfolioCompletionService::class)->recomputeSection($this->portfolio->entryFor(5));

        $this->dispatch('section-saved', section: 5);
    }

    public function render()
    {
        return view('livewire.portfolio.technical-competency', [
            'categories' => CompetencyCategory::with('competencies')->orderBy('sort_order')->get(),
            'stageOptions' => CompetencyStage::cases(),
            'validated' => $this->portfolio->technicalCompetencies->keyBy('competency_id'),
        ]);
    }
}
