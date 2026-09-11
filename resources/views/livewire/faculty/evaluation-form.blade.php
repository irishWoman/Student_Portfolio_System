{{--
    Faculty assessment form (Section X) plus level validation. Two jobs on one
    screen because evaluators do both in the same sitting.
--}}
<div class="space-y-5">

    <div class="card">
        <div class="card-header">Validate claimed levels</div>
        <p class="px-5 py-3 text-sm text-slate-600 bg-cream-100 border-b border-cream-200">
            Nothing counts toward attainment until it is validated here.
        </p>

        @if ($records->isEmpty())
            <p class="p-5 text-sm text-slate-500">This student has not submitted course evidence yet.</p>
        @else
            <table class="data-table">
                <thead><tr><th>Course</th><th>Output</th><th>PLOs</th><th>Score</th><th>Claimed</th><th class="w-56">Validated level</th></tr></thead>
                <tbody>
                    @foreach ($records as $record)
                        <tr>
                            <td class="font-medium">{{ $record->course?->code }}</td>
                            <td>{{ $record->output_title }}</td>
                            <td class="text-xs">{{ $record->plos->map(fn ($p) => $p->number)->implode(', ') }}</td>
                            <td>{{ $record->score ? $record->score.'/'.$record->score_max : '—' }}</td>
                            <td class="text-xs">{{ $record->claimed_level ? \App\Support\Enums\AttainmentLevel::from($record->claimed_level)->label() : '—' }}</td>
                            <td>
                                <select wire:model="validatedLevels.{{ $record->id }}" class="select">
                                    <option value="">Not yet decided</option>
                                    @foreach (\App\Support\Enums\AttainmentLevel::cases() as $level)
                                        <option value="{{ $level->value }}">{{ $level->value }} — {{ $level->label() }}</option>
                                    @endforeach
                                </select>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="px-5 py-4 border-t border-slate-200">
                <button wire:click="validateLevels" class="btn-primary">Save validated levels</button>
            </div>
        @endif
    </div>

    <div class="card">
        <div class="card-header">Faculty assessment</div>
        <div class="p-5 space-y-4">
            <div>
                <label class="field-label">Artifact assessed</label>
                <input type="text" wire:model="artifact" class="input" placeholder="e.g. Design review and defense, CPE401">
                @error('artifact') <p class="text-sm text-status-risk mt-1">{{ $message }}</p> @enderror
            </div>

            <table class="data-table">
                <thead><tr><th>Criterion</th><th class="w-40">Rating</th><th>Comments</th></tr></thead>
                <tbody>
                    @foreach ($criteria as $key => $label)
                        <tr>
                            <td class="font-medium">{{ $label }}</td>
                            <td>
                                <div class="flex gap-3">
                                    @for ($i = 1; $i <= 4; $i++)
                                        <label class="text-xs flex items-center gap-1">
                                            <input type="radio" wire:model.live="ratings.{{ $key }}" value="{{ $i }}"
                                                   class="border-slate-300 text-navy-800 focus:ring-navy-600">
                                            {{ $i }}
                                        </label>
                                    @endfor
                                </div>
                            </td>
                            <td>
                                <input type="text" wire:model="comments.{{ $key }}" class="input"
                                       placeholder="{{ ($ratings[$key] ?? 4) < config('portfolio.comment_below') ? 'Required for this rating' : 'Optional' }}">
                                @error('comments.'.$key) <p class="text-xs text-status-risk mt-1">{{ $message }}</p> @enderror
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div>
                <label class="field-label">Overall comment</label>
                <textarea wire:model="overallComment" rows="3" class="textarea"></textarea>
            </div>
        </div>
        <div class="px-5 py-4 border-t border-slate-200">
            <button wire:click="saveEvaluation" class="btn-primary">Record assessment</button>
        </div>
    </div>

    @if ($evaluations->isNotEmpty())
        <div class="card">
            <div class="card-header">Assessments on record</div>
            <table class="data-table">
                <thead><tr><th>Artifact</th><th>Evaluator</th><th>Date</th><th>Mean rating</th></tr></thead>
                <tbody>
                    @foreach ($evaluations as $evaluation)
                        <tr>
                            <td>{{ $evaluation->artifact_assessed }}</td>
                            <td class="text-xs">{{ $evaluation->evaluator->displayName() }}</td>
                            <td class="text-xs">{{ $evaluation->evaluated_on->format('M j, Y') }}</td>
                            <td>{{ $evaluation->overall_rating }}/4</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
