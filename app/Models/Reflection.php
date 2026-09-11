<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Reflection extends Model
{
    protected $fillable = [
        'portfolio_id', 'reflectable_type', 'reflectable_id',
        'subject', 'answers', 'completed_at',
    ];

    protected $casts = ['answers' => 'array', 'completed_at' => 'datetime'];

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    public function reflectable(): MorphTo
    {
        return $this->morphTo();
    }

    public function answer(string $key): ?string
    {
        return data_get($this->answers, $key);
    }

    /** All ten prompts answered? */
    public function isComplete(): bool
    {
        foreach (array_keys(config('portfolio.reflection_prompts')) as $key) {
            if (blank($this->answer($key))) {
                return false;
            }
        }

        return true;
    }
}
