<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plo extends Model
{
    protected $fillable = ['number', 'title', 'statement'];

    public function descriptors(): HasMany
    {
        return $this->hasMany(PloRubricDescriptor::class);
    }

    public function competencies(): BelongsToMany
    {
        return $this->belongsToMany(Competency::class, 'competency_plo');
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(PloAttainmentSnapshot::class);
    }

    /** "PLO 3" */
    public function code(): string
    {
        return 'PLO '.$this->number;
    }

    public function descriptorFor(int $level): ?string
    {
        return $this->descriptors->firstWhere('level', $level)?->descriptor;
    }
}
