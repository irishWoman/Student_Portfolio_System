<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Competency extends Model
{
    protected $fillable = ['competency_category_id', 'name', 'description', 'sort_order'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(CompetencyCategory::class, 'competency_category_id');
    }

    public function plos(): BelongsToMany
    {
        return $this->belongsToMany(Plo::class, 'competency_plo');
    }
}
