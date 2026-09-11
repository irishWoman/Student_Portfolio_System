<?php

namespace App\Livewire\Portfolio;

use App\Models\CapstoneRecord;
use App\Models\Portfolio;
use App\Services\PortfolioCompletionService;
use Livewire\Component;

/**
 * SECTION 10 — CAPSTONE PROJECT PORTFOLIO
 * -----------------------------------------------------------------------------
 * CpE Practice and Design 1 and 2. Every field on this form maps to a PLO
 * through CapstoneRecord::CRITERIA_PLO_MAP, and that mapping is shown next to
 * each field so the student can see which outcome they are evidencing as they
 * write. Making the mapping visible is the cheapest way to make it meaningful.
 */
class CapstoneForm extends Component
{
    public Portfolio $portfolio;

    public bool $canEdit = true;

    public string $title = '';
    public ?string $adviserName = null;
    public ?string $proposalDefendedOn = null;
    public ?string $finalDefendedOn = null;

    /** field => content, keyed by CapstoneRecord::CRITERIA_PLO_MAP */
    public array $fields = [];

    public ?string $savedAt = null;

    public function mount(Portfolio $portfolio, bool $canEdit = true): void
    {
        $this->portfolio = $portfolio;
        $this->canEdit = $canEdit;

        $record = $portfolio->capstoneRecord;

        foreach (array_keys(CapstoneRecord::CRITERIA_PLO_MAP) as $field) {
            $this->fields[$field] = $record?->{$field} ?? '';
        }

        if ($record) {
            $this->title = $record->title;
            $this->adviserName = $record->adviser_name;
            $this->proposalDefendedOn = $record->proposal_defended_on?->toDateString();
            $this->finalDefendedOn = $record->final_defended_on?->toDateString();
        }
    }

    public function save(): void
    {
        if (! $this->canEdit) {
            return;
        }

        $this->validate([
            'title' => ['required', 'string', 'max:250'],
            'adviserName' => ['nullable', 'string', 'max:180'],
            'proposalDefendedOn' => ['nullable', 'date'],
            'finalDefendedOn' => ['nullable', 'date', 'after_or_equal:proposalDefendedOn'],
        ]);

        CapstoneRecord::updateOrCreate(
            ['portfolio_id' => $this->portfolio->id],
            array_merge($this->fields, [
                'title' => $this->title,
                'adviser_name' => $this->adviserName,
                'proposal_defended_on' => $this->proposalDefendedOn,
                'final_defended_on' => $this->finalDefendedOn,
            ])
        );

        app(PortfolioCompletionService::class)->recomputeSection($this->portfolio->entryFor(10));

        $this->savedAt = now()->format('g:i a');
        $this->dispatch('section-saved', section: 10);
    }

    public function render()
    {
        return view('livewire.portfolio.capstone', [
            'map' => CapstoneRecord::CRITERIA_PLO_MAP,
            'labels' => [
                'problem_definition' => 'Problem definition',
                'literature_review' => 'Literature review',
                'requirements' => 'Requirements',
                'system_architecture' => 'System architecture',
                'design' => 'Design',
                'implementation' => 'Implementation',
                'testing' => 'Testing',
                'validation' => 'Validation',
                'cost_analysis' => 'Cost analysis',
                'risk_assessment' => 'Risk assessment',
                'ethics' => 'Ethics',
                'sustainability' => 'Sustainability',
                'documentation_note' => 'Technical documentation',
            ],
        ]);
    }
}
