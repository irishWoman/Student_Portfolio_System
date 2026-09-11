{{--
    Section 10. Each field shows the PLO it evidences, so the mapping is visible
    while writing rather than discovered at assessment time.
--}}
<div class="space-y-5">
    <div class="card">
        <div class="card-header flex items-center justify-between">
            <span>Capstone project</span>
            @if ($savedAt)<span class="text-xs font-normal text-white/70">Saved {{ $savedAt }}</span>@endif
        </div>
        <div class="p-5 grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="field-label">Title</label>
                <input type="text" wire:model="title" @disabled(! $canEdit) class="input">
                @error('title') <p class="text-sm text-status-risk mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="field-label">Adviser</label>
                <input type="text" wire:model="adviserName" @disabled(! $canEdit) class="input">
            </div>
            <div class="flex gap-3">
                <div class="flex-1">
                    <label class="field-label">Proposal defense</label>
                    <input type="date" wire:model="proposalDefendedOn" @disabled(! $canEdit) class="input">
                </div>
                <div class="flex-1">
                    <label class="field-label">Final defense</label>
                    <input type="date" wire:model="finalDefendedOn" @disabled(! $canEdit) class="input">
                    @error('finalDefendedOn') <p class="text-sm text-status-risk mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Capstone documentation</div>
        <div class="divide-y divide-slate-100">
            @foreach ($labels as $field => $label)
                <div class="p-5 grid gap-3 sm:grid-cols-[13rem_1fr]">
                    <div>
                        <p class="font-medium text-slate-800">{{ $label }}</p>
                        <span class="pill bg-navy-100 text-navy-800 ring-navy-100 mt-1">PLO {{ $map[$field] }}</span>
                    </div>
                    <textarea wire:model="fields.{{ $field }}" rows="3" @disabled(! $canEdit) class="textarea"></textarea>
                </div>
            @endforeach
        </div>
        @if ($canEdit)
            <div class="px-5 py-4 border-t border-slate-200">
                <button wire:click="save" class="btn-primary">Save capstone</button>
            </div>
        @endif
    </div>
</div>
