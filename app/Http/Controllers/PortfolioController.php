<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Portfolio;
use App\Services\DeadlineService;
use App\Services\PloAttainmentService;
use App\Services\PortfolioCompletionService;
use App\Services\PortfolioProvisioningService;
use Illuminate\Http\Request;

/**
 * The student's own view of the portfolio: the yearly overview, one section at
 * a time, and the act of submitting for review.
 *
 * Editing happens inside Livewire components; this controller only decides
 * which portfolio and which section the student is looking at.
 */
class PortfolioController extends Controller
{
    public function __construct(
        protected PortfolioProvisioningService $provisioning,
        protected PortfolioCompletionService $completion,
        protected DeadlineService $deadlines,
    ) {}

    /** Overview for the current academic year, creating the shell if needed. */
    public function index(Request $request)
    {
        $student = $request->user()->student;
        abort_unless($student, 403, 'This account has no student record.');

        $year = AcademicYear::current();
        abort_unless($year, 503, 'No academic year has been opened yet.');

        $portfolio = $this->provisioning->ensureFor($student, $year);
        $this->completion->recomputePortfolio($portfolio);

        return view('portfolio.index', [
            'portfolio' => $portfolio->fresh(['sectionEntries', 'academicYear']),
            'student' => $student,
            'deadlines' => $this->deadlines->forStudent($student, $year),
            'nextDeadline' => $this->deadlines->nextFor($student),
            'history' => $student->portfolios()->with('academicYear')->orderByDesc('academic_year_id')->get(),
        ]);
    }

    /** One section's editor. */
    public function section(Request $request, Portfolio $portfolio, int $number)
    {
        $this->authorize('view', $portfolio);

        $definition = config('portfolio.sections')[$number] ?? abort(404);
        $entry = $portfolio->entryFor($number) ?? abort(404, 'That section is not part of this year level.');

        return view('portfolio.section', [
            'portfolio' => $portfolio,
            'entry' => $entry,
            'number' => $number,
            'definition' => $definition,
            'deadline' => $this->deadlines->forSection($portfolio->student, $number),
            // A section is editable on its own terms: the student owns the
            // portfolio (checked above), this specific entry hasn't itself
            // been submitted or validated, and no locking deadline has
            // passed. The portfolio's overall status does not gate this —
            // one section going to review does not freeze the others.
            'canEdit' => $request->user()->isRole(\App\Support\Enums\Role::Student)
                && $request->user()->student?->id === $portfolio->student_id
                && $entry->status->isEditableByStudent()
                && $this->deadlines->canEditSection($portfolio->student, $number),
        ]);
    }

    /**
     * Submit the whole portfolio for review. Sections still in draft are swept
     * along, because a portfolio is reviewed as one document.
     */
    public function submit(Request $request, Portfolio $portfolio, PloAttainmentService $attainment)
    {
        $this->authorize('submit', $portfolio);

        $percent = $this->completion->recomputePortfolio($portfolio);
        $late = $this->deadlines->isLate($portfolio->student);

        $portfolio->update([
            'status' => 'submitted',
            'submitted_at' => now(),
            'is_late' => $late,
            'completion_percent' => $percent,
        ]);

        $portfolio->sectionEntries()->where('status', 'draft')->update([
            'status' => 'submitted',
            'submitted_at' => now(),
            'is_late' => $late,
        ]);

        // Refresh the numbers so the evaluator opens a current picture.
        $attainment->recomputeForPortfolio($portfolio);

        AuditLog::record('portfolio.submitted', $portfolio, ['late' => $late, 'completion' => $percent]);

        return redirect()->route('portfolio.index')->with('status', $late
            ? 'Submitted past the deadline. It has been flagged late for your evaluator.'
            : 'Portfolio submitted for review.');
    }

    /** A read-only view of a past year. */
    public function history(Request $request, Portfolio $portfolio)
    {
        $this->authorize('view', $portfolio);

        return view('portfolio.history', [
            'portfolio' => $portfolio->load(['sectionEntries', 'academicYear']),
        ]);
    }
}
