<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'program_id', 'student_number', 'last_name', 'first_name',
        'middle_name', 'year_level', 'section', 'photo_path', 'admitted_year',
        'adviser_id', 'standing',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function adviser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adviser_id');
    }

    public function portfolios(): HasMany
    {
        return $this->hasMany(Portfolio::class);
    }

    public function attainmentSnapshots(): HasMany
    {
        return $this->hasMany(PloAttainmentSnapshot::class);
    }

    /** "COSTALES, Irish Jane V." — the form the registrar uses. */
    public function fullName(): string
    {
        $middle = $this->middle_name ? ' '.strtoupper(substr($this->middle_name, 0, 1)).'.' : '';

        return strtoupper($this->last_name).', '.$this->first_name.$middle;
    }

    public function portfolioFor(AcademicYear $year): ?Portfolio
    {
        return $this->portfolios()->where('academic_year_id', $year->id)->first();
    }
}
