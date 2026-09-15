{{-- 2x2 ID photo, shown once above Section 1's own answers. --}}
<div class="card mb-5">
    <div class="card-header">2x2 ID photo</div>

    <div class="p-5 flex flex-col sm:flex-row gap-5 items-start">
        <div class="w-32 h-32 shrink-0 border border-slate-300 bg-slate-50 flex items-center justify-center overflow-hidden">
            @if ($photo)
                <img src="{{ $photo->temporaryUrl() }}" alt="Photo preview" class="w-full h-full object-cover">
            @elseif ($student->photo_path)
                <img src="{{ $student->photoUrl() }}" alt="{{ $student->fullName() }}" class="w-full h-full object-cover">
            @else
                <span class="text-xs text-slate-400 text-center px-2">No photo yet</span>
            @endif
        </div>

        @if ($canEdit)
            <div class="flex-1 min-w-0">
                <label for="profile-photo" class="field-label">Upload a 2x2 photo</label>
                <input id="profile-photo" type="file" wire:model="photo" accept="image/*" class="input">
                @error('photo') <p class="text-sm text-status-risk mt-1">{{ $message }}</p> @enderror
                <p class="field-help">JPG or PNG, up to 2 MB. Used for identification on your printed portfolio.</p>

                <div wire:loading wire:target="photo" class="text-xs text-slate-500 mt-1">Uploading…</div>

                @if ($photo)
                    <button type="button" wire:click="save" class="btn-primary mt-3">Save photo</button>
                @endif
            </div>
        @elseif (! $student->photo_path)
            <p class="text-sm text-slate-500">No photo on file.</p>
        @endif
    </div>
</div>
