{{--
    PLO checkbox list with each outcome's title and statement shown beside its
    number. Most students cannot recite what "PLO 7" means, so choosing one
    from a bare number is guesswork.

    Params: $plos, and either $name (plain form input) or $wireModel (Livewire).
    Optional: $disabled.
--}}
<div class="grid gap-2 sm:grid-cols-2">
    @foreach ($plos as $plo)
        <label class="flex items-start gap-3 p-3 bg-white border border-slate-200 hover:border-navy-600 cursor-pointer transition">
            <input type="checkbox" value="{{ $plo->id }}"
                   @isset($name) name="{{ $name }}" @endisset
                   @isset($wireModel) wire:model="{{ $wireModel }}" @endisset
                   @disabled($disabled ?? false)
                   class="mt-0.5 shrink-0 border-slate-300 text-navy-800 focus:ring-navy-600">
            <span class="min-w-0">
                <span class="block text-sm font-semibold text-navy-800">{{ $plo->code() }} · {{ $plo->title }}</span>
                <span class="block text-xs text-slate-500 mt-0.5">{{ $plo->statement }}</span>
            </span>
        </label>
    @endforeach
</div>
