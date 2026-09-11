@extends('layouts.app')
@section('title', 'Review portfolio')

@section('content')
    <a href="{{ route('faculty.queue') }}" class="text-sm text-navy-700 underline underline-offset-2">Back to queue</a>

    {{-- Student header --}}
    <div class="card mt-3 mb-5">
        <div class="h-1 bg-amber-500"></div>
        <div class="p-5 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-lg font-semibold text-navy-800">{{ $portfolio->student->fullName() }}</h1>
                <p class="text-sm text-slate-600">
                    {{ $portfolio->student->student_number }} · Year {{ $portfolio->year_level }}
                    · {{ $portfolio->academicYear->label }}
                </p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('export.pdf', $portfolio) }}" class="btn-quiet text-xs">PDF</a>
                <a href="{{ route('export.word', $portfolio) }}" class="btn-quiet text-xs">Word</a>
            </div>
        </div>
    </div>

    {{-- What the student wrote, section by section --}}
    <div class="card mb-5">
        <div class="card-header">Sections</div>
        <table class="data-table">
            <thead><tr><th>#</th><th>Section</th><th>Completion</th><th></th></tr></thead>
            <tbody>
                @foreach ($portfolio->sectionEntries as $entry)
                    <tr>
                        <td>{{ $entry->section_number }}</td>
                        <td>{{ $entry->title() }}</td>
                        <td>{{ $entry->completion_percent }}%</td>
                        <td class="text-right">
                            <a href="{{ route('portfolio.section', [$portfolio, $entry->section_number]) }}"
                               class="text-xs text-navy-700 underline underline-offset-2">Open</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Evidence quality rating --}}
    <div class="card mb-5">
        <div class="card-header">Evidence quality</div>
        @if ($portfolio->evidenceFiles->isEmpty())
            <p class="p-5 text-sm text-slate-500">No files attached.</p>
        @else
            <table class="data-table">
                <thead><tr><th>Evidence</th><th>PLOs</th><th class="w-64">Quality rating</th><th>Remarks</th></tr></thead>
                <tbody>
                    @foreach ($portfolio->evidenceFiles as $file)
                        <tr>
                            <td>
                                <a href="{{ route('evidence.download', $file) }}" class="font-medium text-navy-700 underline underline-offset-2">{{ $file->title }}</a>
                                <span class="block text-xs text-slate-500">{{ $file->original_name }} · {{ $file->humanSize() }}</span>
                            </td>
                            <td class="text-xs">{{ $file->plos->map(fn ($p) => $p->number)->implode(', ') ?: '—' }}</td>
                            <td colspan="2">
                                <form method="POST" action="{{ route('faculty.evidence.rate', $file) }}" class="flex flex-wrap items-center gap-2">
                                    @csrf
                                    <select name="quality_rating" class="select w-44">
                                        @foreach (\App\Support\Enums\EvidenceQuality::cases() as $quality)
                                            <option value="{{ $quality->value }}" @selected($file->quality_rating === $quality->value)>
                                                {{ $quality->value }} — {{ $quality->label() }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <input name="quality_remarks" class="input flex-1 min-w-48" value="{{ $file->quality_remarks }}" placeholder="Remarks">
                                    <button class="btn-quiet text-xs">Save</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- Assessment form and level validation --}}
    @livewire('faculty.evaluation-form', ['portfolio' => $portfolio])

    {{-- Current attainment picture --}}
    <div class="card my-5">
        <div class="card-header">Attainment after validation</div>
        <table class="data-table">
            <thead><tr><th>PLO</th><th>Outcome</th><th>Direct score</th><th>Valid evidence</th><th>Flag</th></tr></thead>
            <tbody>
                @foreach ($snapshots as $number => $snapshot)
                    <tr>
                        <td class="font-semibold text-navy-800">PLO {{ $number }}</td>
                        <td>{{ $snapshot->plo->title }}</td>
                        <td>{{ $snapshot->direct_score !== null ? number_format((float) $snapshot->direct_score, 2) : '—' }}</td>
                        <td>{{ $snapshot->valid_evidence_count }}</td>
                        <td><span class="pill {{ $snapshot->flagClasses() }}">{{ $snapshot->flagLabel() }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Decision --}}
    <div class="card">
        <div class="card-header">Decision</div>
        <form method="POST" action="{{ route('faculty.decide', $portfolio) }}" class="p-5 space-y-4">
            @csrf
            <div>
                <label class="field-label">Notes for the student</label>
                <textarea name="overall_remarks" rows="3" class="textarea">{{ old('overall_remarks', $portfolio->overall_remarks) }}</textarea>
                <p class="field-help">Required when returning the portfolio: say what needs fixing.</p>
            </div>
            <div class="flex gap-3">
                <button name="decision" value="validated" class="btn-primary">Validate portfolio</button>
                <button name="decision" value="returned" class="btn-quiet">Return for revision</button>
            </div>
        </form>
    </div>
@endsection
