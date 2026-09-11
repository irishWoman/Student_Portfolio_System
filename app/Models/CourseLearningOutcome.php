<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CourseLearningOutcome extends Model
{
    protected $fillable = ['curriculum_course_id', 'code', 'statement'];

    public function course(): BelongsTo
    {
        return $this->belongsTo(CurriculumCourse::class, 'curriculum_course_id');
    }

    /** PLOs this CLO maps to, with the level the course targets. */
    public function plos(): BelongsToMany
    {
        return $this->belongsToMany(Plo::class, 'clo_plo', 'course_learning_outcome_id', 'plo_id')
            ->withPivot('target_level')
            ->withTimestamps();
    }
}
