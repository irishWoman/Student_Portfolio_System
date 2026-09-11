<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Section 4 row: course, CLO, PLOs, assessment activity, output, score, level.
 * The student fills everything except validated_level, which is the evaluator's.
 */
class CourseEvidenceRecord extends Model
{
    protected $fillable = [
        'portfolio_id', 'curriculum_course_id', 'course_learning_outcome_id',
        'clo_statement', 'assessment_activity', 'output_title', 'score', 'score_max',
        'claimed_level', 'validated_level', 'evaluator_id', 'evaluated_on',
        'reflection_note', 'improvement_action',
    ];

    protected $casts = ['evaluated_on' => 'date'];

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(CurriculumCourse::class, 'curriculum_course_id');
    }

    public function learningOutcome(): BelongsTo
    {
        return $this->belongsTo(CourseLearningOutcome::class, 'course_learning_outcome_id');
    }

    public function plos(): BelongsToMany
    {
        return $this->belongsToMany(Plo::class, 'course_evidence_plo');
    }

    public function evidenceFiles(): MorphMany
    {
        return $this->morphMany(EvidenceFile::class, 'attachable');
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function isValidated(): bool
    {
        return $this->validated_level !== null;
    }
}
