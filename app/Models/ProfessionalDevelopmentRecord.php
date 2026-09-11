<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ProfessionalDevelopmentRecord extends Model
{
    protected $fillable = [
        'portfolio_id', 'kind', 'title', 'organizer', 'held_on', 'hours',
        'competency_demonstrated', 'claimed_level', 'validated_level', 'evaluator_id',
    ];

    protected $casts = ['held_on' => 'date'];

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    public function evidenceFiles(): MorphMany
    {
        return $this->morphMany(EvidenceFile::class, 'attachable');
    }

    public function kindLabel(): string
    {
        return str($this->kind)->replace('_', ' ')->title()->toString();
    }
}
