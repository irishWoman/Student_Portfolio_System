{{--
    Student Competency Transcript (specification Section IX).

    One page, issued at graduation: PLO attainment across four years, then the
    best stage reached in each competency area with the evidence that earned it.
    Columns follow the department's printed form exactly.
--}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 18mm 14mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #1f2937; line-height: 1.35; }
        h1 { font-size: 14px; color: #14375E; margin: 0 0 2px; }
        h2 { font-size: 11px; color: #14375E; border-bottom: 2px solid #F0A500; padding-bottom: 3px; margin: 14px 0 6px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th { background: #14375E; color: #fff; text-align: left; padding: 4px 5px; font-size: 8.5px; border: 1px solid #14375E; }
        td { padding: 4px 5px; border: 1px solid #C9D2DE; vertical-align: top; }
        tbody tr:nth-child(odd) td { background: #FCEFD9; }
        tbody tr:nth-child(even) td { background: #FEF8EE; }
        .center { text-align: center; }
        .note { color: #4B5563; font-style: italic; font-size: 8.5px; }
        .head { text-align: center; margin-bottom: 10px; }
        .label { font-weight: bold; width: 26%; }
    </style>
</head>
<body>

<div class="head">
    <p class="note">{{ config('app.institution.name') }} · {{ config('app.institution.unit') }}</p>
    <h1>STUDENT COMPETENCY TRANSCRIPT</h1>
    <p class="note">{{ $student->program->title }} — {{ $isFinal ? 'Final' : 'Interim' }} record</p>
</div>

<table>
    <tbody>
        <tr><td class="label">Student</td><td>{{ $student->fullName() }}</td></tr>
        <tr><td class="label">Student Number</td><td>{{ $student->student_number }}</td></tr>
        <tr><td class="label">Year Level</td><td>Year {{ $student->year_level }}</td></tr>
        <tr><td class="label">Issued</td><td>{{ now()->format('F j, Y') }}</td></tr>
    </tbody>
</table>

<h2>Program Learning Outcome attainment</h2>
<table>
    <thead>
        <tr>
            <th style="width:7%">PLO</th><th style="width:27%">Outcome</th>
            <th class="center" style="width:6%">Y1</th><th class="center" style="width:6%">Y2</th>
            <th class="center" style="width:6%">Y3</th><th class="center" style="width:6%">Y4</th>
            <th style="width:22%">Current Level</th><th>Standing</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($dashboard as $number => $row)
            <tr>
                <td><strong>PLO {{ $number }}</strong></td>
                <td>{{ $row['plo']->title }}</td>
                @foreach ([1, 2, 3, 4] as $year)
                    <td class="center">{{ $row['years'][$year] }}</td>
                @endforeach
                <td>{{ $row['current'] }}</td>
                <td>{{ $row['flag'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<h2>Competency areas</h2>
<table>
    <thead><tr><th style="width:26%">Competency Area</th><th style="width:18%">Level</th><th>Best Evidence</th><th class="center" style="width:8%">Year</th></tr></thead>
    <tbody>
        @forelse ($transcript as $row)
            <tr>
                <td>{{ $row['area'] }}</td>
                <td>{{ $row['level'] ?? '—' }}</td>
                <td>{{ $row['evidence'] }}</td>
                <td class="center">{{ $row['year'] }}</td>
            </tr>
        @empty
            <tr><td colspan="4">No technical competencies recorded.</td></tr>
        @endforelse
    </tbody>
</table>

<p class="note">
    Numeric scale: 1 = Introduced, 2 = Developing, 3 = Proficient, 4 = Advanced. Attainment target
    {{ number_format($target, 2) }}, computed from validated direct assessment evidence only.
    Competency stage scale: Knowledge → Skill → Application → Integration → Professional Practice.
    @unless ($isFinal)
        This transcript is finalised at graduation, once Year 4 OJT and capstone evidence has been validated.
    @endunless
</p>

</body>
</html>
