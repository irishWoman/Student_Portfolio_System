<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CurriculumCourse extends Model
{
    protected $fillable = ['program_id', 'code', 'title', 'units', 'year_level', 'term_kind', 'is_major'];

    protected $casts = ['is_major' => 'boolean'];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function learningOutcomes(): HasMany
    {
        return $this->hasMany(CourseLearningOutcome::class);
    }

    public function label(): string
    {
        return $this->code.' — '.$this->title;
    }

    /** Courses a given year level may cite as evidence (this year and earlier). */
    public function scopeUpToYear($query, int $yearLevel)
    {
        return $query->where('year_level', '<=', $yearLevel);
    }
}
