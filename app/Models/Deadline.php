<?php

namespace App\Models;

use App\Support\Enums\DeadlineScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Deadline extends Model
{
    protected $fillable = [
        'deadline_set_id', 'term_id', 'section_number', 'scope', 'title',
        'due_at', 'grace_days', 'locks_editing', 'instructions',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'locks_editing' => 'boolean',
        'scope' => DeadlineScope::class,
    ];

    public function set(): BelongsTo
    {
        return $this->belongsTo(DeadlineSet::class, 'deadline_set_id');
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function extensions(): HasMany
    {
        return $this->hasMany(DeadlineExtension::class);
    }

    /** Effective due date for one student, honouring an approved extension. */
    public function dueFor(Student $student): \Illuminate\Support\Carbon
    {
        $extension = $this->extensions
            ->first(fn ($e) => $e->student_id === $student->id && $e->status === 'approved');

        return $extension ? $extension->extended_to : $this->due_at;
    }

    /** Last moment a late submission is still accepted. */
    public function graceEndsFor(Student $student): \Illuminate\Support\Carbon
    {
        return $this->dueFor($student)->copy()->addDays($this->grace_days);
    }

    public function isOverdueFor(Student $student): bool
    {
        return now()->greaterThan($this->dueFor($student));
    }

    public function sectionTitle(): string
    {
        return $this->section_number
            ? (config('portfolio.sections')[$this->section_number]['title'] ?? 'Section '.$this->section_number)
            : 'Whole portfolio';
    }
}
