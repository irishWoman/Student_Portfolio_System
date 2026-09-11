<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompetencyCategory extends Model
{
    protected $fillable = ['key', 'name', 'sort_order'];

    public function competencies(): HasMany
    {
        return $this->hasMany(Competency::class)->orderBy('sort_order');
    }
}
