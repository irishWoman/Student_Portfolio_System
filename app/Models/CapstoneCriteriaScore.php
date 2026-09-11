<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CapstoneCriteriaScore extends Model
{
    protected $fillable = ['capstone_record_id', 'criterion', 'plo_id', 'rating', 'comment', 'evaluator_id'];

    public function capstoneRecord(): BelongsTo
    {
        return $this->belongsTo(CapstoneRecord::class);
    }

    public function plo(): BelongsTo
    {
        return $this->belongsTo(Plo::class);
    }
}
