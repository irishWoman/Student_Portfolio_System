<?php

namespace App\Livewire\Faculty;

use App\Models\AssessmentPloScore;
use App\Models\AuditLog;
use App\Models\CourseEvidenceRecord;
use App\Models\FacultyEvaluation;
use App\Models\FacultyEvaluationScore;
use App\Models\Portfolio;
use App\Services\PloAttainmentService;
use Livewire\Component;

/**
 * FACULTY ASSESSMENT FORM (specification Section X)
 * -----------------------------------------------------------------------------
 * Ten criteria, rated 1-4, with a comment required for anything below the
 * expected level. The same screen is where the evaluator validates the levels
 * the student claimed on their course evidence, which is the step that turns a
 * student's claim into countable attainment data.
 */
class EvaluationForm extends Component
{
    public Portfolio $portfolio;

    public string $artifact = '';

    /** criterion key => rating 1-4 */
    public array $ratings = [];

    /** criterion key => comment */
    public array $comments = [];

    public string $overallComment = '';

    /** course_evidence_record_id => validated level */
    public array $validatedLevels = [];

    public function mount(Portfolio $portfolio): void
    {
        $this->portfolio = $portfolio;

        foreach (array_keys(config('portfolio.faculty_criteria')) as $criterion) {
            $this->ratings[$criterion] = null;
            $this->comments[$criterion] = '';
        }

        foreach ($portfolio->courseEvidence as $record) {
            $this->validatedLevels[$record->id] = $record->validated_level ?? $record->claimed_level;
        }
    }

    /** Save the ten-criterion assessment. */
    public function saveEvaluation(): void
    {
        $threshold = (int) config('portfolio.comment_below', 3);

        $this->validate([
            'artifact' => ['required', 'string', 'max:200'],
            'ratings.*' => ['required', 'integer', 'between:1,4'],
        ], [
            'ratings.*.required' => 'Rate every criterion before saving.',
        ]);

        // A low rating without a comment is not useful to the student.
        foreach ($this->ratings as $criterion => $rating) {
            if ($rating < $threshold && blank($this->comments[$criterion] ?? null)) {
                $this->addError('comments.'.$criterion, 'A comment is required for ratings below '.$threshold.'.');
            }
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $evaluation = FacultyEvaluation::create([
            'portfolio_id' => $this->portfolio->id,
            'evaluator_id' => auth()->id(),
            'artifact_assessed' => $this->artifact,
            'evaluated_on' => now(),
            'overall_comment' => $this->overallComment,
        ]);

        foreach ($this->ratings as $criterion => $rating) {
            FacultyEvaluationScore::create([
                'faculty_evaluation_id' => $evaluation->id,
                'criterion' => $criterion,
                'rating' => $rating,
                'comment' => $this->comments[$criterion] ?: null,
            ]);
        }

        $evaluation->update(['overall_rating' => $evaluation->computeOverall()]);

        AuditLog::record('faculty.evaluation.created', $evaluation);

        $this->reset(['artifact', 'overallComment']);
        $this->dispatch('evaluation-saved');
    }

    /**
     * Validate the student's claimed levels. This is the moment a claim becomes
     * direct evidence: nothing enters the attainment figure before it.
     */
    public function validateLevels(PloAttainmentService $attainment): void
    {
        foreach ($this->validatedLevels as $recordId => $level) {
            if (! $level) {
                continue;
            }

            $record = CourseEvidenceRecord::find($recordId);

            if (! $record || $record->portfolio_id !== $this->portfolio->id) {
                continue;
            }

            $record->update([
                'validated_level' => (int) $level,
                'evaluator_id' => auth()->id(),
                'evaluated_on' => now(),
            ]);
        }

        // Mirror the decision into assessment_plo_scores so one query answers
        // "what direct evidence exists for this PLO?".
        $this->syncPloScores();

        $attainment->recomputeForPortfolio($this->portfolio);

        AuditLog::record('faculty.levels.validated', $this->portfolio);

        $this->dispatch('levels-validated');
    }

    protected function syncPloScores(): void
    {
        $assessment = $this->portfolio->assessments()->firstOrCreate(
            ['type' => 'course', 'title' => 'Validated course evidence'],
            ['assessed_on' => now(), 'evaluator_id' => auth()->id()]
        );

        foreach ($this->portfolio->courseEvidence()->with('plos')->get() as $record) {
            if (! $record->validated_level) {
                continue;
            }

            foreach ($record->plos as $plo) {
                AssessmentPloScore::updateOrCreate(
                    ['assessment_id' => $assessment->id, 'plo_id' => $plo->id, 'is_self_assessment' => false],
                    [
                        'level' => $record->validated_level,
                        'is_validated' => true,
                        'validated_by' => auth()->id(),
                        'validated_at' => now(),
                    ]
                );
            }
        }
    }

    public function render()
    {
        return view('livewire.faculty.evaluation-form', [
            'criteria' => config('portfolio.faculty_criteria'),
            'records' => $this->portfolio->courseEvidence()->with(['course', 'plos'])->get(),
            'evaluations' => $this->portfolio->facultyEvaluations()->with(['scores', 'evaluator'])->latest()->get(),
        ]);
    }
}
