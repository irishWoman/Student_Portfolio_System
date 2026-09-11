<?php

namespace App\Livewire\Portfolio;

use App\Models\DesignElement;
use App\Models\Portfolio;
use App\Models\Project;
use App\Services\PortfolioCompletionService;
use Livewire\Component;

/**
 * SECTION 6 — ENGINEERING DESIGN PORTFOLIO
 * -----------------------------------------------------------------------------
 * One major design project per year, written across the 20 design-cycle steps
 * from config('portfolio.design_elements'). The steps are presented in order
 * and saved individually, so a student can fill step 3 in September and step 14
 * in March without losing anything in between.
 */
class DesignProject extends Component
{
    public Portfolio $portfolio;

    public bool $canEdit = true;

    public ?int $projectId = null;

    // Project header
    public string $title = '';
    public string $role = '';
    public ?int $teamSize = null;
    public string $summary = '';
    public ?int $courseId = null;

    /** element_key => content */
    public array $elements = [];

    public ?string $savedAt = null;

    public function mount(Portfolio $portfolio, bool $canEdit = true): void
    {
        $this->portfolio = $portfolio;
        $this->canEdit = $canEdit;

        $project = $portfolio->projects()->where('kind', 'design_project')->with('designElements')->first();

        if ($project) {
            $this->loadProject($project);
        } else {
            // Start with every step present but empty, so the student sees the
            // whole cycle from day one rather than discovering it step by step.
            foreach (array_keys(config('portfolio.design_elements')) as $key) {
                $this->elements[$key] = '';
            }
        }
    }

    protected function loadProject(Project $project): void
    {
        $this->projectId = $project->id;
        $this->title = $project->title;
        $this->role = (string) $project->role;
        $this->teamSize = $project->team_size;
        $this->summary = (string) $project->summary;
        $this->courseId = $project->curriculum_course_id;

        foreach (array_keys(config('portfolio.design_elements')) as $key) {
            $this->elements[$key] = $project->designElements->firstWhere('element_key', $key)?->content ?? '';
        }
    }

    public function save(): void
    {
        if (! $this->canEdit) {
            return;
        }

        $this->validate([
            'title' => ['required', 'string', 'max:200'],
            'role' => ['nullable', 'string', 'max:80'],
            'teamSize' => ['nullable', 'integer', 'between:1,20'],
            'summary' => ['nullable', 'string', 'max:2000'],
        ]);

        $project = Project::updateOrCreate(
            ['id' => $this->projectId],
            [
                'portfolio_id' => $this->portfolio->id,
                'curriculum_course_id' => $this->courseId,
                'title' => $this->title,
                'kind' => 'design_project',
                'role' => $this->role,
                'team_size' => $this->teamSize,
                'summary' => $this->summary,
            ]
        );

        $this->projectId = $project->id;

        // Persist the 20 steps, keeping their canonical order.
        $position = 1;
        foreach (config('portfolio.design_elements') as $key => $label) {
            DesignElement::updateOrCreate(
                ['project_id' => $project->id, 'element_key' => $key],
                ['position' => $position, 'content' => $this->elements[$key] ?? null]
            );
            $position++;
        }

        app(PortfolioCompletionService::class)->recomputeSection($this->portfolio->entryFor(6));

        $this->savedAt = now()->format('g:i a');
        $this->dispatch('section-saved', section: 6);
    }

    /** Autosave on debounced typing in any step. */
    public function updatedElements(): void
    {
        if ($this->projectId) {
            $this->save();
        }
    }

    public function render()
    {
        return view('livewire.portfolio.design-project', [
            'definitions' => config('portfolio.design_elements'),
            'courses' => \App\Models\CurriculumCourse::where('program_id', $this->portfolio->student->program_id)
                ->upToYear($this->portfolio->year_level)->orderBy('code')->get(),
            'completion' => $this->projectId
                ? Project::find($this->projectId)?->designCompletion() ?? 0
                : 0,
        ]);
    }
}
