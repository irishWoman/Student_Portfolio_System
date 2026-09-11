<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Self-assessment, exit survey, employer and alumni feedback. Reported next to
 * the direct figure on every dashboard, never averaged into it.
 */
class IndirectAssessment extends Model
{
    protected $fillable = ['portfolio_id', 'plo_id', 'source', 'level', 'comment', 'collected_on'];

    protected $casts = ['collected_on' => 'date'];

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    public function plo(): BelongsTo
    {
        return $this->belongsTo(Plo::class);
    }
}
