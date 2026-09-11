<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DesignElement extends Model
{
    protected $fillable = ['project_id', 'element_key', 'position', 'content'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function label(): string
    {
        return config('portfolio.design_elements')[$this->element_key] ?? $this->element_key;
    }
}
