{{--
    PDF rendering of the accomplished portfolio.

    Mirrors the printed sample section for section, and mirrors the Word export
    (App\Services\PortfolioExportService) table for table — the two must not
    drift, so both read their grids from PortfolioMatrixService.

    DomPDF only supports old-school CSS, so everything here is tables and plain
    properties: no flexbox, no grid, no Tailwind.
--}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 20mm 14mm 18mm 14mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #1f2937; line-height: 1.35; }

        h1 { font-size: 13px; color: #14375E; margin: 16px 0 6px; border-bottom: 2px solid #F0A500; padding-bottom: 3px; }
        h2 { font-size: 11px; color: #1B4677; margin: 12px 0 4px; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th { background: #14375E; color: #fff; text-align: left; padding: 4px 5px; font-size: 8.5px; border: 1px solid #14375E; }
        td { padding: 4px 5px; border: 1px solid #C9D2DE; vertical-align: top; }
        tbody tr:nth-child(odd) td { background: #FCEFD9; }
        tbody tr:nth-child(even) td { background: #FEF8EE; }

        .label { font-weight: bold; width: 28%; }
        .center { text-align: center; }
        .note { color: #4B5563; font-style: italic; font-size: 8.5px; margin: 4px 0 10px; }
        .cover { text-align: center; }
        .cover .inst { font-size: 10px; font-weight: bold; }
        .cover .unit { font-size: 8.5px; color: #4B5563; margin-bottom: 10px; }
        .cover .title { font-size: 15px; font-weight: bold; color: #14375E; margin-bottom: 2px; }
        .cover .sub { font-size: 9.5px; font-style: italic; margin-bottom: 12px; }
        .prompt { font-weight: bold; margin: 6px 0 2px; }
        .answer { font-style: italic; margin: 0 0 4px; }
        .pagebreak { page-break-after: always; }

        /* -------------------------------------------------- Cover letterhead */
        .letterhead { text-align: center; margin-bottom: 10px; }
        .letterhead img { height: 58px; margin-bottom: 4px; }
        .letterhead .uni { font-size: 16px; font-weight: bold; color: #14375E; margin: 0; }
        .letterhead .school { font-size: 9.5px; font-weight: bold; margin: 6px 0 0; }
        .letterhead .dept { font-size: 9.5px; font-weight: bold; margin: 0; }
        .letterhead .degree { font-size: 9px; margin: 8px 0 0; }
        .letterhead .doctitle { font-size: 11px; font-weight: bold; margin: 0; }

        /* Borderless label/value pairs, as the printed form's blank-line rows.
           !important beats the generic tbody zebra-striping rule above, which
           would otherwise win on specificity and bleed into these. */
        .plain { border: none; }
        .plain td { border: none !important; background: none !important; padding: 2px 4px; }
        .plabel { font-weight: bold; width: 38%; }

        .photo-box {
            width: 120px; height: 120px; border: 1px solid #9CA3AF;
            text-align: center; vertical-align: middle; font-size: 8px; color: #6B7280;
        }
        .photo-box img { width: 118px; height: 118px; }

        /* The orange section band from the printed form. */
        .form-band {
            background: #FDF3DC; color: #14375E; font-weight: bold; text-transform: uppercase;
            font-size: 9px; padding: 4px 6px; border: 1px solid #F0A500; margin: 10px 0 2px;
        }
    </style>
</head>
<body>

@php
    $profile = $portfolio->entryFor(1);
    $dob = $profile?->answer('date_of_birth');
    $dobFormatted = $dob ? \Illuminate\Support\Carbon::parse($dob)->format('F j, Y') : null;
@endphp

{{-- ---------------------------------------------------------------- Cover --}}
<div class="letterhead">
    <img src="{{ public_path('images/logo-uslt.png') }}" alt="">
    <p class="uni">{{ strtoupper(config('app.institution.name')) }}</p>
    <p class="school">{{ strtoupper(config('app.institution.unit')) }}</p>
    <p class="dept">{{ strtoupper(config('app.institution.department')) }}</p>
    <p class="degree">{{ strtoupper($portfolio->student->program->title) }}</p>
    <p class="doctitle">STUDENT PORTFOLIO</p>
</div>

<table class="plain">
    <tr>
        <td style="width:68%; vertical-align:top; border:none;">
            <table class="plain">
                <tr><td class="plabel">Name:</td><td>{{ $portfolio->student->fullName() }}</td></tr>
                <tr><td class="plabel">Address:</td><td>{{ $profile?->answer('address') ?: '—' }}</td></tr>
                <tr><td class="plabel">E-mail Address:</td><td>{{ $profile?->answer('email_address') ?: '—' }}</td></tr>
                <tr><td class="plabel">Contact Number/s:</td><td>{{ $profile?->answer('contact_number') ?: '—' }}</td></tr>
                <tr><td class="plabel">Term/Year Started:</td><td>{{ $profile?->answer('term_year_started') ?: '—' }}</td></tr>
                <tr><td class="plabel">Year Level:</td><td>{{ $portfolio->year_level }}{{ $ordinal }} Year</td></tr>
            </table>
        </td>
        <td style="width:32%; text-align:center; vertical-align:top; border:none;">
            <div class="photo-box">
                @if ($portfolio->student->photo_path)
                    <img src="{{ $portfolio->student->photoAbsolutePath() }}" alt="">
                @else
                    2x2 ID Photo
                @endif
            </div>
        </td>
    </tr>
</table>

<p class="form-band">Personal Data</p>
<table class="plain">
    <tr><td class="plabel">Gender:</td><td>{{ $profile?->answer('gender') ?: '—' }}</td></tr>
    <tr><td class="plabel">Date of Birth:</td><td>{{ $dobFormatted ?: '—' }}</td></tr>
    <tr><td class="plabel">Birth Place:</td><td>{{ $profile?->answer('birth_place') ?: '—' }}</td></tr>
    <tr><td class="plabel">Religion:</td><td>{{ $profile?->answer('religion') ?: '—' }}</td></tr>
    <tr><td class="plabel">Civil Status:</td><td>{{ $profile?->answer('civil_status') ?: '—' }}</td></tr>
    <tr><td class="plabel">Citizenship:</td><td>{{ $profile?->answer('citizenship') ?: '—' }}</td></tr>
    <tr><td class="plabel">Parents:</td><td>{{ $profile?->answer('parents') ?: '—' }}</td></tr>
</table>

<p class="form-band">Educational Background</p>
<table class="plain">
    <tr><td class="plabel">Kinder 1-2:</td><td>{{ $profile?->answer('kinder_school') ?: '—' }}</td></tr>
    <tr><td class="plabel">Grade 1-6:</td><td>{{ $profile?->answer('elementary_school') ?: '—' }}</td></tr>
    <tr><td class="plabel">Grade 7-10:</td><td>{{ $profile?->answer('junior_high_school') ?: '—' }}</td></tr>
    <tr><td class="plabel">Grade 11-12:</td><td>{{ $profile?->answer('senior_high_school') ?: '—' }}</td></tr>
    <tr><td class="plabel">Tertiary:</td><td>{{ $profile?->answer('tertiary_school') ?: '—' }}</td></tr>
</table>
<p class="note" style="margin-top: -4px;">(write program/school before taking up BSCpE at USLT)</p>

<p class="form-band">Personal Reflection</p>
<p style="margin: 4px 0 8px;">{{ $profile?->answer('personal_reflection') ?: '—' }}</p>

<table class="plain" style="margin-top: 8px;">
    <tr><td class="plabel">Academic Year:</td><td>{{ $portfolio->academicYear->label }}</td></tr>
    <tr><td class="plabel">Portfolio Status:</td><td>{{ $statusLine }}</td></tr>
</table>

<p class="note">
    Generated on {{ now()->format('F j, Y') }}. Attainment figures are computed from validated direct
    assessment evidence only.
</p>

<div class="pagebreak"></div>

{{-- ------------------------------------------------------------ Section 1 --}}
<h1>Section 1 — Student Profile</h1>
<table>
    <thead><tr><th style="width:30%">Field</th><th>Entry</th></tr></thead>
    <tbody>
        <tr><td class="label">Student Name</td><td>{{ $portfolio->student->fullName() }}</td></tr>
        <tr><td class="label">Student Number</td><td>{{ $portfolio->student->student_number }}</td></tr>
        <tr><td class="label">Program</td><td>{{ $portfolio->student->program->title }}</td></tr>
        <tr><td class="label">Year Level</td><td>{{ $portfolio->year_level }}{{ $ordinal }} Year</td></tr>
        <tr><td class="label">Academic Year</td><td>{{ $portfolio->academicYear->label }}</td></tr>
        @foreach ($profile?->definition()['fields'] ?? [] as $field)
            @continue(($field['type'] ?? 'text') === 'heading')
            <tr><td class="label">{{ $field['label'] }}</td><td>{{ $profile->answer($field['name']) ?: '—' }}</td></tr>
        @endforeach
    </tbody>
</table>

{{-- ------------------------------------------------------------ Section 2 --}}
@php $plan = $portfolio->entryFor(2); @endphp
@if ($plan)
    <h1>Section 2 — Personal Development Plan (AY {{ $portfolio->academicYear->label }}, Year {{ $portfolio->year_level }} entry)</h1>
    <table>
        <thead><tr><th style="width:30%">Item</th><th>Entry</th></tr></thead>
        <tbody>
            @foreach ($plan->definition()['fields'] as $field)
                <tr><td class="label">{{ $field['label'] }}</td><td>{{ $plan->answer($field['name']) ?: '—' }}</td></tr>
            @endforeach
        </tbody>
    </table>
@endif

{{-- ------------------------------------------------- Section 3: the matrix --}}
<h1>Section 3 — PLO and Competency Matrix (as of end of Year {{ $portfolio->year_level }})</h1>
<p class="note">Legend: I = Introduced, D = Developing, A = Applied, P = Proficient, — = not yet targeted this year</p>
<table>
    <thead>
        <tr>
            <th style="width:7%">PLO</th><th style="width:21%">Competency</th>
            <th class="center" style="width:7%">Year 1</th><th class="center" style="width:7%">Year 2</th>
            <th class="center" style="width:7%">Year 3</th><th class="center" style="width:7%">Year 4</th>
            <th>Representative Evidence</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($matrix as $number => $row)
            <tr>
                <td><strong>PLO {{ $number }}</strong></td>
                <td>{{ $row['plo']->title }}</td>
                @foreach ([1, 2, 3, 4] as $year)
                    <td class="center">{{ $row['years'][$year] }}</td>
                @endforeach
                <td>{{ $row['evidence'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
@if ($portfolio->year_level < 4)
    <p class="note">
        Year {{ $portfolio->year_level + 1 }} onward is marked "Pending": that evidence has not been produced yet.
        This matrix is updated as each year's evidence is validated.
    </p>
@endif

{{-- ------------------------------------------------------------ Section 4 --}}
@if ($portfolio->courseEvidence->isNotEmpty())
    <h1>Section 4 — Course-Based Evidence (selected entries)</h1>
    <table>
        <thead>
            <tr>
                <th>Course</th><th>CLO</th><th class="center">PLO</th><th>Assessment</th>
                <th>Evidence</th><th class="center">Score</th><th>Level</th><th>Evaluator / Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($portfolio->courseEvidence as $record)
                <tr>
                    <td>{{ $record->course ? $record->course->code.' '.$record->course->title.' (Y'.$record->course->year_level.')' : '—' }}</td>
                    <td>{{ $record->clo_statement ?: ($record->learningOutcome?->statement ?? '—') }}</td>
                    <td class="center">{{ $record->plos->map(fn ($p) => $p->number)->implode(',') }}</td>
                    <td>{{ $record->assessment_activity }}</td>
                    <td>{{ $record->output_title }}</td>
                    <td class="center">{{ $record->score ? $record->score.'/'.$record->score_max : '—' }}</td>
                    <td>{{ $record->validated_level ? \App\Support\Enums\AttainmentLevel::from($record->validated_level)->matrixLabel() : 'Pending' }}</td>
                    <td>{{ $record->evaluator ? $record->evaluator->displayName().' / '.($record->evaluated_on?->format('M Y') ?? '') : 'Not yet validated' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

{{-- ------------------------------------------------------------ Section 5 --}}
@if ($portfolio->technicalCompetencies->isNotEmpty())
    <h1>Section 5 — Technical Competency Portfolio (current standing)</h1>
    <table>
        <thead><tr><th style="width:20%">Category</th><th style="width:24%">Competency</th><th style="width:18%">Stage Reached</th><th>Evidence</th></tr></thead>
        <tbody>
            @foreach ($portfolio->technicalCompetencies->sortBy(fn ($r) => $r->competency->category->sort_order) as $record)
                <tr>
                    <td>{{ $record->competency->category->name }}</td>
                    <td>{{ $record->competency->name }}</td>
                    <td>{{ $record->effectiveStage()?->label() ?? '—' }}</td>
                    <td>{{ $record->evidence_note ?: '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p class="note">
        Stage scale: Knowledge → Skill → Application → Integration → Professional Practice.
        "Integration" and "Professional Practice" are expected to be reached in Year 4 through the capstone and OJT.
    </p>
@endif

{{-- ------------------------------------------------------------ Section 6 --}}
@foreach ($portfolio->projects as $project)
    <h1>Section 6 — Engineering Design Portfolio</h1>
    <h2>Project: {{ $project->title }}@if ($project->course || $project->role)
        ({{ collect([$project->course?->code, 'Year '.$portfolio->year_level, $project->role])->filter()->implode(', ') }})
    @endif</h2>
    <table>
        <thead><tr><th class="center" style="width:5%">#</th><th style="width:22%">Design Element</th><th>Entry</th></tr></thead>
        <tbody>
            @foreach ($project->designElements as $element)
                <tr>
                    <td class="center">{{ $element->position }}</td>
                    <td><strong>{{ $element->label() }}</strong></td>
                    <td>{{ $element->content ?: '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endforeach

{{-- ------------------------------------------------------- Sections 7 & 8 --}}
@foreach ([7 => 'Section 7 — Research and Investigation Portfolio', 8 => 'Section 8 — Professional Competency Portfolio'] as $number => $heading)
    @php $entry = $portfolio->entryFor($number); @endphp
    @if ($entry && array_filter($entry->payload ?? []))
        <h1>{{ $heading }}</h1>
        <table>
            <thead><tr><th style="width:30%">Item</th><th>Entry</th></tr></thead>
            <tbody>
                @foreach ($entry->definition()['fields'] as $field)
                    <tr><td class="label">{{ $field['label'] }}</td><td>{{ $entry->answer($field['name']) ?: '—' }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endforeach

{{-- ------------------------------------------------------------ Section 9 --}}
@if ($portfolio->ojtRecord)
    @php $ojt = $portfolio->ojtRecord; @endphp
    <h1>Section 9 — Industry / OJT Portfolio</h1>
    <table>
        <thead><tr><th style="width:30%">Item</th><th>Entry</th></tr></thead>
        <tbody>
            <tr><td class="label">Company</td><td>{{ $ojt->company_name }}@if ($ojt->industry) ({{ $ojt->industry }})@endif</td></tr>
            <tr><td class="label">Address</td><td>{{ $ojt->company_address ?: '—' }}</td></tr>
            <tr><td class="label">Supervisor</td><td>{{ trim($ojt->supervisor_name.' — '.$ojt->supervisor_position, ' —') }}</td></tr>
            <tr><td class="label">Period</td><td>{{ $ojt->started_on?->format('M j, Y') }} to {{ $ojt->ended_on?->format('M j, Y') }}</td></tr>
            <tr><td class="label">Hours completed</td><td>{{ $ojt->completed_hours }} of {{ $ojt->required_hours }} required</td></tr>
            <tr><td class="label">Objectives</td><td>{{ $ojt->objectives ?: '—' }}</td></tr>
            <tr><td class="label">Responsibilities</td><td>{{ $ojt->responsibilities ?: '—' }}</td></tr>
            <tr><td class="label">Work outputs</td><td>{{ $ojt->work_outputs ?: '—' }}</td></tr>
            <tr><td class="label">Student reflection</td><td>{{ $ojt->student_reflection ?: '—' }}</td></tr>
        </tbody>
    </table>

    @if ($ojt->supervisorEvaluation?->submitted_at)
        @php $supervisor = $ojt->supervisorEvaluation; @endphp
        <h2>Industry Supervisor Evaluation</h2>
        <table>
            <thead><tr><th style="width:56%">Area</th><th class="center">1</th><th class="center">2</th><th class="center">3</th><th class="center">4</th></tr></thead>
            <tbody>
                @foreach (\App\Models\OjtSupervisorEvaluation::CRITERIA as $key => $label)
                    <tr>
                        <td>{{ $label }}</td>
                        @for ($i = 1; $i <= 4; $i++)
                            <td class="center">{{ (int) $supervisor->{$key} === $i ? '✓' : '' }}</td>
                        @endfor
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p><strong>Strengths:</strong> {{ $supervisor->strengths ?: '—' }}</p>
        <p><strong>Areas for improvement:</strong> {{ $supervisor->areas_for_improvement ?: '—' }}</p>
        <p class="note">
            Signed: {{ $supervisor->signed_by }} &nbsp;&nbsp; Date: {{ $supervisor->submitted_at->format('F j, Y') }}
            &nbsp;&nbsp; Mean rating: {{ $supervisor->meanRating() }}/4
        </p>
    @else
        <p class="note">Industry supervisor evaluation has not been received.</p>
    @endif
@endif

{{-- ----------------------------------------------------------- Section 10 --}}
@if ($portfolio->capstoneRecord)
    @php $capstone = $portfolio->capstoneRecord; @endphp
    <h1>Section 10 — Capstone Project Portfolio</h1>
    <h2>Project: {{ $capstone->title }}</h2>
    <table>
        <thead><tr><th style="width:22%">Criterion</th><th class="center" style="width:8%">PLO</th><th>Entry</th></tr></thead>
        <tbody>
            @foreach (\App\Models\CapstoneRecord::CRITERIA_PLO_MAP as $field => $ploNumber)
                <tr>
                    <td><strong>{{ str($field)->replace('_', ' ')->title() }}</strong></td>
                    <td class="center">PLO {{ $ploNumber }}</td>
                    <td>{{ $capstone->{$field} ?: '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p class="note">
        Adviser: {{ $capstone->adviser_name ?: '—' }} &nbsp;&nbsp;
        Proposal defense: {{ $capstone->proposal_defended_on?->format('F j, Y') ?? '—' }} &nbsp;&nbsp;
        Final defense: {{ $capstone->final_defended_on?->format('F j, Y') ?? '—' }}
    </p>
@endif

{{-- ----------------------------------------------------------- Section 11 --}}
@if ($portfolio->professionalDevelopment->isNotEmpty())
    <h1>Section 11 — Professional Development</h1>
    <table>
        <thead><tr><th>Activity</th><th>Type</th><th>Organizer</th><th>Date</th><th class="center">Hours</th><th style="width:32%">Competency Demonstrated</th></tr></thead>
        <tbody>
            @foreach ($portfolio->professionalDevelopment as $record)
                <tr>
                    <td>{{ $record->title }}</td>
                    <td>{{ $record->kindLabel() }}</td>
                    <td>{{ $record->organizer ?: '—' }}</td>
                    <td>{{ $record->held_on?->format('M Y') ?? '—' }}</td>
                    <td class="center">{{ $record->hours ?: '—' }}</td>
                    <td>{{ $record->competency_demonstrated }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p class="note">Attendance alone earns no attainment. The competency described in the final column is what is assessed.</p>
@endif

{{-- ----------------------------------------------------------- Section 12 --}}
@if ($portfolio->reflections->isNotEmpty())
    <h1>Section 12 — Student Reflection</h1>
    @foreach ($portfolio->reflections as $reflection)
        <h2>Reflection on: {{ $reflection->subject }}</h2>
        @php $n = 1; @endphp
        @foreach (config('portfolio.reflection_prompts') as $key => $prompt)
            <p class="prompt">{{ $n }}. {{ $prompt }}</p>
            <p class="answer">{{ $reflection->answer($key) ?: '—' }}</p>
            @php $n++; @endphp
        @endforeach
    @endforeach
@endif

{{-- ------------------------------------------------- Faculty assessment --}}
@foreach ($portfolio->facultyEvaluations as $evaluation)
    <h1>Faculty Assessment Form (Accomplished)</h1>
    <p>Artifact assessed: {{ $evaluation->artifact_assessed }}</p>
    <table>
        <thead>
            <tr>
                <th style="width:30%">Criterion</th>
                <th class="center" style="width:6%">1</th><th class="center" style="width:6%">2</th>
                <th class="center" style="width:6%">3</th><th class="center" style="width:6%">4</th>
                <th>Comments</th>
            </tr>
        </thead>
        <tbody>
            @foreach (config('portfolio.faculty_criteria') as $key => $label)
                @php $score = $evaluation->scores->firstWhere('criterion', $key); @endphp
                <tr>
                    <td>{{ $label }}</td>
                    @for ($i = 1; $i <= 4; $i++)
                        <td class="center">{{ (int) ($score?->rating ?? 0) === $i ? '✓' : '' }}</td>
                    @endfor
                    <td>{{ $score?->comment ?: '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p>Evaluator: {{ $evaluation->evaluator->displayName() }} &nbsp;&nbsp;&nbsp;&nbsp; Date: {{ $evaluation->evaluated_on->format('F j, Y') }}</p>
    @if ($evaluation->overall_comment)
        <p class="note">Overall: {{ $evaluation->overall_comment }}</p>
    @endif
@endforeach

{{-- ------------------------------------------------------------ Dashboard --}}
<h1>PLO Attainment Dashboard (as of end of Year {{ $portfolio->year_level }})</h1>
<table>
    <thead>
        <tr>
            <th style="width:7%">PLO</th>
            <th class="center" style="width:6%">Y1</th><th class="center" style="width:6%">Y2</th>
            <th class="center" style="width:6%">Y3</th><th class="center" style="width:7%">Y4</th>
            <th style="width:24%">Current Level</th><th>Flag</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($dashboard as $number => $row)
            <tr>
                <td><strong>PLO {{ $number }}</strong></td>
                @foreach ([1, 2, 3, 4] as $year)
                    <td class="center">{{ $row['years'][$year] }}</td>
                @endforeach
                <td>{{ $row['current'] }}</td>
                <td>{{ $row['flag'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
<p class="note">
    Numeric scale follows Section V (1 = Introduced, 2 = Developing, 3 = Proficient, 4 = Advanced).
    Target {{ number_format((float) config('portfolio.assessment.target'), 2) }}. Figures are computed from
    validated direct assessment evidence only; indirect evidence (self-assessment, exit survey, employer and
    alumni feedback) is reported separately and is never averaged in.
</p>

{{-- ----------------------------------------------------------- Transcript --}}
@if ($transcript->isNotEmpty())
    <h1>Student Competency Transcript ({{ $transcriptStatus }})</h1>
    <table>
        <thead><tr><th style="width:26%">Competency Area</th><th style="width:18%">Level</th><th>Best Evidence</th><th class="center" style="width:8%">Year</th></tr></thead>
        <tbody>
            @foreach ($transcript as $row)
                <tr>
                    <td>{{ $row['area'] }}</td>
                    <td>{{ $row['level'] ?? '—' }}</td>
                    <td>{{ $row['evidence'] }}</td>
                    <td class="center">{{ $row['year'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @if ($transcriptStatus !== 'Final')
        <p class="note">
            This transcript is finalised at graduation, once Year 4 OJT and capstone evidence has been validated.
        </p>
    @endif
@endif

</body>
</html>
