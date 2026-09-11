@extends('layouts.app')
@section('title', 'Graduate competency profile')

@section('content')
    <div class="card mb-5">
        <div class="h-1 bg-amber-500"></div>
        <div class="p-5 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-lg font-semibold text-navy-800">{{ $student->fullName() }}</h1>
                <p class="text-sm text-slate-600">
                    {{ $student->student_number }} · {{ $student->program->title }} · Year {{ $student->year_level }}
                </p>
            </div>
            <a href="{{ route('export.transcript', $student) }}" class="btn-quiet">Download competency transcript</a>
        </div>
    </div>

    {{--
        The accreditation question in one table: has this student progressively
        attained each outcome across four years?
    --}}
    <div class="card mb-5">
        <div class="card-header">Cumulative PLO attainment</div>
        <table class="data-table">
            <thead><tr><th>PLO</th><th>Cumulative score</th><th>Valid evidence</th><th>Standing</th></tr></thead>
            <tbody>
                @foreach ($cumulative as $number => $row)
                    <tr>
                        <td class="font-semibold text-navy-800">PLO {{ $number }}</td>
                        <td class="font-medium">{{ $row['score'] !== null ? number_format($row['score'], 2) : '—' }}</td>
                        <td>{{ $row['evidence'] }}</td>
                        <td>
                            @if ($row['score'] === null)
                                <span class="pill bg-slate-100 text-slate-600 ring-slate-300">No evidence</span>
                            @elseif ($row['score'] >= $target)
                                <span class="pill bg-emerald-50 text-status-ontrack ring-emerald-200">Attained</span>
                            @else
                                <span class="pill bg-red-50 text-status-risk ring-red-200">Gap remains</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="card">
        <div class="card-header">Year-by-year portfolios</div>
        <table class="data-table">
            <thead><tr><th>Academic year</th><th>Year level</th><th>Status</th><th>Completion</th></tr></thead>
            <tbody>
                @foreach ($student->portfolios as $portfolio)
                    <tr>
                        <td>{{ $portfolio->academicYear->label }}</td>
                        <td>Year {{ $portfolio->year_level }}</td>
                        <td><span class="pill {{ $portfolio->status->badgeClasses() }}">{{ $portfolio->status->label() }}</span></td>
                        <td>{{ $portfolio->completion_percent }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
