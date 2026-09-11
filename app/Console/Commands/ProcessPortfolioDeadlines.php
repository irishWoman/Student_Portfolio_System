<?php

namespace App\Console\Commands;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Services\DeadlineService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * Daily deadline housekeeping:
 *   1. send reminders at the offsets in config('portfolio.deadlines.reminder_days')
 *   2. flag portfolios that have passed the year-end deadline unsubmitted
 *
 * Run by the scheduler, or manually: `php artisan portfolio:process-deadlines`
 */
class ProcessPortfolioDeadlines extends Command
{
    protected $signature = 'portfolio:process-deadlines {--dry-run : Report without notifying}';

    protected $description = 'Send portfolio deadline reminders and flag overdue portfolios';

    public function handle(DeadlineService $deadlines): int
    {
        $year = AcademicYear::current();

        if (! $year) {
            $this->warn('No current academic year. Open one first.');

            return self::FAILURE;
        }

        // --- 1. Reminders -----------------------------------------------------
        $due = $deadlines->dueForReminder();
        $this->info("Deadlines inside a reminder window: {$due->count()}");

        foreach ($due as $deadline) {
            $students = Student::where('program_id', $deadline->set->program_id)
                ->where('year_level', $deadline->set->year_level)
                ->where('standing', 'active')
                ->with('user')
                ->get();

            $this->line("  {$deadline->title} — {$students->count()} student(s)");

            if ($this->option('dry-run')) {
                continue;
            }

            foreach ($students as $student) {
                if (! $student->user) {
                    continue;
                }

                Notification::send(
                    $student->user,
                    new \App\Notifications\DeadlineApproaching($deadline)
                );
            }
        }

        // --- 2. Overdue flags -------------------------------------------------
        $overdue = $deadlines->overduePortfolios($year);
        $this->info("Overdue portfolios: {$overdue->count()}");

        if (! $this->option('dry-run')) {
            foreach ($overdue as $portfolio) {
                $portfolio->update(['is_late' => true]);
            }
        }

        return self::SUCCESS;
    }
}
