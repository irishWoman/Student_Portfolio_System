<?php

namespace App\Support\Enums;

/**
 * A deadline either closes a semester checkpoint or closes the whole academic
 * year. Year-end deadlines are the ones that move a portfolio to review.
 */
enum DeadlineScope: string
{
    case SemesterCheckpoint = 'semester_checkpoint';
    case AcademicYearEnd = 'academic_year_end';

    public function label(): string
    {
        return match ($this) {
            self::SemesterCheckpoint => 'Semester checkpoint',
            self::AcademicYearEnd => 'End of academic year',
        };
    }
}
