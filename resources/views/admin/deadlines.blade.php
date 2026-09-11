@extends('layouts.app')
@section('title', 'Deadlines')

@section('content')
    <h1 class="text-lg font-semibold text-navy-800 mb-1">Calendar and deadlines</h1>
    <p class="text-sm text-slate-600 mb-5">
        Each year level gets semester checkpoints plus one deadline before the academic year ends.
    </p>

    {{-- Open a year, and generate a schedule --}}
    <div class="grid gap-4 sm:grid-cols-2 mb-6">
        <form method="POST" action="{{ route('admin.year.open') }}" class="card p-5">
            @csrf
            <p class="font-medium text-slate-800 mb-1">Open an academic year</p>
            <p class="text-sm text-slate-500 mb-3">Marks it current and creates every active student's portfolio.</p>
            <div class="flex gap-3">
                <select name="academic_year_id" class="select">
                    @foreach ($years as $option)
                        <option value="{{ $option->id }}" @selected($year?->id === $option->id)>{{ $option->label }}</option>
                    @endforeach
                </select>
                <button class="btn-primary">Open year</button>
            </div>
        </form>

        <form method="POST" action="{{ route('admin.deadlines.generate') }}" class="card p-5">
            @csrf
            <p class="font-medium text-slate-800 mb-1">Generate a schedule</p>
            <p class="text-sm text-slate-500 mb-3">Builds the default dates from the term calendar. Existing dates are kept.</p>
            <div class="flex gap-3">
                <select name="academic_year_id" class="select">
                    @foreach ($years as $option)
                        <option value="{{ $option->id }}" @selected($year?->id === $option->id)>{{ $option->label }}</option>
                    @endforeach
                </select>
                <select name="year_level" class="select w-32">
                    @foreach ([1, 2, 3, 4] as $level)
                        <option value="{{ $level }}">Year {{ $level }}</option>
                    @endforeach
                </select>
                <button class="btn-accent">Generate</button>
            </div>
        </form>
    </div>

    @forelse ($sets as $set)
        <div class="card mb-5">
            <div class="card-header">{{ $set->label }}</div>
            <table class="data-table">
                <thead><tr><th>Applies to</th><th>Scope</th><th>Due</th><th>Grace</th><th>Locks</th><th></th></tr></thead>
                <tbody>
                    @foreach ($set->deadlines as $deadline)
                        <tr>
                            <td>
                                <form method="POST" action="{{ route('admin.deadlines.update', $deadline) }}" id="d{{ $deadline->id }}">
                                    @csrf @method('PUT')
                                    <input name="title" class="input" value="{{ $deadline->title }}">
                                    <span class="text-xs text-slate-500">{{ $deadline->sectionTitle() }}</span>
                                </form>
                            </td>
                            <td class="text-xs">{{ $deadline->scope->label() }}</td>
                            <td>
                                <input form="d{{ $deadline->id }}" type="datetime-local" name="due_at" class="input"
                                       value="{{ $deadline->due_at->format('Y-m-d\TH:i') }}">
                            </td>
                            <td>
                                <input form="d{{ $deadline->id }}" type="number" name="grace_days" class="input w-20"
                                       value="{{ $deadline->grace_days }}">
                            </td>
                            <td>
                                <label class="text-xs flex items-center gap-1">
                                    <input form="d{{ $deadline->id }}" type="checkbox" name="locks_editing" value="1"
                                           @checked($deadline->locks_editing)
                                           class="border-slate-300 text-navy-800 focus:ring-navy-600">
                                    Hard lock
                                </label>
                            </td>
                            <td class="text-right whitespace-nowrap">
                                <button form="d{{ $deadline->id }}" class="btn-quiet text-xs">Save</button>
                                <form method="POST" action="{{ route('admin.deadlines.destroy', $deadline) }}" class="inline">
                                    @csrf @method('DELETE')
                                    <button class="text-xs text-status-risk underline underline-offset-2 ml-2">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @empty
        <div class="card p-5 text-sm text-slate-500">
            No schedule for {{ $year?->label }} yet. Generate one above.
        </div>
    @endforelse
@endsection
