<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\CqiAction;
use App\Models\Plo;
use App\Models\Program;
use App\Models\Student;
use App\Services\CqiService;
use App\Services\PloAttainmentService;
use Illuminate\Http\Request;

/**
 * Program-level view: the dashboard that answers the accreditation question,
 * and the CQI loop that follows from it.
 */
class ChairController extends Controller
{
    public function __construct(
        protected PloAttainmentService $attainment,
        protected CqiService $cqi,
    ) {}

    /** Cohort attainment per PLO, filterable by year level. */
    public function dashboard(Request $request)
    {
        $year = $request->filled('academic_year_id')
            ? AcademicYear::findOrFail($request->integer('academic_year_id'))
            : AcademicYear::current();

        $yearLevel = $request->integer('year_level') ?: null;

        return view('chair.dashboard', [
            'year' => $year,
            'years' => AcademicYear::orderByDesc('starts_on')->get(),
            'yearLevel' => $yearLevel,
            'summary' => $year ? $this->attainment->cohortSummary($year, $yearLevel) : [],
            'target' => (float) config('portfolio.assessment.target'),
            'studentCount' => Student::where('standing', 'active')->count(),
        ]);
    }

    /** Drill into one PLO: who is below target, and where it is taught. */
    public function plo(Request $request, Plo $plo)
    {
        $year = AcademicYear::current();

        $snapshots = $plo->snapshots()
            ->with('student')
            ->where('academic_year_id', $year?->id)
            ->get()
            ->sortBy('direct_score');

        return view('chair.plo', [
            'plo' => $plo,
            'year' => $year,
            'snapshots' => $snapshots,
            'courses' => $this->cqi->coursesTeaching($plo),
            'target' => (float) config('portfolio.assessment.target'),
        ]);
    }

    /**
     * Curriculum mapping: CLOs and the PLOs they develop. The screen itself is
     * a Livewire component; this action only renders the page around it.
     */
    public function curriculum()
    {
        return view('chair.curriculum');
    }

    /** The CQI table, with a button to draft rows from current data. */
    public function cqi(Request $request)
    {
        $year = AcademicYear::current();

        return view('chair.cqi', [
            'year' => $year,
            'actions' => CqiAction::with(['plo', 'academicYear'])
                ->where('academic_year_id', $year?->id)
                ->orderBy('status')
                ->get(),
        ]);
    }

    public function generateCqi(Request $request)
    {
        $year = AcademicYear::current();
        $program = Program::where('is_active', true)->firstOrFail();

        $drafts = $this->cqi->generateDrafts($program, $year);

        return back()->with('status', $drafts->count()
            ? $drafts->count().' gap(s) drafted. Add cause, intervention and owner to each.'
            : 'Every PLO is at or above target. No new CQI rows needed.');
    }

    public function updateCqi(Request $request, CqiAction $action)
    {
        $data = $request->validate([
            'possible_cause' => ['nullable', 'string', 'max:2000'],
            'intervention' => ['nullable', 'string', 'max:2000'],
            'responsible_unit' => ['nullable', 'string', 'max:180'],
            'target_date' => ['nullable', 'date'],
            'reassessment_date' => ['nullable', 'date', 'after_or_equal:target_date'],
            'status' => ['required', 'in:open,in_progress,closed'],
            'reassessed_score' => ['nullable', 'numeric', 'between:1,4'],
        ]);

        $action->update($data);

        return back()->with('status', 'CQI action updated.');
    }

    /** Graduate Competency Profile for one student. */
    public function graduateProfile(Request $request, Student $student)
    {
        return view('chair.graduate-profile', [
            'student' => $student->load('program', 'portfolios.academicYear'),
            'cumulative' => $this->attainment->cumulativeFor($student),
            'target' => (float) config('portfolio.assessment.target'),
        ]);
    }
}
