<?php

namespace App\Livewire\Chair;

use App\Models\AuditLog;
use App\Models\CourseLearningOutcome;
use App\Models\CurriculumCourse;
use App\Models\Plo;
use App\Models\Program;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * CURRICULUM MAPPING
 * =============================================================================
 * Where faculty maintain course learning outcomes and map them to PLOs.
 *
 * This screen is what makes the rest of the system defensible. Attainment
 * figures are only as meaningful as the mapping behind them, and a mapping that
 * lives in a spreadsheet — or, worse, only in the seeder — drifts out of date
 * the moment a syllabus changes.
 *
 * Two things are surfaced deliberately:
 *
 *   1. PLO coverage across the whole curriculum, with uncovered outcomes shown
 *      in red. An outcome no course claims to teach cannot be attained, and that
 *      is a curriculum problem, not a student problem.
 *   2. The target level per mapping, so a first-year course can claim to
 *      introduce an outcome without claiming to develop it to proficiency.
 * =============================================================================
 */
class CurriculumMapping extends Component
{
    // --- Course list filters --------------------------------------------------
    public ?int $yearFilter = null;
    public string $termFilter = '';
    public bool $majorsOnly = true;
    public string $search = '';

    // --- Selection ------------------------------------------------------------
    public ?int $courseId = null;
    public ?int $editingCloId = null;

    // --- CLO form -------------------------------------------------------------
    public string $cloCode = '';
    public string $cloStatement = '';

    /** plo_id => bool, checked state of each outcome for the CLO being edited */
    public array $selectedPlos = [];

    /** plo_id => target level 1-4 */
    public array $targetLevels = [];

    protected function rules(): array
    {
        return [
            'cloCode' => ['required', 'string', 'max:20'],
            'cloStatement' => ['required', 'string', 'min:15', 'max:500'],
        ];
    }

    protected $messages = [
        'cloStatement.min' => 'Write the outcome as a full statement — an assessor has to be able to tell whether a student met it.',
    ];

    public function mount(): void
    {
        // Land on the first major course so the screen is never empty.
        $this->courseId = $this->courses()->first()?->id;
        $this->resetCloForm();
    }

    // --- Course selection -----------------------------------------------------

    public function selectCourse(int $id): void
    {
        $this->courseId = $id;
        $this->resetCloForm();
    }

    public function updatedYearFilter(): void
    {
        $this->courseId = $this->courses()->first()?->id;
    }

    public function updatedMajorsOnly(): void
    {
        $this->courseId = $this->courses()->first()?->id;
    }

    // --- CLO editing ----------------------------------------------------------

    public function editClo(int $id): void
    {
        $clo = CourseLearningOutcome::with('plos')->findOrFail($id);

        $this->editingCloId = $clo->id;
        $this->cloCode = (string) $clo->code;
        $this->cloStatement = $clo->statement;

        $this->selectedPlos = [];
        $this->targetLevels = [];

        foreach ($clo->plos as $plo) {
            $this->selectedPlos[$plo->id] = true;
            $this->targetLevels[$plo->id] = $plo->pivot->target_level;
        }
    }

    public function saveClo(): void
    {
        $data = $this->validate();

        $course = CurriculumCourse::findOrFail($this->courseId);

        $checked = collect($this->selectedPlos)->filter()->keys();

        // A CLO that maps to nothing contributes nothing; catch it here rather
        // than letting it sit in the curriculum looking like coverage.
        if ($checked->isEmpty()) {
            $this->addError('selectedPlos', 'Map this outcome to at least one PLO, or it will never produce attainment data.');

            return;
        }

        $clo = CourseLearningOutcome::updateOrCreate(
            ['id' => $this->editingCloId],
            [
                'curriculum_course_id' => $course->id,
                'code' => $data['cloCode'],
                'statement' => $data['cloStatement'],
            ]
        );

        $clo->plos()->sync(
            $checked->mapWithKeys(fn ($ploId) => [
                $ploId => ['target_level' => (int) ($this->targetLevels[$ploId] ?? $this->defaultTarget($course))],
            ])->all()
        );

        AuditLog::record('curriculum.clo.saved', $clo, [
            'course' => $course->code,
            'plos' => $checked->values()->all(),
        ]);

        $this->resetCloForm();
        $this->dispatch('section-saved', section: 0);
    }

