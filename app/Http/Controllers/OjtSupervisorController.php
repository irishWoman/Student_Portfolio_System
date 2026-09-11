<?php

namespace App\Http\Controllers;

use App\Models\OjtSupervisorEvaluation;
use Illuminate\Http\Request;

/**
 * The one part of the system that works without an account.
 *
 * The company supervisor receives a link containing a single-use token, fills
 * the eight-item rating form, and never sees anything else in the system. This
 * keeps industry evaluation authentic without asking a busy engineer to
 * register for a university portal.
 */
class OjtSupervisorController extends Controller
{
    public function show(string $token)
    {
        $evaluation = OjtSupervisorEvaluation::with('ojtRecord.portfolio.student')
            ->where('access_token', $token)
            ->firstOrFail();

        abort_unless($evaluation->isOpen(), 410, 'This evaluation link has already been used or has expired.');

        return view('ojt.supervisor-form', [
            'evaluation' => $evaluation,
            'student' => $evaluation->ojtRecord->portfolio->student,
            'criteria' => OjtSupervisorEvaluation::CRITERIA,
        ]);
    }

    public function store(Request $request, string $token)
    {
        $evaluation = OjtSupervisorEvaluation::where('access_token', $token)->firstOrFail();
        abort_unless($evaluation->isOpen(), 410);

        $rules = ['strengths' => ['nullable', 'string', 'max:1500'],
            'areas_for_improvement' => ['nullable', 'string', 'max:1500'],
            'signed_by' => ['required', 'string', 'max:180']];

        foreach (array_keys(OjtSupervisorEvaluation::CRITERIA) as $criterion) {
            $rules[$criterion] = ['required', 'integer', 'between:1,4'];
        }

        $evaluation->update($request->validate($rules) + ['submitted_at' => now()]);

        return view('ojt.supervisor-thanks', ['student' => $evaluation->ojtRecord->portfolio->student]);
    }
}
