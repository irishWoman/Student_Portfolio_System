<?php

namespace App\Services;

use App\Models\CapstoneRecord;
use App\Models\Portfolio;
use App\Support\Enums\AttainmentLevel;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\Style\Language;

/**
 * WORD EXPORT
 * =============================================================================
 * Produces the accomplished portfolio document.
 *
 * The structure here deliberately mirrors the department's printed sample
 * exactly — same section order, same table columns, same legends and footnotes,
 * same two-scheme level naming (I/D/A/P in the matrix, 1-4 numeric on the
 * dashboard). Anyone who has read the paper form should be able to read this
 * output without being told it was generated.
 *
 * Column widths are in twips (1/20 pt); the totals below come to roughly the
 * printable width of A4 with the margins set in buildWord().
 * =============================================================================
 */
class PortfolioExportService
{
    /** Reusable cell styles, so every table in the document matches. */
    protected array $headerCell = ['bgColor' => '14375E'];

    protected array $headerText = ['bold' => true, 'color' => 'FFFFFF', 'size' => 9];

    protected array $labelText = ['bold' => true, 'size' => 10];

    protected array $bodyText = ['size' => 10];

    protected array $noteText = ['italic' => true, 'size' => 9, 'color' => '4B5563'];

    public function __construct(
        protected PloAttainmentService $attainment,
        protected PortfolioMatrixService $matrix,
    ) {}

    public function buildWord(Portfolio $portfolio): PhpWord
    {
        $portfolio->loadMissing([
            'student.program', 'academicYear', 'sectionEntries',
            'courseEvidence.course', 'courseEvidence.plos', 'courseEvidence.evaluator',
            'technicalCompetencies.competency.category',
            'projects.designElements', 'projects.course', 'reflections',
            'professionalDevelopment', 'ojtRecord.supervisorEvaluation', 'capstoneRecord',
            'facultyEvaluations.scores', 'facultyEvaluations.evaluator',
        ]);

        $word = new PhpWord;
        $word->getSettings()->setThemeFontLang(new Language(Language::EN_US));
        $word->setDefaultFontName('Calibri');
        $word->setDefaultFontSize(10);

        $word->addTitleStyle(1, ['size' => 14, 'bold' => true, 'color' => '14375E'], ['spaceBefore' => 280, 'spaceAfter' => 120]);
        $word->addTitleStyle(2, ['size' => 11, 'bold' => true, 'color' => '1B4677'], ['spaceBefore' => 200, 'spaceAfter' => 80]);

        $word->addTableStyle('grid', [
            'borderColor' => 'C9D2DE',
            'borderSize' => 6,
            'cellMargin' => 70,
            'alignment' => Jc::CENTER,
        ]);

        // Borderless: the cover page's label/value rows read as blank lines on
        // a form, not as a data table.
        $word->addTableStyle('plain', [
            'borderSize' => 0,
            'cellMargin' => 40,
        ]);

        $section = $word->addSection([
            'marginTop' => 1000, 'marginBottom' => 1000,
            'marginLeft' => 1000, 'marginRight' => 1000,
        ]);

        $this->addCover($section, $portfolio);
        $this->addStudentProfile($section, $portfolio);
        $this->addDevelopmentPlan($section, $portfolio);
        $this->addPloMatrix($section, $portfolio);
        $this->addCourseEvidence($section, $portfolio);
        $this->addTechnicalCompetency($section, $portfolio);
        $this->addDesignPortfolio($section, $portfolio);
        $this->addResearchSection($section, $portfolio);
        $this->addProfessionalCompetency($section, $portfolio);
        $this->addOjt($section, $portfolio);
        $this->addCapstone($section, $portfolio);
        $this->addProfessionalDevelopment($section, $portfolio);
        $this->addReflections($section, $portfolio);
        $this->addFacultyAssessment($section, $portfolio);
        $this->addAttainmentDashboard($section, $portfolio);
        $this->addCompetencyTranscript($section, $portfolio);
        $this->addFooter($section);

        return $word;
    }

    // -------------------------------------------------------------------------
    // Cover
    // -------------------------------------------------------------------------

