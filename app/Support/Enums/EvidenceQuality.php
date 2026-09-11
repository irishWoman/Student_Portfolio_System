<?php

namespace App\Support\Enums;

/**
 * Evidence Quality Scale (specification Section VI). Rated by the evaluator,
 * not the student. Anything below config('portfolio.assessment.min_evidence_quality')
 * stays in the portfolio but is excluded from attainment maths.
 */
enum EvidenceQuality: int
{
    case Insufficient = 1;
    case Limited = 2;
    case Adequate = 3;
    case Strong = 4;

    public function label(): string
    {
        return match ($this) {
            self::Insufficient => 'Insufficient evidence',
            self::Limited => 'Limited evidence',
            self::Adequate => 'Adequate evidence',
            self::Strong => 'Strong evidence',
        };
    }

    public function descriptor(): string
    {
        return match ($this) {
            self::Insufficient => 'Does not adequately demonstrate attainment.',
            self::Limited => 'Indirect or incomplete evidence.',
            self::Adequate => 'Direct evidence of the PLO with minor limitations.',
            self::Strong => 'Direct, authentic, recent and substantial evidence of the PLO.',
        };
    }

    public function countsTowardAttainment(): bool
    {
        return $this->value >= (int) config('portfolio.assessment.min_evidence_quality', 3);
    }
}
