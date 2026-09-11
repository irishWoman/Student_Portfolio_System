<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class OjtRecord extends Model
{
    protected $fillable = [
        'portfolio_id', 'company_name', 'company_address', 'industry',
        'supervisor_name', 'supervisor_position', 'supervisor_email',
        'started_on', 'ended_on', 'required_hours', 'completed_hours',
        'objectives', 'responsibilities', 'work_outputs', 'student_reflection',
    ];

    protected $casts = ['started_on' => 'date', 'ended_on' => 'date'];

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    public function supervisorEvaluation(): HasOne
    {
        return $this->hasOne(OjtSupervisorEvaluation::class);
    }

    public function evidenceFiles(): MorphMany
    {
        return $this->morphMany(EvidenceFile::class, 'attachable');
    }

    public function hoursProgress(): int
    {
        return $this->required_hours === 0
            ? 0
            : min(100, (int) round($this->completed_hours / $this->required_hours * 100));
    }
}