    protected function addCover(Section $section, Portfolio $portfolio): void
    {
        $student = $portfolio->student;
        $profile = $portfolio->entryFor(1);
        $dob = $profile?->answer('date_of_birth');

        // --- Letterhead --------------------------------------------------------
        $logo = public_path('images/logo-uslt.png');
        if (is_file($logo)) {
            $section->addImage($logo, ['width' => 60, 'height' => 60, 'alignment' => Jc::CENTER]);
        }
        $section->addText(strtoupper(config('app.institution.name')), ['bold' => true, 'size' => 14, 'color' => '14375E'], ['alignment' => Jc::CENTER]);
        $section->addText(strtoupper(config('app.institution.unit')), ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER, 'spaceBefore' => 120]);
        $section->addText(strtoupper(config('app.institution.department')), ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);
        $section->addText(strtoupper($student->program->title), ['size' => 9], ['alignment' => Jc::CENTER, 'spaceBefore' => 120]);
        $section->addText('STUDENT PORTFOLIO', ['bold' => true, 'size' => 11], ['alignment' => Jc::CENTER, 'spaceAfter' => 200]);

        // --- Identification block: form fields beside the 2x2 photo -----------
        $idTable = $section->addTable('plain');
        $idTable->addRow();

        $infoCell = $idTable->addCell(6800);
        $infoTable = $infoCell->addTable('plain');
        foreach ([
            'Name' => $student->fullName(),
            'Address' => $profile?->answer('address') ?: '—',
            'E-mail Address' => $profile?->answer('email_address') ?: '—',
            'Contact Number/s' => $profile?->answer('contact_number') ?: '—',
            'Term/Year Started' => $profile?->answer('term_year_started') ?: '—',
            'Year Level' => $portfolio->year_level.$this->ordinal($portfolio->year_level).' Year ('.$this->stageName($portfolio->year_level).' Level)',
        ] as $label => $value) {
            $this->labelRow($infoTable, $label, (string) $value);
        }

        $photoCell = $idTable->addCell(2600, ['vAlign' => 'center']);
        $photoPath = $student->photoAbsolutePath();
        if ($photoPath && is_file($photoPath)) {
            $photoCell->addImage($photoPath, ['width' => 105, 'height' => 105, 'alignment' => Jc::CENTER]);
        } else {
            $photoCell->addText('2x2 ID Photo', ['size' => 8, 'color' => '6B7280'], ['alignment' => Jc::CENTER]);
        }

        // --- Personal data -------------------------------------------------------
        $this->bandRow($section, 'Personal Data');
        $personalTable = $section->addTable('plain');
        foreach ([
            'Gender' => $profile?->answer('gender') ?: '—',
            'Date of Birth' => $dob ? \Illuminate\Support\Carbon::parse($dob)->format('F j, Y') : '—',
            'Birth Place' => $profile?->answer('birth_place') ?: '—',
            'Religion' => $profile?->answer('religion') ?: '—',
            'Civil Status' => $profile?->answer('civil_status') ?: '—',
            'Citizenship' => $profile?->answer('citizenship') ?: '—',
            'Parents' => $profile?->answer('parents') ?: '—',
        ] as $label => $value) {
            $this->labelRow($personalTable, $label, (string) $value);
        }

        // --- Educational background -----------------------------------------------
        $this->bandRow($section, 'Educational Background');
        $eduTable = $section->addTable('plain');
        foreach ([
            'Kinder 1-2' => $profile?->answer('kinder_school') ?: '—',
            'Grade 1-6' => $profile?->answer('elementary_school') ?: '—',
            'Grade 7-10' => $profile?->answer('junior_high_school') ?: '—',
            'Grade 11-12' => $profile?->answer('senior_high_school') ?: '—',
            'Tertiary' => $profile?->answer('tertiary_school') ?: '—',
        ] as $label => $value) {
            $this->labelRow($eduTable, $label, (string) $value);
        }
        $section->addText('(write program/school before taking up BSCpE at USLT)', $this->noteText);

        // --- Personal reflection --------------------------------------------------
        $this->bandRow($section, 'Personal Reflection');
        $section->addText($profile?->answer('personal_reflection') ?: '—', $this->bodyText, ['spaceAfter' => 160]);

        // --- Record keeping --------------------------------------------------------
        $recordTable = $section->addTable('plain');
        $this->labelRow($recordTable, 'Academic Year', $portfolio->academicYear->label);
        $this->labelRow($recordTable, 'Portfolio Status', $this->statusLine($portfolio));

        $section->addTextBreak(1);
        $section->addText(
            'Generated from the Computer Engineering Student Development Portfolio system on '
            .now()->format('F j, Y').'. Attainment figures are computed from validated direct assessment evidence only.',
            $this->noteText
        );

