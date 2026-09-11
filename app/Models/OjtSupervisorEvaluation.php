<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Filled by the company supervisor through a one-time signed link, so the
 * company never needs an account in the school's system.
 */
class OjtSupervisorEvaluation extends Model
{
    protected $fillable = [
        'ojt_record_id', 'access_token', 'token_expires_at', 'submitted_at',
        'technical_competency', 'professional_behavior', 'communication', 'teamwork',
        'safety', 'engineering_analysis', 'documentation', 'professional_growth',
        'strengths', 'areas_for_improvement', 'signed_by',
    ];

    protected $casts = ['token_expires_at' => 'datetime', 'submitted_at' => 'datetime'];

    /** The eight rated dimensions, in the order the form shows them. */
    public const CRITERIA = [
        'technical_competency' => 'Technical competency',
        'professional_behavior' => 'Professional behaviour',
        'communication' => 'Communication',
        'teamwork' => 'Teamwork',
        'safety' => 'Safety',
        'engineering_analysis' => 'Engineering analysis',
        'documentation' => 'Documentation',
        'professional_growth' => 'Professional growth',
    ];

    public function ojtRecord(): BelongsTo
    {
        return $this->belongsTo(OjtRecord::class);
    }

    public static function issueToken(OjtRecord $record, int $validDays = 45): self
    {
        return static::updateOrCreate(
            ['ojt_record_id' => $record->id],
            ['access_token' => Str::random(48), 'token_expires_at' => now()->addDays($validDays)]
        );
    }

    public function isOpen(): bool
    {
        return ! $this->submitted_at && (! $this->token_expires_at || $this->token_expires_at->isFuture());
    }

    public function meanRating(): ?float
    {
        $values = collect(array_keys(self::CRITERIA))->map(fn ($k) => $this->{$k})->filter();

        return $values->isEmpty() ? null : round($values->avg(), 2);
    }
}
