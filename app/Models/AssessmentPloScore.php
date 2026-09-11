<?php

namespace App\Models;

use App\Support\Enums\AttainmentLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The atom of the whole assessment system: "for this artifact, this PLO was
 * demonstrated at this level".
 *
 * Two rows can exist per artifact per PLO: the student's self-assessment
 * (is_self_assessment = true) and the faculty's validated judgement. Only the
 * validated, non-self row is counted as direct evidence.
 */
class AssessmentPloScore extends Model
{
    protected $fillable = [
        'assessment_id', 'plo_id', 'level', 'is_self_assessment',
        'is_validated', 'validated_by', 'validated_at', 'validator_note',
    ];

    protected $casts = [
        'is_self_assessment' => 'boolean',
        'is_validated' => 'boolean',
        'validated_at' => 'datetime',
    ];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function plo(): BelongsTo
    {
        return $this->belongsTo(Plo::class);
    }

    public function attainmentLevel(): AttainmentLevel
    {
        return AttainmentLevel::from($this->level);
    }

    /** Rows that count as valid direct evidence. */
    public function scopeCountable($query)
    {
        return $query->where('is_self_assessment', false)->where('is_validated', true);
    }
}
