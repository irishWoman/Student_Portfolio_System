<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CqiAction extends Model
{
    protected $fillable = [
        'program_id', 'academic_year_id', 'plo_id', 'gap_description', 'evidence_summary',
        'possible_cause', 'intervention', 'responsible_unit', 'target_date',
        'reassessment_date', 'status', 'baseline_score', 'reassessed_score',
    ];

    protected $casts = ['target_date' => 'date', 'reassessment_date' => 'date'];

    public function plo(): BelongsTo
    {
        return $this->belongsTo(Plo::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /** Did the intervention move the number? Null until reassessed. */
    public function delta(): ?float
    {
        if ($this->baseline_score === null || $this->reassessed_score === null) {
            return null;
        }

        return round((float) $this->reassessed_score - (float) $this->baseline_score, 2);
    }
}
