<?php

namespace App\Support\Enums;

/**
 * The four account types. Kept as an enum (not free strings) so a typo in a
 * middleware or a Blade check fails loudly instead of silently denying access.
 */
enum Role: string
{
    case Student = 'student';
    case Faculty = 'faculty';
    case Chair = 'chair';       // program chair / OBE coordinator
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Student => 'Student',
            self::Faculty => 'Faculty evaluator',
            self::Chair => 'Program chair',
            self::Admin => 'Administrator',
        };
    }

    /** Landing route after login. */
    public function home(): string
    {
        return match ($this) {
            self::Student => 'portfolio.index',
            self::Faculty => 'faculty.queue',
            self::Chair => 'chair.dashboard',
            self::Admin => 'admin.deadlines',
        };
    }

    public static function values(): array
    {
        return array_map(fn (self $r) => $r->value, self::cases());
    }
}
