<?php

namespace App\Models;

use App\Support\Enums\CompetencyStage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TechnicalCompetencyRecord extends Model
{
    protected $fillable = [
        'portfolio_id', 'competency_id', 'claimed_stage', 'validated_stage',
        'evidence_note', 'evaluator_id',
    ];

    protected $casts = [
        'claimed_stage' => CompetencyStage::class,
        'validated_stage' => CompetencyStage::class,
    ];

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    public function competency(): BelongsTo
    {
        return $this->belongsTo(Competency::class);
    }

    /** Validated stage where one exists, otherwise the student's claim. */
    public function effectiveStage(): ?CompetencyStage
    {
        return $this->validated_stage ?? $this->claimed_stage;
    }
}
