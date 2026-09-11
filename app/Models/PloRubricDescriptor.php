<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PloRubricDescriptor extends Model
{
    protected $fillable = ['plo_id', 'level', 'descriptor'];

    public function plo(): BelongsTo
    {
        return $this->belongsTo(Plo::class);
    }
}
