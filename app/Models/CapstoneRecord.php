<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CapstoneRecord extends Model
{
    protected $fillable = [
        'portfolio_id', 'project_id', 'title', 'problem_definition', 'literature_review',
        'requirements', 'system_architecture', 'design', 'implementation', 'testing',
        'validation', 'cost_analysis', 'risk_assessment', 'ethics', 'sustainability',
        'documentation_note', 'proposal_defended_on', 'final_defended_on', 'adviser_name',
    ];

    protected $casts = ['proposal_defended_on' => 'date', 'final_defended_on' => 'date'];

    /** Capstone criteria and the PLO each one maps to (specification Section 10). */
    public const CRITERIA_PLO_MAP = [
        'problem_definition' => 2,
        'literature_review' => 2,
        'requirements' => 3,
        'system_architecture' => 3,
        'design' => 3,
        'implementation' => 14,
        'testing' => 4,
        'validation' => 4,
        'cost_analysis' => 11,
        'risk_assessment' => 11,
        'ethics' => 8,
        'sustainability' => 7,
        'documentation_note' => 10,
    ];

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function criteriaScores(): HasMany
    {
        return $this->hasMany(CapstoneCriteriaScore::class);
    }

    public function evidenceFiles(): MorphMany
    {
        return $this->morphMany(EvidenceFile::class, 'attachable');
    }
}
