<?php

namespace App\Livewire\Portfolio;

use App\Models\SectionEntry;
use App\Services\PortfolioCompletionService;
use Livewire\Component;

/**
 * GENERIC SECTION EDITOR
 * -----------------------------------------------------------------------------
 * Renders any section whose definition in config/portfolio.php uses
 * `editor => 'fields'` (sections 1, 2, 7 and 8). The form is built from the
 * config array, so the department can add or reword a question without a
 * migration, a new component or a new view.
 *
 * Answers autosave into section_entries.payload as the student types
 * (debounced), because portfolio work happens in long sittings and losing a
 * page of writing to a closed tab is the fastest way to lose their trust.
 */
class SectionForm extends Component
{
    public SectionEntry $entry;

    public bool $canEdit = true;

    /** field name => answer */
    public array $answers = [];

    public ?string $savedAt = null;

    public function mount(SectionEntry $entry, bool $canEdit = true): void
    {
        $this->entry = $entry;
        $this->canEdit = $canEdit;

        foreach ($this->fields() as $field) {
            $this->answers[$field['name']] = $entry->answer($field['name'], '');
        }
    }

    public function fields(): array
    {
        return $this->entry->definition()['fields'] ?? [];
    }

    /** Autosave: fires on every debounced change from the view. */
    public function updatedAnswers(): void
    {
        $this->save(silent: true);
    }

    public function save(bool $silent = false): void
    {
        if (! $this->canEdit) {
            return;
        }

        $this->entry->update(['payload' => $this->answers]);

        app(PortfolioCompletionService::class)->recomputeSection($this->entry->fresh());

        $this->savedAt = now()->format('g:i a');

        if (! $silent) {
            $this->dispatch('section-saved', section: $this->entry->section_number);
        }
    }

    /**
     * Mark this one section ready for review. Required answers must be present:
     * an evaluator's time is worth more than a half-filled form.
     */
    public function submitSection(): void
    {
        $missing = collect($this->fields())
            ->filter(fn ($f) => ($f['required'] ?? false) && blank($this->answers[$f['name']] ?? null))
            ->pluck('label');

        if ($missing->isNotEmpty()) {
            $this->addError('answers', 'Still needed: '.$missing->implode(', '));

            return;
        }

        $this->save(silent: true);
        $this->entry->update(['status' => 'submitted', 'submitted_at' => now()]);
        $this->dispatch('section-submitted');
    }

    public function render()
    {
        return view('livewire.portfolio.section-form');
    }
}
