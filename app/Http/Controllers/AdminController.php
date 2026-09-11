<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Deadline;
use App\Models\DeadlineSet;
use App\Models\Program;
use App\Services\DeadlineService;
use App\Services\PortfolioProvisioningService;
use Illuminate\Http\Request;

/**
 * Administration: the academic calendar, the deadline schedule, and opening a
 * new academic year for every student in one action.
 */
class AdminController extends Controller
{
    public function __construct(
        protected DeadlineService $deadlines,
        protected PortfolioProvisioningService $provisioning,
    ) {}

    public function deadlines(Request $request)
    {
        $year = $request->filled('academic_year_id')
            ? AcademicYear::findOrFail($request->integer('academic_year_id'))
            : AcademicYear::current();

        return view('admin.deadlines', [
            'year' => $year,
            'years' => AcademicYear::orderByDesc('starts_on')->get(),
            'sets' => DeadlineSet::with('deadlines.term')
                ->where('academic_year_id', $year?->id)
                ->orderBy('year_level')
                ->get(),
        ]);
    }

    /** Build the default schedule for one year level from the calendar. */
    public function generateDeadlines(Request $request)
    {
        $data = $request->validate([
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'year_level' => ['required', 'integer', 'between:1,4'],
        ]);

        $year = AcademicYear::with('terms')->findOrFail($data['academic_year_id']);
        $program = Program::where('is_active', true)->firstOrFail();

        $this->deadlines->generateDefaultSet($year, $program, $data['year_level']);

        return back()->with('status', "Schedule generated for Year {$data['year_level']}.");
    }

    public function updateDeadline(Request $request, Deadline $deadline)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'due_at' => ['required', 'date'],
            'grace_days' => ['required', 'integer', 'between:0,60'],
            'locks_editing' => ['boolean'],
            'instructions' => ['nullable', 'string', 'max:1000'],
        ]);

        $deadline->update($data + ['locks_editing' => $request->boolean('locks_editing')]);

        return back()->with('status', 'Deadline updated.');
    }

    public function destroyDeadline(Deadline $deadline)
    {
        $deadline->delete();

        return back()->with('status', 'Deadline removed.');
    }

    /**
     * Open a new academic year: mark it current and create every student's
     * portfolio shell so nobody starts the year with an empty screen.
     */
    public function openYear(Request $request)
    {
        $data = $request->validate(['academic_year_id' => ['required', 'exists:academic_years,id']]);

        $year = AcademicYear::findOrFail($data['academic_year_id']);

        AcademicYear::where('is_current', true)->update(['is_current' => false]);
        $year->update(['is_current' => true]);

        $count = $this->provisioning->openYearForAllStudents($year);

        return back()->with('status', "{$year->label} is now the current year. {$count} portfolios opened.");
    }
}
