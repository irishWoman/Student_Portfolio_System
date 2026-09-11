@extends('layouts.app')
@section('title', 'My portfolio')

@section('content')
    {{-- Header: who, which year, and how far along --}}
    <div class="card mb-5">
        <div class="h-1 bg-amber-500"></div>
        <div class="p-5 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-lg font-semibold text-navy-800">{{ $student->fullName() }}</h1>
                <p class="text-sm text-slate-600">
                    {{ $student->student_number }} · {{ $student->program->code }}-{{ $portfolio->year_level }}
                    · Academic year {{ $portfolio->academicYear->label }}
                </p>
                <span class="pill mt-2 {{ $portfolio->status->badgeClasses() }}">
                    {{ $portfolio->status->label() }}@if ($portfolio->is_late) · late @endif
                </span>
            </div>

            <div class="text-right">
                <p class="text-3xl font-semibold text-navy-800">{{ $portfolio->completion_percent }}%</p>
                <p class="text-xs text-slate-500 mb-2">of required sections filled in</p>
                <div class="meter w-48"><div class="meter-fill" style="width: {{ $portfolio->completion_percent }}%"></div></div>
            </div>
        </div>

        {{-- The deadline that governs the whole year --}}
        @if ($nextDeadline)
            @php $daysLeft = (int) now()->startOfDay()->diffInDays($nextDeadline->dueFor($student)->startOfDay(), false); @endphp
            <div class="border-t border-slate-200 bg-cream-100 px-5 py-3 text-sm flex flex-wrap items-center justify-between gap-3">
                <span>
                    <span class="font-medium">Next due:</span> {{ $nextDeadline->title }} —
                    {{ $nextDeadline->dueFor($student)->format('F j, Y \a\t g:i a') }}
                </span>
                <span class="pill {{ $daysLeft <= 3 ? 'bg-red-50 text-status-risk ring-red-200' : 'bg-white text-slate-700 ring-slate-300' }}">
                    {{ $daysLeft === 0 ? 'Due today' : ($daysLeft === 1 ? '1 day left' : $daysLeft.' days left') }}
                </span>
            </div>
        @endif
    </div>

    {{-- Sections required at this year level --}}
    <h2 class="text-sm font-semibold text-slate-600 uppercase tracking-wider mb-3">Sections for year {{ $portfolio->year_level }}</h2>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 mb-6">
        @foreach ($portfolio->sectionEntries as $entry)
            @php $definition = $entry->definition(); @endphp
            <a href="{{ route('portfolio.section', [$portfolio, $entry->section_number]) }}" class="section-tile">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
                    <span class="text-xs font-semibold text-navy-700">Section {{ $entry->section_number }}</span>
                    <span class="pill {{ $entry->status->badgeClasses() }}">{{ $entry->status->label() }}</span>
                </div>
                <div class="p-4 flex-1">
                    <p class="font-medium text-slate-800">{{ $definition['title'] }}</p>
                    <p class="text-sm text-slate-500 mt-1">{{ $definition['blurb'] }}</p>
                </div>
                <div class="px-4 pb-4">
                    <div class="meter"><div class="meter-fill" style="width: {{ $entry->completion_percent }}%"></div></div>
                    <p class="text-xs text-slate-500 mt-1">{{ $entry->completion_percent }}% complete</p>
                </div>
            </a>
        @endforeach
    </div>

    {{-- Submit for review --}}
    @if ($portfolio->isEditableByStudent())
        <div class="card p-5 mb-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="font-medium text-slate-800">Send this year's portfolio for review</p>
                    <p class="text-sm text-slate-500">
                        Your evaluator validates the levels you claimed. You can keep editing if it is sent back.
                    </p>
                </div>
                <form method="POST" action="{{ route('portfolio.submit', $portfolio) }}">
                    @csrf
                    <button class="btn-accent">Submit for review</button>
                </form>
            </div>
        </div>
    @elseif ($portfolio->overall_remarks)
        <div class="card p-5 mb-6 border-l-4 border-amber-500">
            <p class="font-medium text-slate-800">Notes from your evaluator</p>
            <p class="text-sm text-slate-600 mt-1">{{ $portfolio->overall_remarks }}</p>
        </div>
    @endif

    {{-- Full schedule --}}
    @if ($deadlines->isNotEmpty())
        <div class="card mb-6">
            <div class="card-header">Deadlines this academic year</div>
            <table class="data-table">
                <thead>
                    <tr><th>What</th><th>Scope</th><th>Due</th><th>Late accepted until</th></tr>
                </thead>
                <tbody>
                    @foreach ($deadlines->sortBy('due_at') as $deadline)
                        <tr>
                            <td>{{ $deadline->title }}</td>
                            <td>{{ $deadline->scope->label() }}</td>
                            <td>{{ $deadline->dueFor($student)->format('M j, Y g:i a') }}</td>
                            <td>
                                {{ $deadline->locks_editing
                                    ? 'Locks at due date'
                                    : $deadline->graceEndsFor($student)->format('M j, Y') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Previous years --}}
    @if ($history->count() > 1)
        <div class="card">
            <div class="card-header">Previous years</div>
            <table class="data-table">
                <thead><tr><th>Academic year</th><th>Year level</th><th>Status</th><th>Completion</th><th></th></tr></thead>
                <tbody>
                    @foreach ($history->where('id', '!=', $portfolio->id) as $past)
                        <tr>
                            <td>{{ $past->academicYear->label }}</td>
                            <td>Year {{ $past->year_level }}</td>
                            <td><span class="pill {{ $past->status->badgeClasses() }}">{{ $past->status->label() }}</span></td>
                            <td>{{ $past->completion_percent }}%</td>
                            <td class="text-right">
                                <a href="{{ route('portfolio.history', $past) }}" class="text-navy-700 underline underline-offset-2">Open</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
