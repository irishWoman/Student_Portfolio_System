<?php

namespace App\Models;

use App\Support\Enums\EvidenceQuality;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EvidenceFile extends Model
{
    protected $fillable = [
        'portfolio_id', 'attachable_type', 'attachable_id', 'title', 'description',
        'original_name', 'stored_path', 'mime_type', 'size_bytes', 'external_url',
        'quality_rating', 'quality_remarks', 'rated_by', 'rated_at', 'uploaded_by',
    ];

    protected $casts = ['rated_at' => 'datetime', 'quality_rating' => 'integer'];

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function plos(): BelongsToMany
    {
        return $this->belongsToMany(Plo::class, 'evidence_plo');
    }

    public function quality(): ?EvidenceQuality
    {
        return $this->quality_rating ? EvidenceQuality::from($this->quality_rating) : null;
    }

    /** Only adequate/strong evidence feeds the attainment computation. */
    public function countsTowardAttainment(): bool
    {
        return $this->quality()?->countsTowardAttainment() ?? false;
    }

    public function humanSize(): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = (float) $this->size_bytes;
        $i = 0;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return round($size, $i === 0 ? 0 : 1).' '.$units[$i];
    }
}
