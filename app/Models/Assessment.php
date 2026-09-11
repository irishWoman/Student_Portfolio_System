<?php

namespace App\Models;

use App\Support\Enums\AssessmentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * One assessed artifact. The PLO levels awarded for it live in
 * assessment_plo_scores; this row carries the context (what, which course,
 * who assessed it, raw score).
 */
class Assessment extends Model
{
    protected $fillable = [
        'portfolio_id', 'assessable_type', 'assessable_id', 'type', 'title',
        'curriculum_course_id', 'course_learning_outcome_id', 'raw_score',
        'max_score', 'evaluator_id', 'assessed_on', 'remarks',
    ];

    protected $casts = ['assessed_on' => 'date', 'type' => AssessmentType::class];

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    public function assessable(): MorphTo
    {
        return $this->morphTo();
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(CurriculumCourse::class, 'curriculum_course_id');
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function ploScores(): HasMany
    {
        return $this->hasMany(AssessmentPloScore::class);
    }

    /** Percentage form of the raw score, when both numbers are present. */
    public function percentage(): ?float
    {
        if (! $this->raw_score || ! $this->max_score) {
            return null;
        }

        return round(($this->raw_score / $this->max_score) * 100, 2);
    }
}
