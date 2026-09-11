<?php

namespace App\Livewire\Portfolio;

use App\Models\CourseEvidenceRecord;
use App\Models\CurriculumCourse;
use App\Models\Plo;
use App\Models\Portfolio;
use App\Services\PortfolioCompletionService;
use Livewire\Component;

/**
 * SECTION 4 — COURSE-BASED EVIDENCE
 * -----------------------------------------------------------------------------
 * A repeater: one row per course output the student offers as evidence. The
 * course list comes from the seeded curriculum, filtered to the student's year
 * level and below, so a second year cannot cite a fourth year course.
 *
 * The student supplies everything except `validated_level`, which only an
 * evaluator can set. That single rule is what keeps the attainment figure
 * honest.
 */
class CourseEvidence extends Component
{
    public Portfolio $portfolio;

    public bool $canEdit = true;

    // --- New-row form state ---------------------------------------------------
    public ?int $courseId = null;
    public string $cloStatement = '';
    public string $activity = '';
    public string $output = '';
    public ?string $score = null;
    public ?string $scoreMax = '100';
    public ?int $claimedLevel = null;
    public array $selectedPlos = [];
    public string $reflectionNote = '';

    public ?int $editingId = null;

    protected function rules(): array
    {
        return [
            'courseId' => ['required', 'exists:curriculum_courses,id'],
            'cloStatement' => ['required', 'string', 'max:300'],
            'activity' => ['required', 'string', 'max:180'],
            'output' => ['required', 'string', 'max:180'],
            'score' => ['nullable', 'numeric', 'min:0'],
            'scoreMax' => ['nullable', 'numeric', 'min:1'],
            'claimedLevel' => ['required', 'integer', 'between:1,4'],
            'selectedPlos' => ['required', 'array', 'min:1'],
            'selectedPlos.*' => ['integer', 'exists:plos,id'],
            'reflectionNote' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected $messages = [
        'selectedPlos.required' => 'Pick at least one PLO this output demonstrates.',
        'claimedLevel.required' => 'Say what level you think this output shows.',
    ];

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

        $data = $this->validate();

        $record = CourseEvidenceRecord::updateOrCreate(
            ['id' => $this->editingId],
            [
                'portfolio_id' => $this->portfolio->id,
                'curriculum_course_id' => $data['courseId'],
                'clo_statement' => $data['cloStatement'],
                'assessment_activity' => $data['activity'],
                'output_title' => $data['output'],
                'score' => $data['score'],
                'score_max' => $data['scoreMax'],
                'claimed_level' => $data['claimedLevel'],
                'reflection_note' => $data['reflectionNote'],
            ]
        );

        $record->plos()->sync($data['selectedPlos']);

        app(PortfolioCompletionService::class)->recomputeSection($this->portfolio->entryFor(4));

        $this->resetForm();
        $this->dispatch('section-saved', section: 4);
    }

    public function edit(int $id): void
    {
        $record = CourseEvidenceRecord::with('plos')->findOrFail($id);
        abort_unless($record->portfolio_id === $this->portfolio->id, 403);

        $this->editingId = $record->id;
        $this->courseId = $record->curriculum_course_id;
        $this->cloStatement = (string) $record->clo_statement;
        $this->activity = $record->assessment_activity;
        $this->output = $record->output_title;
        $this->score = $record->score;
        $this->scoreMax = $record->score_max;
        $this->claimedLevel = $record->claimed_level;
        $this->selectedPlos = $record->plos->pluck('id')->all();
        $this->reflectionNote = (string) $record->reflection_note;
    }

    public function delete(int $id): void
    {
        $record = CourseEvidenceRecord::findOrFail($id);
        abort_unless($record->portfolio_id === $this->portfolio->id, 403);

        // Once an evaluator has ruled on a row it belongs to the assessment
        // record, not to the student.
        if ($record->isValidated()) {
            $this->addError('records', 'That entry has been validated and can no longer be removed.');

            return;
        }

        $record->delete();
        app(PortfolioCompletionService::class)->recomputeSection($this->portfolio->entryFor(4));
    }

    public function resetForm(): void
    {
        $this->reset(['courseId', 'cloStatement', 'activity', 'output', 'score',
            'claimedLevel', 'selectedPlos', 'reflectionNote', 'editingId']);
        $this->scoreMax = '100';
    }

    public function render()
    {
        return view('livewire.portfolio.course-evidence', [
            'records' => $this->portfolio->courseEvidence()
                ->with(['course', 'plos', 'evaluator'])
                ->latest('id')->get(),
            'courses' => CurriculumCourse::where('program_id', $this->portfolio->student->program_id)
                ->upToYear($this->portfolio->year_level)
                ->orderBy('year_level')->orderBy('code')->get(),
            'plos' => Plo::orderBy('number')->get(),
        ]);
    }
}
