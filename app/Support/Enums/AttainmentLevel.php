<?php

namespace App\Support\Enums;

/**
 * The four-level PLO attainment scale (specification Section V).
 * The integer value is what the attainment maths averages.
 */
enum AttainmentLevel: int
{
    case Introduced = 1;
    case Developing = 2;
    case Proficient = 3;
    case Advanced = 4;

    public function label(): string
    {
        return match ($this) {
            self::Introduced => 'Introduced',
            self::Developing => 'Developing',
            self::Proficient => 'Proficient',
            self::Advanced => 'Advanced / Professional',
        };
    }

    /** Single-letter code used in the PLO matrix grid (I / D / A / P). */
    public function code(): string
    {
        return match ($this) {
            self::Introduced => 'I',
            self::Developing => 'D',
            self::Proficient => 'A',
            self::Advanced => 'P',
        };
    }

    /**
     * The label used in the PLO matrix and the course-evidence "Level" column.
     *
     * The printed portfolio uses two naming schemes on purpose: the matrix
     * reads I / D / A / P (Introduced, Developing, Applied, Proficient) while
     * the attainment dashboard reports the numeric scale (1 Introduced,
     * 2 Developing, 3 Proficient, 4 Advanced). Level 3 is therefore "Applied"
     * in the matrix and "Proficient" on the dashboard. Both are kept so the
     * generated document matches the department's existing forms exactly.
     */
    public function matrixLabel(): string
    {
        return match ($this) {
            self::Introduced => 'Introduced',
            self::Developing => 'Developing',
            self::Proficient => 'Applied',
            self::Advanced => 'Proficient',
        };
    }

    /** Matrix cell as the sample prints it, e.g. "A (Y3)". */
    public function matrixCell(int $yearLevel): string
    {
        return $this->code().' (Y'.$yearLevel.')';
    }

    public function descriptor(): string
    {
        return match ($this) {
            self::Introduced => 'Demonstrates basic awareness and understanding but requires substantial guidance.',
            self::Developing => 'Performs the competency with guidance and shows partial independence.',
            self::Proficient => 'Independently applies the competency correctly in engineering activities.',
            self::Advanced => 'Integrates the competency into complex engineering problems at a professional standard.',
        };
    }

    /** Level expected of a student at the end of each year level. */
    public static function expectedForYear(int $year): self
    {
        return match ($year) {
            1 => self::Introduced,
            2 => self::Developing,
            3 => self::Proficient,
            default => self::Advanced,
        };
    }
}
