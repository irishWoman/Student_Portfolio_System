<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    protected $fillable = ['label', 'starts_on', 'ends_on', 'portfolio_due_on', 'is_current'];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
        'portfolio_due_on' => 'date',
        'is_current' => 'boolean',
    ];

    public function terms(): HasMany
    {
        return $this->hasMany(Term::class);
    }

    public function portfolios(): HasMany
    {
        return $this->hasMany(Portfolio::class);
    }

    /** The academic year the system is currently operating in. */
    public static function current(): ?self
    {
        return static::where('is_current', true)->first()
            ?? static::orderByDesc('starts_on')->first();
    }

    public function currentTerm(): ?Term
    {
        return $this->terms()->where('is_current', true)->first();
    }
}
