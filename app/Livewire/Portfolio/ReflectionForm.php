<?php

namespace App\Livewire\Portfolio;

use App\Models\Portfolio;
use App\Models\Project;
use App\Models\Reflection;
use App\Services\PortfolioCompletionService;
use Livewire\Component;

/**
 * SECTION 12 — STUDENT REFLECTION
 * -----------------------------------------------------------------------------
 * The ten prompts, answered once per major project. Prompts come from config so
 * the wording matches the printed portfolio exactly.
 */
class ReflectionForm extends Component
{
    public Portfolio $portfolio;

    public bool $canEdit = true;

    public ?int $reflectionId = null;
    public ?int $projectId = null;
    public string $subject = '';
    public array $answers = [];

    public function mount(Portfolio $portfolio, bool $canEdit = true): void
    {
        $this->portfolio = $portfolio;
        $this->canEdit = $canEdit;
        $this->resetAnswers();
    }

    protected function resetAnswers(): void
    {
        foreach (array_keys(config('portfolio.reflection_prompts')) as $key) {
            $this->answers[$key] = '';
        }
    }

    public function edit(int $id): void
    {
        $reflection = Reflection::findOrFail($id);
        abort_unless($reflection->portfolio_id === $this->portfolio->id, 403);

        $this->reflectionId = $reflection->id;
        $this->subject = $reflection->subject;
        $this->projectId = $reflection->reflectable_type === Project::class ? $reflection->reflectable_id : null;

        foreach (array_keys(config('portfolio.reflection_prompts')) as $key) {
            $this->answers[$key] = $reflection->answer($key) ?? '';
        }
    }

    public function save(): void
    {
        if (! $this->canEdit) {
            return;
        }

        $this->validate([
            'subject' => ['required', 'string', 'max:200'],
            'answers.learned' => ['required', 'string', 'min:20'],
            'answers.problem_solved' => ['required', 'string', 'min:20'],
            'answers.plos_addressed' => ['required', 'string'],
        ], [
            'answers.learned.min' => 'Give the first answer a bit more substance — a sentence or two at least.',
            'answers.problem_solved.min' => 'Describe the problem in your own words.',
        ]);

        $reflection = Reflection::updateOrCreate(
            ['id' => $this->reflectionId],
            [
                'portfolio_id' => $this->portfolio->id,
                'reflectable_type' => $this->projectId ? Project::class : null,
                'reflectable_id' => $this->projectId,
                'subject' => $this->subject,
                'answers' => $this->answers,
                'completed_at' => now(),
            ]
        );

        $this->reflectionId = $reflection->id;

        app(PortfolioCompletionService::class)->recomputeSection($this->portfolio->entryFor(12));

        $this->dispatch('section-saved', section: 12);
    }

    public function startNew(): void
    {
        $this->reset(['reflectionId', 'projectId', 'subject']);
        $this->resetAnswers();
    }

    public function render()
    {
        return view('livewire.portfolio.reflection-form', [
            'prompts' => config('portfolio.reflection_prompts'),
            'existing' => $this->portfolio->reflections()->latest('id')->get(),
            'projects' => $this->portfolio->projects()->get(),
        ]);
    }
}
