@extends('layouts.app')
@section('title', 'Review queue')

@section('content')
    <h1 class="text-lg font-semibold text-navy-800 mb-1">Portfolios waiting for review</h1>
    <p class="text-sm text-slate-600 mb-5">
        Academic year {{ $year?->label ?? '—' }}. Your own advisees appear first.
    </p>

    <div class="card">
        @if ($portfolios->isEmpty())
            <p class="p-5 text-sm text-slate-500">Nothing waiting. Submissions appear here as students send them in.</p>
        @else
            <table class="data-table">
                <thead>
                    <tr><th>Student</th><th>Year</th><th>Completion</th><th>Submitted</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach ($portfolios as $portfolio)
                        <tr>
                            <td>
                                <p class="font-medium">{{ $portfolio->student->fullName() }}</p>
                                <p class="text-xs text-slate-500">{{ $portfolio->student->student_number }}</p>
                            </td>
                            <td>Year {{ $portfolio->year_level }}</td>
                            <td>
                                <div class="meter w-24"><div class="meter-fill" style="width: {{ $portfolio->completion_percent }}%"></div></div>
                                <span class="text-xs text-slate-500">{{ $portfolio->completion_percent }}%</span>
                            </td>
                            <td class="text-xs">
                                {{ $portfolio->submitted_at?->format('M j, Y') ?? '—' }}
                                @if ($portfolio->is_late)
                                    <span class="pill bg-red-50 text-status-risk ring-red-200 ml-1">Late</span>
                                @endif
                            </td>
                            <td><span class="pill {{ $portfolio->status->badgeClasses() }}">{{ $portfolio->status->label() }}</span></td>
                            <td class="text-right">
                                <a href="{{ route('faculty.review', $portfolio) }}" class="btn-quiet text-xs">Review</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
