<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A dated record of one student's attainment of one PLO in one academic year.
 * Recomputed by PloAttainmentService; kept so the department can show an
 * accreditor what the figure was on a given date.
 */
class PloAttainmentSnapshot extends Model
{
    protected $fillable = [
        'student_id', 'academic_year_id', 'plo_id', 'year_level',
        'direct_score', 'indirect_score', 'evidence_count',
        'valid_evidence_count', 'flag', 'computed_at',
    ];

    protected $casts = ['computed_at' => 'datetime'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function plo(): BelongsTo
    {
        return $this->belongsTo(Plo::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function flagLabel(): string
    {
        return match ($this->flag) {
            'on_track' => 'On track',
            'watch' => 'Watch',
            'under_assessed' => 'Under-assessed',
            'at_risk' => 'Needs intervention',
            default => 'No data',
        };
    }

    public function flagClasses(): string
    {
        return match ($this->flag) {
            'on_track' => 'bg-emerald-50 text-status-ontrack ring-emerald-200',
            'watch' => 'bg-amber-50 text-status-watch ring-amber-200',
            'under_assessed' => 'bg-slate-100 text-slate-600 ring-slate-300',
            'at_risk' => 'bg-red-50 text-status-risk ring-red-200',
            default => 'bg-slate-50 text-slate-400 ring-slate-200',
        };
    }
}
