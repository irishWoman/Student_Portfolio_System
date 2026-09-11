<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Portfolio;
use App\Services\PloAttainmentService;
use Illuminate\Http\Request;

/**
 * The evaluator's side: a queue of portfolios waiting for review, and the
 * review screen itself.
 */
class FacultyController extends Controller
{
    /** Portfolios submitted and not yet validated, advisees first. */
    public function queue(Request $request)
    {
        $year = AcademicYear::current();

        $portfolios = Portfolio::with(['student.user', 'student.adviser', 'academicYear'])
            ->where('academic_year_id', $year?->id)
            ->whereIn('status', ['submitted', 'returned'])
            ->get()
            ->sortByDesc(fn (Portfolio $p) => $p->student->adviser_id === $request->user()->id)
            ->values();

        return view('faculty.queue', [
            'portfolios' => $portfolios,
            'year' => $year,
        ]);
    }

    /** Full review screen for one portfolio. */
    public function review(Request $request, Portfolio $portfolio, PloAttainmentService $attainment)
    {
        $this->authorize('evaluate', $portfolio);

        $portfolio->load([
            'student.program', 'academicYear', 'sectionEntries',
            'courseEvidence.course', 'courseEvidence.plos',
            'evidenceFiles.plos', 'projects.designElements', 'reflections',
            'technicalCompetencies.competency', 'professionalDevelopment',
            'ojtRecord.supervisorEvaluation', 'capstoneRecord',
        ]);

        return view('faculty.review', [
            'portfolio' => $portfolio,
            'snapshots' => $attainment->recomputeForPortfolio($portfolio),
        ]);
    }

    /** Validate the portfolio, or send it back with notes. */
    public function decide(Request $request, Portfolio $portfolio, PloAttainmentService $attainment)
    {
        $this->authorize('evaluate', $portfolio);

        $data = $request->validate([
            'decision' => ['required', 'in:validated,returned'],
            'overall_remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        // A return without a reason is not actionable for the student.
        if ($data['decision'] === 'returned' && blank($data['overall_remarks'])) {
            return back()->withErrors(['overall_remarks' => 'Say what needs fixing before returning the portfolio.']);
        }

        $portfolio->update([
            'status' => $data['decision'],
            'overall_remarks' => $data['overall_remarks'] ?? null,
            'validated_at' => $data['decision'] === 'validated' ? now() : null,
            'validated_by' => $data['decision'] === 'validated' ? $request->user()->id : null,
        ]);

        $portfolio->sectionEntries()->update([
            'status' => $data['decision'],
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()->id,
        ]);

        $attainment->recomputeForPortfolio($portfolio);

        \App\Models\AuditLog::record('portfolio.'.$data['decision'], $portfolio);

        return redirect()->route('faculty.queue')->with('status', $data['decision'] === 'validated'
            ? 'Portfolio validated.'
            : 'Portfolio returned to the student.');
    }
}
