{{--
    Generic field-driven section editor. Every input is built from the config
    array, so this one file renders sections 1, 2, 7 and 8.
--}}
<div class="card">
    <div class="card-header flex items-center justify-between">
        <span>Your answers</span>
        @if ($savedAt)
            <span class="text-xs font-normal text-white/70">Saved {{ $savedAt }}</span>
        @endif
    </div>

    <div class="p-5 space-y-5">
        @error('answers') <p class="text-sm text-status-risk">{{ $message }}</p> @enderror

        @foreach ($this->fields() as $field)
            <div>
                <label class="field-label" for="field-{{ $field['name'] }}">
                    {{ $field['label'] }}
                    @if ($field['required'] ?? false)
                        <span class="text-status-risk">*</span>
                    @endif
                </label>

                @if (($field['type'] ?? 'text') === 'textarea')
                    <textarea id="field-{{ $field['name'] }}"
                              wire:model.live.debounce.800ms="answers.{{ $field['name'] }}"
                              rows="{{ $field['rows'] ?? 3 }}"
                              @disabled(! $canEdit)
                              class="textarea"></textarea>
                @else
                    <input id="field-{{ $field['name'] }}" type="text"
                           wire:model.live.debounce.800ms="answers.{{ $field['name'] }}"
                           @disabled(! $canEdit)
                           class="input">
                @endif

                @if ($field['help'] ?? false)
                    <p class="field-help">{{ $field['help'] }}</p>
                @endif
            </div>
        @endforeach
    </div>

    @if ($canEdit)
        <div class="px-5 py-4 border-t border-slate-200 flex items-center gap-3">
            <button wire:click="save" class="btn-quiet">Save draft</button>
            <button wire:click="submitSection" class="btn-primary">Mark section ready</button>
            <span class="text-xs text-slate-500">Answers save automatically as you type.</span>
        </div>
    @endif
</div>
