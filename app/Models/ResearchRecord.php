<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ResearchRecord extends Model
{
    protected $fillable = ['portfolio_id', 'title', 'venue', 'completed_on', 'abstract', 'methodology', 'findings'];

    protected $casts = ['completed_on' => 'date'];

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    public function evidenceFiles(): MorphMany
    {
        return $this->morphMany(EvidenceFile::class, 'attachable');
    }
}
