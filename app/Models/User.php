<?php

namespace App\Models;

use App\Support\Enums\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = ['name', 'email', 'employee_no', 'title', 'password', 'is_active'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    // --- Relationships -------------------------------------------------------

    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    /** Students this user advises (faculty accounts). */
    public function advisees(): HasMany
    {
        return $this->hasMany(Student::class, 'adviser_id');
    }

    public function facultyEvaluations(): HasMany
    {
        return $this->hasMany(FacultyEvaluation::class, 'evaluator_id');
    }

    // --- Helpers -------------------------------------------------------------

    public function isRole(Role $role): bool
    {
        return $this->hasRole($role->value);
    }

    /** Name with academic title, e.g. "Engr. P. Villanueva". */
    public function displayName(): string
    {
        return trim(($this->title ? $this->title.' ' : '').$this->name);
    }

    /** Where this account lands after logging in. */
    public function homeRoute(): string
    {
        foreach ([Role::Admin, Role::Chair, Role::Faculty, Role::Student] as $role) {
            if ($this->isRole($role)) {
                return $role->home();
            }
        }

        return 'login';
    }
}