    public function deleteClo(int $id): void
    {
        $clo = CourseLearningOutcome::findOrFail($id);

        // Course evidence rows point at CLOs. Removing one out from under a
        // student's submitted evidence would orphan their record, so block it.
        $inUse = \App\Models\CourseEvidenceRecord::where('course_learning_outcome_id', $clo->id)->exists();

        if ($inUse) {
            $this->addError('clos', 'Students have already cited this outcome as evidence. Reword it instead of deleting it.');

            return;
        }

        AuditLog::record('curriculum.clo.deleted', $clo);
        $clo->delete();

        $this->resetCloForm();
    }

    public function resetCloForm(): void
    {
        $this->reset(['editingCloId', 'cloStatement', 'selectedPlos', 'targetLevels']);

        $course = $this->course();
        $this->cloCode = 'CLO'.(($course?->learningOutcomes()->count() ?? 0) + 1);
    }

    /**
     * Sensible default target for a new mapping: what a course at that year
     * level is normally expected to reach. The evaluator can override it.
     */
    protected function defaultTarget(CurriculumCourse $course): int
    {
        return min(4, max(1, $course->year_level));
    }

    /** Ticking a PLO pre-fills its target level, so one click is enough. */
    public function updatedSelectedPlos($value, $key): void
    {
        if ($value && ! isset($this->targetLevels[$key]) && ($course = $this->course())) {
            $this->targetLevels[$key] = $this->defaultTarget($course);
        }
    }

    // --- Data -----------------------------------------------------------------

    public function course(): ?CurriculumCourse
    {
        return $this->courseId ? CurriculumCourse::find($this->courseId) : null;
    }

    public function courses(): Collection
    {
        return CurriculumCourse::query()
            ->withCount('learningOutcomes')
            ->when($this->majorsOnly, fn ($q) => $q->where('is_major', true))
            ->when($this->yearFilter, fn ($q) => $q->where('year_level', $this->yearFilter))
            ->when($this->termFilter, fn ($q) => $q->where('term_kind', $this->termFilter))
            ->when($this->search, fn ($q) => $q->where(fn ($sub) => $sub
                ->where('code', 'like', "%{$this->search}%")
                ->orWhere('title', 'like', "%{$this->search}%")))
            ->orderBy('year_level')
            ->orderBy('term_kind')
            ->orderBy('code')
            ->get();
    }

    /**
     * How many courses map to each PLO, and at what highest target level.
     * This is the coverage question an accreditor asks first.
     *
     * @return array<int, array{courses: int, highest: int}>
     */
    public function coverage(): array
    {
        $rows = DB::table('clo_plo')
            ->join('course_learning_outcomes', 'course_learning_outcomes.id', '=', 'clo_plo.course_learning_outcome_id')
            ->join('curriculum_courses', 'curriculum_courses.id', '=', 'course_learning_outcomes.curriculum_course_id')
            ->select('clo_plo.plo_id')
            ->selectRaw('COUNT(DISTINCT curriculum_courses.id) as course_count')
            ->selectRaw('MAX(clo_plo.target_level) as highest')
            ->groupBy('clo_plo.plo_id')
            ->get()
            ->keyBy('plo_id');

        $coverage = [];

        foreach (Plo::orderBy('number')->get() as $plo) {
            $row = $rows->get($plo->id);

            $coverage[$plo->number] = [
                'plo' => $plo,
                'courses' => (int) ($row->course_count ?? 0),
                'highest' => (int) ($row->highest ?? 0),
            ];
        }

        return $coverage;
    }

    public function render()
    {
        $course = $this->course();

        return view('livewire.chair.curriculum-mapping', [
            'courses' => $this->courses(),
            'course' => $course,
            'clos' => $course?->learningOutcomes()->with('plos')->get() ?? collect(),
            'plos' => Plo::orderBy('number')->get(),
            'coverage' => $this->coverage(),
            'program' => Program::where('is_active', true)->first(),
        ]);
    }
}
