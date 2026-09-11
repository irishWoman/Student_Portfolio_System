@extends('layouts.app')
@section('title', 'Portfolio '.$portfolio->academicYear->label)

@section('content')
    <a href="{{ route('portfolio.index') }}" class="text-sm text-navy-700 underline underline-offset-2">Back to current year</a>

    <div class="card mt-3 mb-5 p-5">
        <h1 class="text-lg font-semibold text-navy-800">Academic year {{ $portfolio->academicYear->label }}</h1>
        <p class="text-sm text-slate-600">Year {{ $portfolio->year_level }} · {{ $portfolio->completion_percent }}% complete</p>
        <div class="mt-3 flex gap-2">
            <a href="{{ route('export.pdf', $portfolio) }}" class="btn-quiet">Download PDF</a>
            <a href="{{ route('export.word', $portfolio) }}" class="btn-quiet">Download Word</a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Sections</div>
        <table class="data-table">
            <thead><tr><th>#</th><th>Section</th><th>Status</th><th>Completion</th></tr></thead>
            <tbody>
                @foreach ($portfolio->sectionEntries as $entry)
                    <tr>
                        <td>{{ $entry->section_number }}</td>
                        <td><a href="{{ route('portfolio.section', [$portfolio, $entry->section_number]) }}" class="text-navy-700 underline underline-offset-2">{{ $entry->title() }}</a></td>
                        <td><span class="pill {{ $entry->status->badgeClasses() }}">{{ $entry->status->label() }}</span></td>
                        <td>{{ $entry->completion_percent }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
