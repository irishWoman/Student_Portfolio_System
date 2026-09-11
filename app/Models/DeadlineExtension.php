<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeadlineExtension extends Model
{
    protected $fillable = [
        'deadline_id', 'student_id', 'extended_to', 'reason',
        'status', 'decided_by', 'decided_at',
    ];

    protected $casts = ['extended_to' => 'datetime', 'decided_at' => 'datetime'];

    public function deadline(): BelongsTo
    {
        return $this->belongsTo(Deadline::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
