<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Deadline;
use App\Models\DeadlineSet;
use App\Models\Portfolio;
use App\Models\Program;
use App\Models\Student;
use App\Support\Enums\DeadlineScope;
use Illuminate\Support\Collection;

/**
 * DEADLINES
 * -----------------------------------------------------------------------------
 * The portfolio closes before each academic year ends. Semester checkpoints
 * exist so the work is spread across the year instead of being written in the
 * last week; only the year-end deadline moves the portfolio to review.
 *
 * Late handling follows config('portfolio.deadlines'):
 *   - inside the grace window  -> accepted, flagged late
 *   - past the grace window    -> accepted only with an approved extension
 *   - locks_editing = true     -> hard lock at the due date, no late window
 */
class DeadlineService
{
    /** Every deadline that applies to this student, soonest first. */
    public function forStudent(Student $student, ?AcademicYear $year = null): Collection
    {
        $year ??= AcademicYear::current();

        $set = DeadlineSet::where('academic_year_id', $year?->id)
            ->where('program_id', $student->program_id)
            ->where('year_level', $student->year_level)
            ->where('is_published', true)
            ->with(['deadlines.extensions', 'deadlines.term'])
            ->first();

        return $set ? $set->deadlines : collect();
    }

    /** The next deadline the student has not passed yet. */
    public function nextFor(Student $student): ?Deadline
    {
        return $this->forStudent($student)
            ->filter(fn (Deadline $d) => $d->dueFor($student)->isFuture())
            ->sortBy(fn (Deadline $d) => $d->dueFor($student))
            ->first();
    }

    /** Deadline governing one section, if the schedule names one. */
    public function forSection(Student $student, int $sectionNumber): ?Deadline
    {
        return $this->forStudent($student)
            ->filter(fn (Deadline $d) => $d->section_number === $sectionNumber || $d->section_number === null)
            ->sortBy(fn (Deadline $d) => $d->dueFor($student))
            ->first();
    }

    /**
     * May the student still edit this section?
     *
     * A hard-locking deadline closes editing at the due date. Otherwise editing
     * stays open, and lateness is recorded rather than blocked, because a late
     * portfolio is still better evidence than a missing one.
     */
    public function canEditSection(Student $student, int $sectionNumber): bool
    {
        $deadline = $this->forSection($student, $sectionNumber);

        if (! $deadline) {
            return true;
        }

        if ($deadline->locks_editing && $deadline->isOverdueFor($student)) {
            return false;
        }

        return true;
    }

    /** Is a submission made now late, for flagging purposes? */
    public function isLate(Student $student, ?int $sectionNumber = null): bool
    {
        $deadline = $sectionNumber
            ? $this->forSection($student, $sectionNumber)
            : $this->yearEndFor($student);

        return $deadline ? $deadline->isOverdueFor($student) : false;
    }

    public function yearEndFor(Student $student): ?Deadline
    {
        return $this->forStudent($student)
            ->firstWhere('scope', DeadlineScope::AcademicYearEnd);
    }

    /**
     * Build a default schedule for one year level: a checkpoint at the end of
     * each semester for the sections marked 'semester', and one year-end
     * deadline for the whole portfolio.
     */
    public function generateDefaultSet(AcademicYear $year, Program $program, int $yearLevel): DeadlineSet
    {
        $set = DeadlineSet::firstOrCreate(
            ['academic_year_id' => $year->id, 'program_id' => $program->id, 'year_level' => $yearLevel],
            ['label' => "{$year->label} — Year {$yearLevel}", 'is_published' => true]
        );

        $firstSem = $year->terms->firstWhere('kind', 'first_semester');
        $secondSem = $year->terms->firstWhere('kind', 'second_semester');
        $grace = (int) config('portfolio.deadlines.grace_days', 7);

        // --- Semester checkpoints -------------------------------------------
        foreach (config('portfolio.sections') as $number => $definition) {
            if (($definition['checkpoint'] ?? 'year') !== 'semester') {
                continue;
            }

            if (! in_array($yearLevel, $definition['years'], true)) {
                continue;
            }

            foreach ([$firstSem, $secondSem] as $term) {
                if (! $term || ! $term->ends_on) {
                    continue;
                }

                Deadline::firstOrCreate(
                    [
                        'deadline_set_id' => $set->id,
                        'section_number' => $number,
                        'term_id' => $term->id,
                        'scope' => DeadlineScope::SemesterCheckpoint->value,
                    ],
                    [
                        'title' => $definition['title'].' — '.$term->label().' checkpoint',
                        'due_at' => $term->ends_on->copy()->subDays(7)->setTime(17, 0),
                        'grace_days' => $grace,
                        'locks_editing' => false,
                    ]
                );
            }
        }

        // --- The one that matters: before the academic year ends -------------
        $due = ($year->portfolio_due_on ?? $year->ends_on)->copy()->setTime(17, 0);

        Deadline::firstOrCreate(
            [
                'deadline_set_id' => $set->id,
                'section_number' => null,
                'scope' => DeadlineScope::AcademicYearEnd->value,
            ],
            [
                'title' => 'Complete portfolio for '.$year->label,
                'due_at' => $due,
                'grace_days' => $grace,
                'locks_editing' => (bool) config('portfolio.deadlines.lock_on_due', false),
                'instructions' => 'Submit every required section for review before the academic year closes.',
            ]
        );

        return $set->fresh('deadlines');
    }

    /** Deadlines whose reminder window opens today. */
    public function dueForReminder(): Collection
    {
        $offsets = collect(config('portfolio.deadlines.reminder_days', [14, 7, 1]));

        return Deadline::with('set.academicYear')
            ->get()
            ->filter(function (Deadline $deadline) use ($offsets) {
                $days = (int) round(now()->startOfDay()->diffInDays($deadline->due_at->startOfDay(), false));

                return $offsets->contains($days);
            });
    }

    /** Portfolios past their year-end deadline and still unsubmitted. */
    public function overduePortfolios(AcademicYear $year): Collection
    {
        return Portfolio::with('student')
            ->where('academic_year_id', $year->id)
            ->whereIn('status', ['draft', 'returned'])
            ->get()
            ->filter(fn (Portfolio $p) => $this->isLate($p->student));
    }
}
