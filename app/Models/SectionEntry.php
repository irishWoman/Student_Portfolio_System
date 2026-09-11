<?php

namespace App\Models;

use App\Support\Enums\SubmissionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One section of one portfolio. `payload` carries the answers for
 * config-driven sections; table-based sections leave it null and use this row
 * purely for status, review notes and completion.
 */
class SectionEntry extends Model
{
    protected $fillable = [
        'portfolio_id', 'section_number', 'section_key', 'payload', 'status',
        'completion_percent', 'submitted_at', 'reviewed_at', 'reviewed_by',
        'reviewer_notes', 'is_late',
    ];

    protected $casts = [
        'payload' => 'array',
        'status' => SubmissionStatus::class,
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'is_late' => 'boolean',
    ];

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** The config block describing this section. */
    public function definition(): array
    {
        return config('portfolio.sections')[$this->section_number] ?? [];
    }

    public function title(): string
    {
        return $this->definition()['title'] ?? 'Section '.$this->section_number;
    }

    public function answer(string $field, $default = null)
    {
        return data_get($this->payload, $field, $default);
    }
}
