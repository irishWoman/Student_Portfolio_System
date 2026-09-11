<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FacultyEvaluation extends Model
{
    protected $fillable = [
        'portfolio_id', 'evaluator_id', 'artifact_assessed',
        'curriculum_course_id', 'evaluated_on', 'overall_comment', 'overall_rating',
    ];

    protected $casts = ['evaluated_on' => 'date'];

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(FacultyEvaluationScore::class);
    }

    /** Mean of the ten criterion ratings. */
    public function computeOverall(): float
    {
        return round((float) $this->scores()->avg('rating'), 2);
    }
}
