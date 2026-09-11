@extends('layouts.app')
@section('title', 'Improvement actions')

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4 mb-5">
        <div>
            <h1 class="text-lg font-semibold text-navy-800">Continuous quality improvement</h1>
            <p class="text-sm text-slate-600">
                Gaps are drafted from current attainment data. Cause, intervention and owner are yours to write.
            </p>
        </div>
        <form method="POST" action="{{ route('chair.cqi.generate') }}">
            @csrf
            <button class="btn-accent">Draft gaps from current data</button>
        </form>
    </div>

    @if ($actions->isEmpty())
        <div class="card p-5 text-sm text-slate-500">
            No improvement actions on record for {{ $year?->label }}. Draft them from the button above.
        </div>
    @endif

    @foreach ($actions as $action)
        <div class="card mb-4">
            <div class="card-header flex items-center justify-between">
                <span>{{ $action->plo->code() }} — {{ $action->plo->title }}</span>
                <span class="text-xs font-normal text-white/70">{{ str($action->status)->replace('_', ' ')->title() }}</span>
            </div>

            <div class="p-5 space-y-4">
                <div class="bg-cream-100 border-l-4 border-amber-500 px-4 py-3 text-sm">
                    <p class="font-medium">{{ $action->gap_description }}</p>
                    <p class="text-slate-600 mt-1">{{ $action->evidence_summary }}</p>
                </div>

                <form method="POST" action="{{ route('chair.cqi.update', $action) }}" class="grid gap-4 sm:grid-cols-2">
                    @csrf @method('PUT')

                    <div class="sm:col-span-2">
                        <label class="field-label">Possible cause</label>
                        <textarea name="possible_cause" rows="2" class="textarea">{{ $action->possible_cause }}</textarea>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="field-label">Intervention</label>
                        <textarea name="intervention" rows="2" class="textarea">{{ $action->intervention }}</textarea>
                    </div>
                    <div>
                        <label class="field-label">Responsible unit</label>
                        <input name="responsible_unit" class="input" value="{{ $action->responsible_unit }}">
                    </div>
                    <div class="flex gap-3">
                        <div class="flex-1">
                            <label class="field-label">Target date</label>
                            <input type="date" name="target_date" class="input" value="{{ $action->target_date?->toDateString() }}">
                        </div>
                        <div class="flex-1">
                            <label class="field-label">Reassess on</label>
                            <input type="date" name="reassessment_date" class="input" value="{{ $action->reassessment_date?->toDateString() }}">
                        </div>
                    </div>
                    <div>
                        <label class="field-label">Status</label>
                        <select name="status" class="select">
                            @foreach (['open' => 'Open', 'in_progress' => 'In progress', 'closed' => 'Closed'] as $value => $label)
                                <option value="{{ $value }}" @selected($action->status === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="field-label">Reassessed score</label>
                        <input type="number" step="0.01" name="reassessed_score" class="input" value="{{ $action->reassessed_score }}">
                        @if ($action->delta() !== null)
                            <p class="field-help">
                                Change since baseline: {{ $action->delta() > 0 ? '+' : '' }}{{ $action->delta() }}
                            </p>
                        @endif
                    </div>
                    <div class="sm:col-span-2">
                        <button class="btn-primary">Save action</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@endsection
