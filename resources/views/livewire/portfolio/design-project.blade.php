{{-- Section 6. The 20 design-cycle steps, in order, autosaving individually. --}}
<div class="space-y-5">
    <div class="card">
        <div class="card-header flex items-center justify-between">
            <span>Project details</span>
            @if ($savedAt)<span class="text-xs font-normal text-white/70">Saved {{ $savedAt }}</span>@endif
        </div>
        <div class="p-5 grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="dp-title" class="field-label">Project title <span class="text-status-risk">*</span></label>
                <input id="dp-title" type="text" wire:model="title" @disabled(! $canEdit) class="input">
                @error('title') <p class="text-sm text-status-risk mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="field-label">Course</label>
                <select wire:model="courseId" @disabled(! $canEdit) class="select">
                    <option value="">Not tied to a course</option>
                    @foreach ($courses as $course)
                        <option value="{{ $course->id }}">{{ $course->code }} — {{ $course->title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-3">
                <div class="flex-1">
                    <label class="field-label">Your role</label>
                    <input type="text" wire:model="role" @disabled(! $canEdit) class="input" placeholder="Team lead, member">
                </div>
                <div class="w-28">
                    <label class="field-label">Team size</label>
                    <input type="number" wire:model="teamSize" @disabled(! $canEdit) class="input">
                </div>
            </div>
            <div class="sm:col-span-2">
                <label class="field-label">One-paragraph summary</label>
                <textarea wire:model="summary" rows="2" @disabled(! $canEdit) class="textarea"></textarea>
            </div>
        </div>
        <div class="px-5 py-3 border-t border-slate-200 flex items-center gap-4">
            @if ($canEdit)<button wire:click="save" class="btn-primary">Save project</button>@endif
            <div class="flex-1">
                <div class="meter"><div class="meter-fill" style="width: {{ $completion }}%"></div></div>
                <p class="text-xs text-slate-500 mt-1">{{ $completion }}% of the design cycle written</p>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Design cycle</div>
        <div class="divide-y divide-slate-100">
            @php $i = 1; @endphp
            @foreach ($definitions as $key => $label)
                <div class="p-5 grid gap-3 sm:grid-cols-[13rem_1fr]">
                    <div>
                        <p class="text-xs text-slate-400">Step {{ $i }}</p>
                        <p class="font-medium text-slate-800">{{ $label }}</p>
                    </div>
                    <textarea wire:model.live.debounce.1000ms="elements.{{ $key }}" rows="3"
                              @disabled(! $canEdit) class="textarea"></textarea>
                </div>
                @php $i++; @endphp
            @endforeach
        </div>
    </div>
</div>
