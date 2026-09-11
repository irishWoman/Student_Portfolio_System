<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Project extends Model
{
    protected $fillable = [
        'portfolio_id', 'curriculum_course_id', 'title', 'kind', 'role',
        'team_size', 'started_on', 'completed_on', 'summary',
    ];

    protected $casts = ['started_on' => 'date', 'completed_on' => 'date'];

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(CurriculumCourse::class, 'curriculum_course_id');
    }

    public function designElements(): HasMany
    {
        return $this->hasMany(DesignElement::class)->orderBy('position');
    }

    public function evidenceFiles(): MorphMany
    {
        return $this->morphMany(EvidenceFile::class, 'attachable');
    }

    public function reflection(): MorphMany
    {
        return $this->morphMany(Reflection::class, 'reflectable');
    }

    /** How many of the 20 design-cycle elements have been written. */
    public function designCompletion(): int
    {
        $total = count(config('portfolio.design_elements'));
        $filled = $this->designElements->filter(fn ($e) => filled($e->content))->count();

        return $total === 0 ? 0 : (int) round($filled / $total * 100);
    }
}
