<?php

namespace App\Models;

use App\Support\Enums\SubmissionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One student's portfolio for one academic year. Everything else in the system
 * hangs off this row, which is what makes "one cycle per year" enforceable.
 */
class Portfolio extends Model
{
    protected $fillable = [
        'student_id', 'academic_year_id', 'year_level', 'status',
        'completion_percent', 'submitted_at', 'validated_at', 'validated_by',
        'is_late', 'overall_remarks',
    ];

    protected $casts = [
        'status' => SubmissionStatus::class,
        'submitted_at' => 'datetime',
        'validated_at' => 'datetime',
        'is_late' => 'boolean',
    ];

    // --- Relationships -------------------------------------------------------

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function sectionEntries(): HasMany
    {
        return $this->hasMany(SectionEntry::class)->orderBy('section_number');
    }

    public function evidenceFiles(): HasMany
    {
        return $this->hasMany(EvidenceFile::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }

    public function courseEvidence(): HasMany
    {
        return $this->hasMany(CourseEvidenceRecord::class);
    }

    public function technicalCompetencies(): HasMany
    {
        return $this->hasMany(TechnicalCompetencyRecord::class);
    }

    public function professionalDevelopment(): HasMany
    {
        return $this->hasMany(ProfessionalDevelopmentRecord::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function reflections(): HasMany
    {
        return $this->hasMany(Reflection::class);
    }

    public function researchRecords(): HasMany
    {
        return $this->hasMany(ResearchRecord::class);
    }

    public function ojtRecord()
    {
        return $this->hasOne(OjtRecord::class);
    }

    public function capstoneRecord()
    {
        return $this->hasOne(CapstoneRecord::class);
    }

    public function facultyEvaluations(): HasMany
    {
        return $this->hasMany(FacultyEvaluation::class);
    }

    /** Indirect evidence, kept apart from direct scores on purpose. */
    public function indirectAssessments(): HasMany
    {
        return $this->hasMany(IndirectAssessment::class);
    }

    public function attainmentSnapshots()
    {
        return $this->hasMany(PloAttainmentSnapshot::class, 'academic_year_id', 'academic_year_id')
            ->where('student_id', $this->student_id);
    }

    // --- Helpers -------------------------------------------------------------

    /** Section numbers required at this portfolio's year level. */
    public function requiredSections(): array
    {
        return collect(config('portfolio.sections'))
            ->filter(fn ($s, $n) => in_array($this->year_level, $s['years'], true))
            ->keys()
            ->all();
    }

    public function entryFor(int $sectionNumber): ?SectionEntry
    {
        return $this->sectionEntries->firstWhere('section_number', $sectionNumber);
    }

    public function isEditableByStudent(): bool
    {
        return $this->status->isEditableByStudent();
    }
}
