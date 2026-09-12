{{-- Section 12. Ten prompts, one set per major project. --}}
<div class="space-y-5">
    @if ($existing->isNotEmpty())
        <div class="card">
            <div class="card-header">Reflections written this year</div>
            <table class="data-table">
                <thead><tr><th>Subject</th><th>Complete</th><th></th></tr></thead>
                <tbody>
                    @foreach ($existing as $reflection)
                        <tr>
                            <td class="font-medium">{{ $reflection->subject }}</td>
                            <td>
                                <span class="pill {{ $reflection->isComplete() ? 'bg-emerald-50 text-status-ontrack ring-emerald-200' : 'bg-amber-100 text-amber-800 ring-amber-300' }}">
                                    {{ $reflection->isComplete() ? 'All ten answered' : 'Unfinished' }}
                                </span>
                            </td>
                            <td class="text-right">
                                <button wire:click="edit({{ $reflection->id }})" class="text-xs text-navy-700 underline underline-offset-2">Open</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if ($canEdit)
                <div class="px-5 py-3 border-t border-slate-200">
                    <button wire:click="startNew" class="btn-quiet">Start another reflection</button>
                </div>
            @endif
        </div>
    @endif

    <div class="card">
        <div class="card-header">{{ $reflectionId ? 'Editing reflection' : 'New reflection' }}</div>
        <div class="p-5 space-y-5">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="refl-subject" class="field-label">What are you reflecting on? <span class="text-status-risk">*</span></label>
                    <input id="refl-subject" type="text" wire:model="subject" @disabled(! $canEdit) class="input"
                           placeholder="Project or activity name">
                    @error('subject') <p class="text-sm text-status-risk mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="refl-project" class="field-label">Linked project</label>
                    <select id="refl-project" wire:model="projectId" @disabled(! $canEdit) class="select">
                        <option value="">Not linked</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}">{{ $project->title }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            @php $n = 1; @endphp
            @foreach ($prompts as $key => $prompt)
                <div>
                    <label for="refl-{{ $key }}" class="field-label">
                        {{ $n }}. {{ $prompt }}
                        @if (in_array($key, ['learned', 'problem_solved', 'plos_addressed'], true))
                            <span class="text-status-risk">*</span>
                        @endif
                    </label>
                    <textarea id="refl-{{ $key }}" wire:model="answers.{{ $key }}" rows="3" @disabled(! $canEdit) class="textarea"></textarea>
                    @error('answers.'.$key) <p class="text-sm text-status-risk mt-1">{{ $message }}</p> @enderror
                </div>
                @php $n++; @endphp
            @endforeach
        </div>

        @if ($canEdit)
            <div class="px-5 py-4 border-t border-slate-200">
                <button wire:click="save" class="btn-primary">Save reflection</button>
            </div>
        @endif
    </div>
</div>
