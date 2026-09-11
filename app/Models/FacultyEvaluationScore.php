<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacultyEvaluationScore extends Model
{
    protected $fillable = ['faculty_evaluation_id', 'criterion', 'rating', 'comment'];

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(FacultyEvaluation::class, 'faculty_evaluation_id');
    }

    public function label(): string
    {
        return config('portfolio.faculty_criteria')[$this->criterion] ?? $this->criterion;
    }

    /** The form requires a comment when a rating falls below the threshold. */
    public function requiresComment(): bool
    {
        return $this->rating < (int) config('portfolio.comment_below', 3);
    }
}
