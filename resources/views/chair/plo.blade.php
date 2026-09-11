@extends('layouts.app')
@section('title', $plo->code())

@section('content')
    <a href="{{ route('chair.dashboard') }}" class="text-sm text-navy-700 underline underline-offset-2">Back to dashboard</a>

    <div class="card mt-3 mb-5 p-5">
        <h1 class="text-lg font-semibold text-navy-800">{{ $plo->code() }} — {{ $plo->title }}</h1>
        <p class="text-sm text-slate-600 mt-1">{{ $plo->statement }}</p>
    </div>

    {{-- Where the competency is taught: the second CQI question, answered from the CLO map --}}
    <div class="card mb-5">
        <div class="card-header">Courses whose CLOs map to this outcome</div>
        @if ($courses->isEmpty())
            <div class="p-5 text-sm text-slate-600">
                <p class="text-status-risk font-medium">No course currently maps a CLO to this outcome.</p>
                <p class="mt-1">
                    That is an assessment-coverage gap, not a learning gap — students cannot evidence an outcome
                    nothing claims to teach.
                </p>
                <a href="{{ route('chair.curriculum') }}" class="btn-accent mt-3">Fix the mapping</a>
            </div>
        @else
            <table class="data-table">
                <thead><tr><th>Course</th><th>Title</th><th>Year</th><th>Term</th></tr></thead>
                <tbody>
                    @foreach ($courses as $course)
                        <tr>
                            <td class="font-medium">{{ $course->code }}</td>
                            <td>{{ $course->title }}</td>
                            <td>Year {{ $course->year_level }}</td>
                            <td class="text-xs">{{ str($course->term_kind)->replace('_', ' ')->title() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="px-5 py-3 border-t border-slate-200">
                <a href="{{ route('chair.curriculum') }}" class="text-sm text-navy-700 underline underline-offset-2">
                    Edit outcome mapping
                </a>
            </div>
        @endif
    </div>

    <div class="card">
        <div class="card-header">Students, lowest first</div>
        @if ($snapshots->isEmpty())
            <p class="p-5 text-sm text-slate-500">No attainment data for {{ $year?->label }}.</p>
        @else
            <table class="data-table">
                <thead><tr><th>Student</th><th>Year</th><th>Direct score</th><th>Valid evidence</th><th>Flag</th><th></th></tr></thead>
                <tbody>
                    @foreach ($snapshots as $snapshot)
                        <tr>
                            <td class="font-medium">{{ $snapshot->student->fullName() }}</td>
                            <td>Year {{ $snapshot->year_level }}</td>
                            <td>{{ $snapshot->direct_score !== null ? number_format((float) $snapshot->direct_score, 2) : '—' }}</td>
                            <td>{{ $snapshot->valid_evidence_count }}</td>
                            <td><span class="pill {{ $snapshot->flagClasses() }}">{{ $snapshot->flagLabel() }}</span></td>
                            <td class="text-right">
                                <a href="{{ route('chair.graduate-profile', $snapshot->student) }}"
                                   class="text-xs text-navy-700 underline underline-offset-2">Profile</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
