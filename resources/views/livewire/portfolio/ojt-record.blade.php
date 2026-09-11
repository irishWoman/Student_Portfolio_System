{{-- Section 9. Placement details plus the supervisor's one-time evaluation link. --}}
<div class="space-y-5">
    <div class="card">
        <div class="card-header">Placement</div>
        <div class="p-5 grid gap-4 sm:grid-cols-2">
            <div>
                <label class="field-label">Company</label>
                <input type="text" wire:model="form.company_name" @disabled(! $canEdit) class="input">
                @error('form.company_name') <p class="text-sm text-status-risk mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="field-label">Industry</label>
                <input type="text" wire:model="form.industry" @disabled(! $canEdit) class="input">
            </div>
            <div class="sm:col-span-2">
                <label class="field-label">Address</label>
                <input type="text" wire:model="form.company_address" @disabled(! $canEdit) class="input">
            </div>
            <div>
                <label class="field-label">Supervisor</label>
                <input type="text" wire:model="form.supervisor_name" @disabled(! $canEdit) class="input">
                @error('form.supervisor_name') <p class="text-sm text-status-risk mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="field-label">Supervisor position</label>
                <input type="text" wire:model="form.supervisor_position" @disabled(! $canEdit) class="input">
            </div>
            <div>
                <label class="field-label">Supervisor email</label>
                <input type="email" wire:model="form.supervisor_email" @disabled(! $canEdit) class="input">
                @error('form.supervisor_email') <p class="text-sm text-status-risk mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="flex gap-3">
                <div class="flex-1">
                    <label class="field-label">Started</label>
                    <input type="date" wire:model="form.started_on" @disabled(! $canEdit) class="input">
                </div>
                <div class="flex-1">
                    <label class="field-label">Ended</label>
                    <input type="date" wire:model="form.ended_on" @disabled(! $canEdit) class="input">
                </div>
            </div>
            <div>
                <label class="field-label">Hours completed (of 240)</label>
                <input type="number" wire:model="form.completed_hours" @disabled(! $canEdit) class="input">
                @if ($record)
                    <div class="meter mt-2"><div class="meter-fill" style="width: {{ $record->hoursProgress() }}%"></div></div>
                @endif
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Your work</div>
        <div class="p-5 space-y-4">
            <div>
                <label class="field-label">Objectives you set</label>
                <textarea wire:model="form.objectives" rows="2" @disabled(! $canEdit) class="textarea"></textarea>
            </div>
            <div>
                <label class="field-label">Assigned responsibilities</label>
                <textarea wire:model="form.responsibilities" rows="3" @disabled(! $canEdit) class="textarea"></textarea>
                @error('form.responsibilities') <p class="text-sm text-status-risk mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="field-label">Work outputs</label>
                <textarea wire:model="form.work_outputs" rows="3" @disabled(! $canEdit) class="textarea"></textarea>
            </div>
            <div>
                <label class="field-label">Your reflection on the immersion</label>
                <textarea wire:model="form.student_reflection" rows="4" @disabled(! $canEdit) class="textarea"></textarea>
            </div>
        </div>
        @if ($canEdit)
            <div class="px-5 py-4 border-t border-slate-200">
                <button wire:click="save" class="btn-primary">Save placement</button>
            </div>
        @endif
    </div>

    <div class="card">
        <div class="card-header">Supervisor evaluation</div>
        <div class="p-5">
            @if ($record?->supervisorEvaluation?->submitted_at)
                <p class="text-sm text-slate-700">
                    Received {{ $record->supervisorEvaluation->submitted_at->format('F j, Y') }}
                    from {{ $record->supervisorEvaluation->signed_by }} ·
                    mean rating {{ $record->supervisorEvaluation->meanRating() }}/4.
                </p>
            @elseif ($supervisorLink)
                <p class="text-sm text-slate-600 mb-2">
                    Send this link to your supervisor. It opens the rating form once, and needs no account.
                </p>
                <div class="flex flex-wrap items-center gap-2">
                    <code class="text-xs bg-slate-100 px-2 py-1 break-all">{{ $supervisorLink }}</code>
                    <button data-copy="{{ $supervisorLink }}" class="btn-quiet text-xs">Copy link</button>
                </div>
            @else
                <p class="text-sm text-slate-600 mb-3">Save your placement details first, then generate the supervisor's link.</p>
                @if ($canEdit)
                    <button wire:click="issueSupervisorLink" class="btn-accent">Generate supervisor link</button>
                @endif
            @endif
        </div>
    </div>
</div>
