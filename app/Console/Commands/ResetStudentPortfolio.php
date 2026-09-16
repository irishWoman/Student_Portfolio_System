<?php

namespace App\Console\Commands;

use App\Models\AcademicYear;
use App\Models\User;
use App\Services\PortfolioCompletionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Wipe one student's current-year portfolio back to a blank draft: every
 * section's answers cleared, every piece of submitted content (evidence
 * files, course evidence, projects, reflections, OJT/capstone records,
 * faculty evaluations...) deleted, photo removed, status and completion
 * reset to 0%.
 *
 * Built for repeatedly resetting a demo/test account between walkthroughs
 * without hand-typing a throwaway tinker script into a console each time --
 * that gets error-prone fast on a remote shell that doesn't paste cleanly.
 *
 *   php artisan portfolio:reset student@usl.edu.ph
 *   php artisan portfolio:reset student@usl.edu.ph --force
 */
class ResetStudentPortfolio extends Command
{
    protected $signature = 'portfolio:reset {email} {--force : Skip the confirmation prompt}';

    protected $description = "Wipe one student's current-year portfolio back to a blank, 0% draft";

    public function handle(PortfolioCompletionService $completion): int
    {
        $email = $this->argument('email');

        $user = User::where('email', $email)->first();

        if (! $user || ! $user->student) {
            $this->error("No student account found for {$email}.");

            return self::FAILURE;
        }

        $year = AcademicYear::current();

        if (! $year) {
            $this->error('No academic year is open.');

            return self::FAILURE;
        }

        $portfolio = $user->student->portfolioFor($year);

        if (! $portfolio) {
            $this->error("{$email} has no portfolio for {$year->label}.");

            return self::FAILURE;
        }

        $portfolio->load('student');

        if (! $this->option('force') && ! $this->confirm(
            "This permanently deletes every section answer, evidence file, and record on "
            ."{$user->student->fullName()}'s {$year->label} portfolio, and resets it to a blank "
            .'0% draft. Continue?'
        )) {
            $this->info('Cancelled.');

            return self::SUCCESS;
        }

        foreach ($portfolio->sectionEntries as $entry) {
            $entry->update([
                'payload' => null,
                'status' => 'draft',
                'completion_percent' => 0,
                'submitted_at' => null,
                'reviewed_at' => null,
                'reviewed_by' => null,
                'reviewer_notes' => null,
                'is_late' => false,
            ]);
        }

        foreach ($portfolio->evidenceFiles as $file) {
            if ($file->path) {
                Storage::disk('evidence')->delete($file->path);
            }
            $file->delete();
        }

        $portfolio->courseEvidence()->delete();
        $portfolio->technicalCompetencies()->delete();
        $portfolio->professionalDevelopment()->delete();
        $portfolio->projects()->each(fn ($project) => $project->designElements()->delete());
        $portfolio->projects()->delete();
        $portfolio->reflections()->delete();
        $portfolio->researchRecords()->delete();
        $portfolio->indirectAssessments()->delete();

        foreach ($portfolio->facultyEvaluations as $evaluation) {
            $evaluation->scores()->delete();
            $evaluation->delete();
        }

        if ($ojt = $portfolio->ojtRecord) {
            $ojt->supervisorEvaluation()->delete();
            $ojt->delete();
        }

        $portfolio->capstoneRecord?->delete();

        if ($portfolio->student->photo_path) {
            Storage::disk('public')->delete($portfolio->student->photo_path);
            $portfolio->student->update(['photo_path' => null]);
        }

        $portfolio->update([
            'status' => 'draft',
            'completion_percent' => 0,
            'submitted_at' => null,
            'is_late' => false,
            'validated_at' => null,
            'validated_by' => null,
            'overall_remarks' => null,
        ]);

        $completion->recomputePortfolio($portfolio->fresh());

        $this->info(
            "Reset {$user->student->fullName()}'s {$year->label} portfolio: "
            .$portfolio->fresh()->status->value.', '.$portfolio->fresh()->completion_percent.'% complete.'
        );

        return self::SUCCESS;
    }
}
