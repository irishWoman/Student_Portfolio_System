<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Program extends Model
{
    protected $fillable = ['code', 'title', 'department', 'curriculum_version', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function courses(): HasMany
    {
        return $this->hasMany(CurriculumCourse::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }
}
