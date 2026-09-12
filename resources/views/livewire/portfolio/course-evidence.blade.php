{{-- Section 4. Repeater: one row per course output offered as evidence. --}}
<div class="space-y-5">

    <div class="card">
        <div class="card-header">{{ $editingId ? 'Edit entry' : 'Add course evidence' }}</div>

        <div class="p-5 grid gap-4 sm:grid-cols-2">
            <div>
                <label for="ce-course" class="field-label">Course <span class="text-status-risk">*</span></label>
                <select id="ce-course" wire:model="courseId" @disabled(! $canEdit) class="select">
                    <option value="">Choose a course</option>
                    @foreach ($courses as $course)
                        <option value="{{ $course->id }}">{{ $course->code }} — {{ $course->title }} (Y{{ $course->year_level }})</option>
                    @endforeach
                </select>
                @error('courseId') <p class="text-sm text-status-risk mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="ce-clo" class="field-label">Course learning outcome <span class="text-status-risk">*</span></label>
                <input id="ce-clo" type="text" wire:model="cloStatement" @disabled(! $canEdit) class="input"
                       placeholder="e.g. Design and simulate a combinational circuit">
                @error('cloStatement') <p class="text-sm text-status-risk mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="ce-activity" class="field-label">Assessment activity <span class="text-status-risk">*</span></label>
                <input id="ce-activity" type="text" wire:model="activity" @disabled(! $canEdit) class="input"
                       placeholder="Lab project, programming project, practical">
                @error('activity') <p class="text-sm text-status-risk mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="ce-output" class="field-label">Your output <span class="text-status-risk">*</span></label>
                <input id="ce-output" type="text" wire:model="output" @disabled(! $canEdit) class="input"
                       placeholder="What you produced">
                @error('output') <p class="text-sm text-status-risk mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex gap-3">
                <div class="flex-1">
                    <label for="ce-score" class="field-label">Score</label>
                    <input id="ce-score" type="number" step="0.01" wire:model="score" @disabled(! $canEdit) class="input">
                </div>
                <div class="flex-1">
                    <label for="ce-score-max" class="field-label">Out of</label>
                    <input id="ce-score-max" type="number" step="0.01" wire:model="scoreMax" @disabled(! $canEdit) class="input">
                </div>
            </div>

            <div>
                <label for="ce-level" class="field-label">Level you think this shows <span class="text-status-risk">*</span></label>
                <select id="ce-level" wire:model="claimedLevel" @disabled(! $canEdit) class="select">
                    <option value="">Choose a level</option>
                    @foreach (\App\Support\Enums\AttainmentLevel::cases() as $level)
                        <option value="{{ $level->value }}">{{ $level->value }} — {{ $level->label() }}</option>
                    @endforeach
                </select>
                @error('claimedLevel') <p class="text-sm text-status-risk mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="sm:col-span-2">
                <label class="field-label">PLOs this output demonstrates <span class="text-status-risk">*</span></label>
                <div class="flex flex-wrap gap-2">
                    @foreach ($plos as $plo)
                        <label class="pill bg-white ring-slate-300 cursor-pointer">
                            <input type="checkbox" wire:model="selectedPlos" value="{{ $plo->id }}"
                                   @disabled(! $canEdit) class="mr-1 border-slate-300 text-navy-800 focus:ring-navy-600">
                            {{ $plo->code() }}
                        </label>
                    @endforeach
                </div>
                @error('selectedPlos') <p class="text-sm text-status-risk mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="sm:col-span-2">
                <label class="field-label">What you would do differently</label>
                <textarea wire:model="reflectionNote" rows="2" @disabled(! $canEdit) class="textarea"></textarea>
            </div>
        </div>

        @if ($canEdit)
            <div class="px-5 py-4 border-t border-slate-200 flex gap-3">
                <button wire:click="save" class="btn-primary">{{ $editingId ? 'Update entry' : 'Add entry' }}</button>
                @if ($editingId)
                    <button wire:click="resetForm" class="btn-quiet">Cancel</button>
                @endif
            </div>
        @endif
    </div>

    <div class="card">
        <div class="card-header">Entries ({{ $records->count() }})</div>

        @error('records') <p class="px-5 py-3 text-sm text-status-risk">{{ $message }}</p> @enderror

        @if ($records->isEmpty())
            <p class="p-5 text-sm text-slate-500">
                Nothing yet. Three to five strong outputs a year is the target, not everything you produced.
            </p>
        @else
            <table class="data-table">
                <thead>
                    <tr><th>Course</th><th>Output</th><th>PLOs</th><th>Score</th><th>Your claim</th><th>Validated</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach ($records as $record)
                        <tr>
                            <td class="font-medium">{{ $record->course?->code }}</td>
                            <td>
                                {{ $record->output_title }}
                                <span class="block text-xs text-slate-500">{{ $record->assessment_activity }}</span>
                            </td>
                            <td class="text-xs">{{ $record->plos->map(fn ($p) => $p->number)->implode(', ') }}</td>
                            <td>{{ $record->score ? $record->score.'/'.$record->score_max : '—' }}</td>
                            <td>{{ $record->claimed_level ? \App\Support\Enums\AttainmentLevel::from($record->claimed_level)->label() : '—' }}</td>
                            <td>
                                @if ($record->validated_level)
                                    <span class="pill bg-emerald-50 text-status-ontrack ring-emerald-200">
                                        {{ \App\Support\Enums\AttainmentLevel::from($record->validated_level)->label() }}
                                    </span>
                                @else
                                    <span class="text-xs text-slate-400">Pending</span>
                                @endif
                            </td>
                            <td class="text-right whitespace-nowrap">
                                @if ($canEdit)
                                    <button wire:click="edit({{ $record->id }})" class="text-xs text-navy-700 underline underline-offset-2">Edit</button>
                                    @unless ($record->isValidated())
                                        <button wire:click="delete({{ $record->id }})" class="text-xs text-status-risk underline underline-offset-2 ml-2">Remove</button>
                                    @endunless
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
