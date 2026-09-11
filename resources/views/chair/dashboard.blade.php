@extends('layouts.app')
@section('title', 'PLO attainment')

@section('content')
    <h1 class="text-lg font-semibold text-navy-800 mb-1">Program learning outcome attainment</h1>
    <p class="text-sm text-slate-600 mb-5">
        Direct evidence only, target {{ number_format($target, 2) }} on the 1–4 scale.
        Indirect evidence is reported separately and never merged into these figures.
    </p>

    {{-- Filters --}}
    <form method="GET" class="card p-4 mb-5 flex flex-wrap items-end gap-4">
        <div>
            <label class="field-label">Academic year</label>
            <select name="academic_year_id" class="select w-48">
                @foreach ($years as $option)
                    <option value="{{ $option->id }}" @selected($year?->id === $option->id)>{{ $option->label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="field-label">Year level</label>
            <select name="year_level" class="select w-40">
                <option value="">All years</option>
                @foreach ([1, 2, 3, 4] as $level)
                    <option value="{{ $level }}" @selected($yearLevel === $level)>Year {{ $level }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn-primary">Apply</button>
    </form>

    <div class="card">
        <div class="card-header">Cohort attainment</div>
        <table class="data-table">
            <thead>
                <tr><th>PLO</th><th>Outcome</th><th>Cohort mean</th><th>Students with evidence</th><th>At target</th><th>Standing</th></tr>
            </thead>
            <tbody>
                @foreach ($summary as $number => $row)
                    <tr>
                        <td class="font-semibold text-navy-800">
                            <a href="{{ route('chair.plo', $row['plo']) }}" class="underline underline-offset-2">PLO {{ $number }}</a>
                        </td>
                        <td>{{ $row['plo']->title }}</td>
                        <td class="font-medium">{{ $row['mean'] !== null ? number_format($row['mean'], 2) : '—' }}</td>
                        <td>{{ $row['students'] }}</td>
                        <td>
                            {{ $row['at_target'] }} ({{ $row['at_target_percent'] }}%)
                            <div class="meter w-24 mt-1"><div class="meter-fill" style="width: {{ $row['at_target_percent'] }}%"></div></div>
                        </td>
                        <td>
                            @if ($row['students'] === 0)
                                <span class="pill bg-slate-100 text-slate-600 ring-slate-300">No evidence</span>
                            @elseif ($row['meets_target'])
                                <span class="pill bg-emerald-50 text-status-ontrack ring-emerald-200">At target</span>
                            @else
                                <span class="pill bg-red-50 text-status-risk ring-red-200">Below target</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-5 flex gap-3">
        <a href="{{ route('chair.cqi') }}" class="btn-accent">Open improvement actions</a>
    </div>
@endsection
