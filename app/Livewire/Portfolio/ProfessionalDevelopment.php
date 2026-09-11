<?php

namespace App\Livewire\Portfolio;

use App\Models\Portfolio;
use App\Models\ProfessionalDevelopmentRecord;
use App\Services\PortfolioCompletionService;
use Livewire\Component;

/**
 * SECTION 11 — PROFESSIONAL DEVELOPMENT
 * -----------------------------------------------------------------------------
 * Seminars, competitions, certifications. The specification is explicit that a
 * certificate on its own earns no attainment, so `competency_demonstrated` is
 * required and is what the evaluator rates. Attendance alone gets recorded but
 * scores nothing.
 */
class ProfessionalDevelopment extends Component
{
    public Portfolio $portfolio;

    public bool $canEdit = true;

    public ?int $editingId = null;
    public string $kind = 'seminar';
    public string $title = '';
    public string $organizer = '';
    public ?string $heldOn = null;
    public ?int $hours = null;
    public string $competencyDemonstrated = '';
    public ?int $claimedLevel = null;

    public function mount(Portfolio $portfolio, bool $canEdit = true): void
    {
        $this->portfolio = $portfolio;
        $this->canEdit = $canEdit;
    }

    public function save(): void
    {
        if (! $this->canEdit) {
            return;
        }

        $data = $this->validate([
            'kind' => ['required', 'string'],
            'title' => ['required', 'string', 'max:200'],
            'organizer' => ['nullable', 'string', 'max:180'],
            'heldOn' => ['nullable', 'date'],
            'hours' => ['nullable', 'integer', 'between:1,500'],
            'competencyDemonstrated' => ['required', 'string', 'min:25'],
            'claimedLevel' => ['nullable', 'integer', 'between:1,4'],
        ], [
            'competencyDemonstrated.required' => 'Describe what you can now do because of this. A certificate on its own earns no attainment.',
            'competencyDemonstrated.min' => 'Say a little more about what you learned to do.',
        ]);

        ProfessionalDevelopmentRecord::updateOrCreate(
            ['id' => $this->editingId],
            [
                'portfolio_id' => $this->portfolio->id,
                'kind' => $data['kind'],
                'title' => $data['title'],
                'organizer' => $data['organizer'],
                'held_on' => $data['heldOn'],
                'hours' => $data['hours'],
                'competency_demonstrated' => $data['competencyDemonstrated'],
                'claimed_level' => $data['claimedLevel'],
            ]
        );

        app(PortfolioCompletionService::class)->recomputeSection($this->portfolio->entryFor(11));

        $this->reset(['editingId', 'title', 'organizer', 'heldOn', 'hours', 'competencyDemonstrated', 'claimedLevel']);
        $this->dispatch('section-saved', section: 11);
    }

    public function edit(int $id): void
    {
        $record = ProfessionalDevelopmentRecord::findOrFail($id);
        abort_unless($record->portfolio_id === $this->portfolio->id, 403);

        $this->editingId = $record->id;
        $this->kind = $record->kind;
        $this->title = $record->title;
        $this->organizer = (string) $record->organizer;
        $this->heldOn = $record->held_on?->toDateString();
        $this->hours = $record->hours;
        $this->competencyDemonstrated = $record->competency_demonstrated;
        $this->claimedLevel = $record->claimed_level;
    }

    public function delete(int $id): void
    {
        $record = ProfessionalDevelopmentRecord::findOrFail($id);
        abort_unless($record->portfolio_id === $this->portfolio->id, 403);
        $record->delete();
    }

    public function render()
    {
        return view('livewire.portfolio.professional-development', [
            'records' => $this->portfolio->professionalDevelopment()->latest('held_on')->get(),
        ]);
    }
}
