<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use App\Models\Student;
use App\Services\PortfolioExportService;
use App\Services\PortfolioMatrixService;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpWord\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Exports.
 *
 *   Word - the accomplished portfolio, built by PortfolioExportService
 *   PDF  - the same document rendered from Blade
 *   Transcript - the one-page competency transcript issued at graduation
 *
 * Both portfolio exports read their grids from PortfolioMatrixService, so the
 * Word file and the PDF can never disagree about a student's attainment.
 *
 * Word is streamed rather than written to disk: nothing here needs keeping, and
 * temporary files in storage/ are a housekeeping problem nobody wants.
 */
class ExportController extends Controller
{
    public function __construct(
        protected PortfolioExportService $exporter,
        protected PortfolioMatrixService $matrix,
    ) {}

    public function word(Portfolio $portfolio): StreamedResponse
    {
        $this->authorize('export', $portfolio);

        $word = $this->exporter->buildWord($portfolio);
        $filename = $this->filename($portfolio, 'docx');

        return response()->streamDownload(function () use ($word) {
            IOFactory::createWriter($word, 'Word2007')->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    public function pdf(Portfolio $portfolio)
    {
        $this->authorize('export', $portfolio);

        $portfolio->load([
            'student.program', 'academicYear', 'sectionEntries',
            'courseEvidence.course', 'courseEvidence.plos', 'courseEvidence.evaluator',
            'technicalCompetencies.competency.category',
            'projects.designElements', 'projects.course', 'reflections', 'professionalDevelopment',
            'ojtRecord.supervisorEvaluation', 'capstoneRecord',
            'facultyEvaluations.scores', 'facultyEvaluations.evaluator',
        ]);

        $final = $portfolio->year_level >= 4 && $portfolio->status->value === 'validated';

        $pdf = Pdf::loadView('exports.portfolio', [
            'portfolio' => $portfolio,
            'matrix' => $this->matrix->matrix($portfolio->student),
            'dashboard' => $this->matrix->dashboard($portfolio->student),
            'transcript' => $this->matrix->competencyTranscript($portfolio->student),
            'transcriptStatus' => $final ? 'Final' : 'Interim — through Year '.$portfolio->year_level,
            'ordinal' => $this->ordinal($portfolio->year_level),
            'statusLine' => $this->statusLine($portfolio),
        ])->setPaper('a4');

        return $pdf->download($this->filename($portfolio, 'pdf'));
    }

    /** Section IX: the one-page competency transcript. */
    public function transcript(Student $student)
    {
        $portfolio = $student->portfolios()->latest('academic_year_id')->firstOrFail();
        $this->authorize('export', $portfolio);

        $pdf = Pdf::loadView('exports.transcript', [
            'student' => $student->load('program'),
            'dashboard' => $this->matrix->dashboard($student),
            'transcript' => $this->matrix->competencyTranscript($student),
            'target' => (float) config('portfolio.assessment.target'),
            'isFinal' => $student->standing === 'graduated',
        ])->setPaper('a4');

        return $pdf->download('competency-transcript-'.$student->student_number.'.pdf');
    }

    // --- Helpers -------------------------------------------------------------

    protected function statusLine(Portfolio $portfolio): string
    {
        $parts = [$portfolio->status->label(), $portfolio->completion_percent.'% complete'];

        if ($portfolio->year_level < 4) {
            $parts[] = 'Year 1–'.$portfolio->year_level.' on record, Year '.($portfolio->year_level + 1).'–4 pending';
        }

        if ($portfolio->is_late) {
            $parts[] = 'submitted late';
        }

        return implode(' — ', $parts);
    }

    protected function ordinal(int $n): string
    {
        return match ($n) { 1 => 'st', 2 => 'nd', 3 => 'rd', default => 'th' };
    }

    protected function filename(Portfolio $portfolio, string $ext): string
    {
        return str($portfolio->student->last_name.'-'.$portfolio->student->student_number
            .'-portfolio-'.$portfolio->academicYear->label)
            ->slug()->append('.'.$ext)->toString();
    }
}
