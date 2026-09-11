<?php

namespace App\Support\Enums;

/**
 * Lifecycle of one section within one academic year.
 *
 *   draft -> submitted -> validated
 *                      \-> returned -> submitted ...
 *
 * `late` is not a status; it is a boolean flag on the submission, because a
 * late submission still has to travel the same path.
 */
enum SubmissionStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Returned = 'returned';
    case Validated = 'validated';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Waiting for review',
            self::Returned => 'Returned for revision',
            self::Validated => 'Validated',
        };
    }

    /** Tailwind classes for the status pill. */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Draft => 'bg-slate-100 text-slate-700 ring-slate-300',
            self::Submitted => 'bg-amber-100 text-amber-800 ring-amber-300',
            self::Returned => 'bg-red-50 text-status-risk ring-red-300',
            self::Validated => 'bg-emerald-50 text-status-ontrack ring-emerald-300',
        };
    }

    /** Students may only edit a section that is not awaiting or past review. */
    public function isEditableByStudent(): bool
    {
        return in_array($this, [self::Draft, self::Returned], true);
    }
}