        $section->addPageBreak();
    }

    /** The printed form's orange section band, e.g. "Personal Data". */
    protected function bandRow(Section $section, string $label): void
    {
        $table = $section->addTable(['borderSize' => 6, 'borderColor' => 'F0A500', 'cellMargin' => 60]);
        $table->addRow();
        $table->addCell(9400, ['bgColor' => 'FDF3DC'])
            ->addText(strtoupper($label), ['bold' => true, 'size' => 9, 'color' => '14375E']);
    }

    // -------------------------------------------------------------------------
    // Section 1 and 2
    // -------------------------------------------------------------------------

    protected function addStudentProfile(Section $section, Portfolio $portfolio): void
    {
        $entry = $portfolio->entryFor(1);
        $student = $portfolio->student;

        $section->addTitle('Section 1 — Student Profile', 1);

        $table = $section->addTable('grid');
        $this->headerRow($table, ['Field', 'Entry'], [3400, 6000]);

        // Registrar facts first, exactly as the printed form lists them, then
        // the student's own answers.
        $this->labelRow($table, 'Student Name', $student->fullName());
        $this->labelRow($table, 'Student Number', $student->student_number);
        $this->labelRow($table, 'Program', $student->program->title);
        $this->labelRow($table, 'Year Level', $portfolio->year_level.$this->ordinal($portfolio->year_level).' Year');
        $this->labelRow($table, 'Academic Year', $portfolio->academicYear->label);

        foreach ($entry?->definition()['fields'] ?? [] as $field) {
            if (($field['type'] ?? 'text') === 'heading') {
                continue;
            }

            $this->labelRow($table, $field['label'], (string) ($entry->answer($field['name']) ?: '—'));
        }
    }

    protected function addDevelopmentPlan(Section $section, Portfolio $portfolio): void
    {
        $entry = $portfolio->entryFor(2);

        if (! $entry) {
            return;
        }

        $section->addTitle('Section 2 — Personal Development Plan (AY '.$portfolio->academicYear->label
            .', Year '.$portfolio->year_level.' entry)', 1);

        $table = $section->addTable('grid');
        $this->headerRow($table, ['Item', 'Entry'], [3400, 6000]);

        foreach ($entry->definition()['fields'] as $field) {
            $this->labelRow($table, $field['label'], (string) ($entry->answer($field['name']) ?: '—'));
        }
    }

    // -------------------------------------------------------------------------
    // Section 3 — the matrix
    // -------------------------------------------------------------------------

    protected function addPloMatrix(Section $section, Portfolio $portfolio): void
    {
        $section->addTitle('Section 3 — PLO and Competency Matrix (as of end of Year '.$portfolio->year_level.')', 1);

        $section->addText(
            'Legend: I = Introduced, D = Developing, A = Applied, P = Proficient, — = not yet targeted this year',
            ['size' => 9], ['spaceAfter' => 120]
        );

        $table = $section->addTable('grid');
        $this->headerRow(
            $table,
            ['PLO', 'Competency', 'Year 1', 'Year 2', 'Year 3', 'Year 4', 'Representative Evidence'],
            [700, 2100, 750, 750, 750, 750, 3600]
        );

        foreach ($this->matrix->matrix($portfolio->student) as $number => $row) {
            $table->addRow();
            $table->addCell(700)->addText('PLO '.$number, $this->labelText);
            $table->addCell(2100)->addText($row['plo']->title, $this->bodyText);

            foreach ([1, 2, 3, 4] as $year) {
                $table->addCell(750)->addText($row['years'][$year], $this->bodyText, ['alignment' => Jc::CENTER]);
            }

            $table->addCell(3600)->addText($row['evidence'], $this->bodyText);
        }

        if ($portfolio->year_level < 4) {
            $section->addText(
                'Year '.($portfolio->year_level + 1).' onward is marked "Pending": that evidence has not been produced yet. '
                .'This matrix is updated as each year\'s evidence is validated.',
                $this->noteText
            );
        }
    }

    // -------------------------------------------------------------------------
    // Section 4
    // -------------------------------------------------------------------------

    protected function addCourseEvidence(Section $section, Portfolio $portfolio): void
    {
        if ($portfolio->courseEvidence->isEmpty()) {
            return;
        }

        $section->addTitle('Section 4 — Course-Based Evidence (selected entries)', 1);

        $table = $section->addTable('grid');
        $this->headerRow(
            $table,
            ['Course', 'CLO', 'PLO', 'Assessment', 'Evidence', 'Score', 'Level', 'Evaluator / Date'],
            [1300, 1900, 700, 1200, 1600, 700, 900, 1100]
        );

        foreach ($portfolio->courseEvidence as $record) {
            $level = $record->validated_level
                ? AttainmentLevel::from($record->validated_level)->matrixLabel()
                : 'Pending';

            $evaluator = $record->evaluator
                ? $record->evaluator->displayName().' / '.($record->evaluated_on?->format('M Y') ?? '')
                : 'Not yet validated';

            $table->addRow();
            $table->addCell(1300)->addText($this->courseLabel($record), $this->bodyText);
            $table->addCell(1900)->addText($record->clo_statement ?: ($record->learningOutcome?->statement ?? '—'), $this->bodyText);
            $table->addCell(700)->addText($record->plos->map(fn ($p) => $p->number)->implode(','), $this->bodyText);
            $table->addCell(1200)->addText($record->assessment_activity, $this->bodyText);
            $table->addCell(1600)->addText($record->output_title, $this->bodyText);
            $table->addCell(700)->addText($record->score ? $record->score.'/'.$record->score_max : '—', $this->bodyText);
            $table->addCell(900)->addText($level, $this->bodyText);
            $table->addCell(1100)->addText($evaluator, $this->bodyText);
        }
    }

    /** "COME 1134 Embedded System (Y4)" — course, title and the year taken. */
    protected function courseLabel($record): string
    {
        $course = $record->course;

        if (! $course) {
            return '—';
        }

        return $course->code.' '.$course->title.' (Y'.$course->year_level.')';
    }

    // -------------------------------------------------------------------------
    // Section 5
    // -------------------------------------------------------------------------

    protected function addTechnicalCompetency(Section $section, Portfolio $portfolio): void
    {
        if ($portfolio->technicalCompetencies->isEmpty()) {
            return;
        }

        $section->addTitle('Section 5 — Technical Competency Portfolio (current standing)', 1);

        $table = $section->addTable('grid');
        $this->headerRow($table, ['Category', 'Competency', 'Stage Reached', 'Evidence'], [2000, 2400, 1800, 3200]);

        foreach ($portfolio->technicalCompetencies->sortBy(fn ($r) => $r->competency->category->sort_order) as $record) {
            $table->addRow();
            $table->addCell(2000)->addText($record->competency->category->name, $this->bodyText);
            $table->addCell(2400)->addText($record->competency->name, $this->bodyText);
            $table->addCell(1800)->addText($record->effectiveStage()?->label() ?? '—', $this->bodyText);
            $table->addCell(3200)->addText($record->evidence_note ?: '—', $this->bodyText);
        }

        $section->addText(
            'Stage scale: Knowledge → Skill → Application → Integration → Professional Practice. '
            .'"Integration" and "Professional Practice" are expected to be reached in Year 4 through the capstone and OJT.',
            $this->noteText
        );
    }

    // -------------------------------------------------------------------------
    // Section 6
    // -------------------------------------------------------------------------

    protected function addDesignPortfolio(Section $section, Portfolio $portfolio): void
    {
        $projects = $portfolio->projects->whereIn('kind', ['design_project', 'course_project', 'capstone']);

        if ($projects->isEmpty()) {
            return;
        }

        $section->addTitle('Section 6 — Engineering Design Portfolio', 1);

        foreach ($projects as $project) {
            $heading = 'Project: '.$project->title;
            $context = collect([
                $project->course?->code,
                'Year '.$portfolio->year_level,
                $project->role,
            ])->filter()->implode(', ');

            $section->addTitle($heading.($context ? ' ('.$context.')' : ''), 2);

            $table = $section->addTable('grid');
            $this->headerRow($table, ['#', 'Design Element', 'Entry'], [500, 2300, 6600]);

            foreach ($project->designElements as $element) {
                $table->addRow();
                $table->addCell(500)->addText((string) $element->position, $this->bodyText, ['alignment' => Jc::CENTER]);
                $table->addCell(2300)->addText($element->label(), $this->labelText);
                $table->addCell(6600)->addText($element->content ?: '—', $this->bodyText);
            }
        }
    }

    // -------------------------------------------------------------------------
    // Sections 7 and 8 (field-driven)
    // -------------------------------------------------------------------------

    protected function addResearchSection(Section $section, Portfolio $portfolio): void
    {
        $this->addFieldSection($section, $portfolio, 7, 'Section 7 — Research and Investigation Portfolio');
    }

    protected function addProfessionalCompetency(Section $section, Portfolio $portfolio): void
    {
        $this->addFieldSection($section, $portfolio, 8, 'Section 8 — Professional Competency Portfolio');
    }

    /** Renders any config-driven section as a two-column table. */
    protected function addFieldSection(Section $section, Portfolio $portfolio, int $number, string $title): void
    {
        $entry = $portfolio->entryFor($number);

        if (! $entry || blank(array_filter($entry->payload ?? []))) {
            return;
        }

        $section->addTitle($title, 1);

        $table = $section->addTable('grid');
        $this->headerRow($table, ['Item', 'Entry'], [3400, 6000]);

        foreach ($entry->definition()['fields'] as $field) {
            $this->labelRow($table, $field['label'], (string) ($entry->answer($field['name']) ?: '—'));
        }
    }

    // -------------------------------------------------------------------------
    // Sections 9, 10, 11
    // -------------------------------------------------------------------------

    protected function addOjt(Section $section, Portfolio $portfolio): void
    {
        $ojt = $portfolio->ojtRecord;

        if (! $ojt) {
            return;
        }

        $section->addTitle('Section 9 — Industry / OJT Portfolio', 1);

        $table = $section->addTable('grid');
        $this->headerRow($table, ['Item', 'Entry'], [3400, 6000]);

        $this->labelRow($table, 'Company', $ojt->company_name.($ojt->industry ? ' ('.$ojt->industry.')' : ''));
        $this->labelRow($table, 'Address', $ojt->company_address ?: '—');
        $this->labelRow($table, 'Supervisor', trim($ojt->supervisor_name.' — '.$ojt->supervisor_position, ' —'));
        $this->labelRow($table, 'Period', trim(($ojt->started_on?->format('M j, Y') ?? '').' to '.($ojt->ended_on?->format('M j, Y') ?? '')));
        $this->labelRow($table, 'Hours completed', $ojt->completed_hours.' of '.$ojt->required_hours.' required');
        $this->labelRow($table, 'Objectives', $ojt->objectives ?: '—');
        $this->labelRow($table, 'Responsibilities', $ojt->responsibilities ?: '—');
        $this->labelRow($table, 'Work outputs', $ojt->work_outputs ?: '—');
        $this->labelRow($table, 'Student reflection', $ojt->student_reflection ?: '—');

        $evaluation = $ojt->supervisorEvaluation;

        if (! $evaluation?->submitted_at) {
            $section->addText('Industry supervisor evaluation has not been received.', $this->noteText);

            return;
        }

        $section->addTitle('Industry Supervisor Evaluation', 2);

        $ratings = $section->addTable('grid');
        $this->headerRow($ratings, ['Area', '1', '2', '3', '4'], [5400, 1000, 1000, 1000, 1000]);

        foreach (\App\Models\OjtSupervisorEvaluation::CRITERIA as $key => $label) {
            $this->tickRow($ratings, $label, (int) $evaluation->{$key}, [5400, 1000, 1000, 1000, 1000]);
        }

        $section->addText('Strengths: '.($evaluation->strengths ?: '—'), $this->bodyText);
        $section->addText('Areas for improvement: '.($evaluation->areas_for_improvement ?: '—'), $this->bodyText);
        $section->addText(
            'Signed: '.$evaluation->signed_by.'     Date: '.$evaluation->submitted_at->format('F j, Y')
            .'     Mean rating: '.$evaluation->meanRating().'/4',
            $this->noteText
        );
    }

    protected function addCapstone(Section $section, Portfolio $portfolio): void
    {
        $capstone = $portfolio->capstoneRecord;

        if (! $capstone) {
            return;
        }

        $section->addTitle('Section 10 — Capstone Project Portfolio', 1);
        $section->addTitle('Project: '.$capstone->title, 2);

        $table = $section->addTable('grid');
        $this->headerRow($table, ['Criterion', 'PLO', 'Entry'], [2300, 700, 6400]);

        foreach (CapstoneRecord::CRITERIA_PLO_MAP as $field => $ploNumber) {
            $table->addRow();
            $table->addCell(2300)->addText(str($field)->replace('_', ' ')->title()->toString(), $this->labelText);
            $table->addCell(700)->addText('PLO '.$ploNumber, $this->bodyText, ['alignment' => Jc::CENTER]);
            $table->addCell(6400)->addText($capstone->{$field} ?: '—', $this->bodyText);
        }

        $section->addText(
            'Adviser: '.($capstone->adviser_name ?: '—')
            .'     Proposal defense: '.($capstone->proposal_defended_on?->format('F j, Y') ?? '—')
            .'     Final defense: '.($capstone->final_defended_on?->format('F j, Y') ?? '—'),
            $this->noteText
        );
    }

    protected function addProfessionalDevelopment(Section $section, Portfolio $portfolio): void
    {
        if ($portfolio->professionalDevelopment->isEmpty()) {
            return;
        }

        $section->addTitle('Section 11 — Professional Development', 1);

        $table = $section->addTable('grid');
        $this->headerRow($table, ['Activity', 'Type', 'Organizer', 'Date', 'Hours', 'Competency Demonstrated'],
            [1900, 1100, 1600, 1000, 700, 3100]);

        foreach ($portfolio->professionalDevelopment as $record) {
            $table->addRow();
            $table->addCell(1900)->addText($record->title, $this->bodyText);
            $table->addCell(1100)->addText($record->kindLabel(), $this->bodyText);
            $table->addCell(1600)->addText($record->organizer ?: '—', $this->bodyText);
            $table->addCell(1000)->addText($record->held_on?->format('M Y') ?? '—', $this->bodyText);
            $table->addCell(700)->addText((string) ($record->hours ?: '—'), $this->bodyText, ['alignment' => Jc::CENTER]);
            $table->addCell(3100)->addText($record->competency_demonstrated, $this->bodyText);
        }

        $section->addText(
            'Attendance alone earns no attainment. The competency described in the final column is what is assessed.',
            $this->noteText
        );
    }

    // -------------------------------------------------------------------------
    // Section 12
    // -------------------------------------------------------------------------

    protected function addReflections(Section $section, Portfolio $portfolio): void
    {
        if ($portfolio->reflections->isEmpty()) {
            return;
        }

        $section->addTitle('Section 12 — Student Reflection', 1);

        foreach ($portfolio->reflections as $reflection) {
            $section->addTitle('Reflection on: '.$reflection->subject, 2);

            $number = 1;

            foreach (config('portfolio.reflection_prompts') as $key => $prompt) {
                $section->addText($number.'. '.$prompt, ['bold' => true, 'size' => 10], ['spaceBefore' => 120, 'spaceAfter' => 40]);
                $section->addText($reflection->answer($key) ?: '—', ['italic' => true, 'size' => 10]);
                $number++;
            }
        }
    }

    // -------------------------------------------------------------------------
    // Faculty assessment
    // -------------------------------------------------------------------------

    protected function addFacultyAssessment(Section $section, Portfolio $portfolio): void
    {
        if ($portfolio->facultyEvaluations->isEmpty()) {
            return;
        }

        foreach ($portfolio->facultyEvaluations as $evaluation) {
            $section->addTitle('Faculty Assessment Form (Accomplished)', 1);
            $section->addText('Artifact assessed: '.$evaluation->artifact_assessed, $this->bodyText, ['spaceAfter' => 120]);

            $widths = [3400, 700, 700, 700, 700, 3200];

            $table = $section->addTable('grid');
            $this->headerRow($table, ['Criterion', '1', '2', '3', '4', 'Comments'], $widths);

            foreach (config('portfolio.faculty_criteria') as $key => $label) {
                $score = $evaluation->scores->firstWhere('criterion', $key);
                $this->tickRow($table, $label, (int) ($score?->rating ?? 0), $widths, $score?->comment ?: '');
            }

            $section->addText(
                'Evaluator: '.$evaluation->evaluator->displayName()
                .'          Date: '.$evaluation->evaluated_on->format('F j, Y'),
                $this->bodyText, ['spaceBefore' => 120]
            );

            if ($evaluation->overall_comment) {
                $section->addText('Overall: '.$evaluation->overall_comment, $this->noteText);
            }
        }
    }

    // -------------------------------------------------------------------------
    // Dashboard and transcript
    // -------------------------------------------------------------------------

    protected function addAttainmentDashboard(Section $section, Portfolio $portfolio): void
    {
        $section->addTitle('PLO Attainment Dashboard (as of end of Year '.$portfolio->year_level.')', 1);

        $table = $section->addTable('grid');
        $this->headerRow(
            $table,
            ['PLO', 'Y1', 'Y2', 'Y3', 'Y4', 'Current Level', 'Flag'],
            [700, 600, 600, 600, 700, 2600, 3600]
        );

        foreach ($this->matrix->dashboard($portfolio->student) as $number => $row) {
            $table->addRow();
            $table->addCell(700)->addText('PLO '.$number, $this->labelText);

            foreach ([1, 2, 3, 4] as $year) {
                $width = $year === 4 ? 700 : 600;
                $table->addCell($width)->addText($row['years'][$year], $this->bodyText, ['alignment' => Jc::CENTER]);
            }

            $table->addCell(2600)->addText($row['current'], $this->bodyText);
            $table->addCell(3600)->addText($row['flag'], $this->bodyText);
        }

        $section->addText(
            'Numeric scale follows Section V (1 = Introduced, 2 = Developing, 3 = Proficient, 4 = Advanced). Target '
            .number_format((float) config('portfolio.assessment.target'), 2)
            .'. Figures are computed from validated direct assessment evidence only; indirect evidence '
            .'(self-assessment, exit survey, employer and alumni feedback) is reported separately and is never averaged in.',
            $this->noteText
        );
    }

    protected function addCompetencyTranscript(Section $section, Portfolio $portfolio): void
    {
        $rows = $this->matrix->competencyTranscript($portfolio->student);

        if ($rows->isEmpty()) {
            return;
        }

        $final = $portfolio->year_level >= 4 && $portfolio->status->value === 'validated';

        $section->addTitle(
            'Student Competency Transcript ('.($final ? 'Final' : 'Interim — through Year '.$portfolio->year_level).')',
            1
        );

        $table = $section->addTable('grid');
        $this->headerRow($table, ['Competency Area', 'Level', 'Best Evidence', 'Year'], [2600, 1800, 4200, 800]);

        foreach ($rows as $row) {
            $table->addRow();
            $table->addCell(2600)->addText($row['area'], $this->bodyText);
            $table->addCell(1800)->addText($row['level'] ?? '—', $this->bodyText);
            $table->addCell(4200)->addText($row['evidence'], $this->bodyText);
            $table->addCell(800)->addText($row['year'], $this->bodyText, ['alignment' => Jc::CENTER]);
        }

        if (! $final) {
            $section->addText(
                'This transcript is finalised at graduation, once Year 4 OJT and capstone evidence has been validated.',
                $this->noteText
            );
        }
    }

    protected function addFooter(Section $section): void
    {
        $footer = $section->addFooter();
        $footer->addPreserveText('Page {PAGE} of {NUMPAGES}', ['size' => 8], ['alignment' => Jc::CENTER]);
    }

    // -------------------------------------------------------------------------
    // Table helpers
    // -------------------------------------------------------------------------

    protected function headerRow($table, array $headings, array $widths): void
    {
        $table->addRow();

        foreach ($headings as $index => $heading) {
            $table->addCell($widths[$index], $this->headerCell)->addText($heading, $this->headerText);
        }
    }

    protected function labelRow($table, string $label, string $value): void
    {
        $table->addRow();
        $table->addCell(3400)->addText($label, $this->labelText);
        $table->addCell(6000)->addText($value !== '' ? $value : '—', $this->bodyText);
    }

    /**
     * A rating row with a tick in the scored column, as the paper form does it.
     * Empty columns stay empty rather than showing a zero.
     */
    protected function tickRow($table, string $label, int $rating, array $widths, ?string $comment = null): void
    {
        $table->addRow();
        $table->addCell($widths[0])->addText($label, $this->bodyText);

        for ($i = 1; $i <= 4; $i++) {
            $table->addCell($widths[$i])->addText($rating === $i ? '✓' : '', $this->bodyText, ['alignment' => Jc::CENTER]);
        }

        if (isset($widths[5])) {
            $table->addCell($widths[5])->addText($comment ?: '', $this->bodyText);
        }
    }

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

    protected function stageName(int $yearLevel): string
    {
        return match ($yearLevel) {
            1 => 'Foundation',
            2 => 'Development',
            3 => 'Application',
            default => 'Integration',
        };
    }

    protected function ordinal(int $n): string
    {
        return match ($n) { 1 => 'st', 2 => 'nd', 3 => 'rd', default => 'th' };
    }
}
