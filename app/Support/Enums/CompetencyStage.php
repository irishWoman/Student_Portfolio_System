<?php

namespace App\Support\Enums;

/**
 * Technical competency ladder used in Section 5. Distinct from AttainmentLevel:
 * this describes depth of a *skill*, not attainment of a *PLO*.
 */
enum CompetencyStage: string
{
    case Knowledge = 'knowledge';
    case Skill = 'skill';
    case Application = 'application';
    case Integration = 'integration';
    case ProfessionalPractice = 'professional_practice';

    public function label(): string
    {
        return config('portfolio.competency_stages')[$this->value] ?? ucfirst($this->value);
    }

    /** Ordinal position, used to sort and to draw the progress bar. */
    public function rank(): int
    {
        return match ($this) {
            self::Knowledge => 1,
            self::Skill => 2,
            self::Application => 3,
            self::Integration => 4,
            self::ProfessionalPractice => 5,
        };
    }
}
