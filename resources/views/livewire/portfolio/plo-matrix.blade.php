{{--
    Section 3. Student claim on the left, evaluator's validated figure on the
    right, so the gap between the two is visible while writing rather than
    afterwards.
--}}
<div class="card">
    <div class="card-header">PLO claims for year {{ $portfolio->year_level }}</div>

    <div class="px-5 py-3 bg-cream-100 text-sm text-slate-700 border-b border-cream-200">
        Expected level at the end of year {{ $portfolio->year_level }}:
        <span class="font-semibold">{{ $expected->label() }}</span>.
        Your claim is indirect evidence; only your evaluator's validation counts toward attainment.
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th class="w-16">PLO</th>
                <th>Outcome</th>
                <th class="w-56">Your claim</th>
                <th>Evidence behind the claim</th>
                <th class="w-28">Validated</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($plos as $plo)
                <tr>
                    <td class="font-semibold text-navy-800">{{ $plo->code() }}</td>
                    <td>
                        <p class="font-medium">{{ $plo->title }}</p>
                        <p class="text-xs text-slate-500">{{ $plo->statement }}</p>
                    </td>
                    <td>
                        <select wire:model="claims.{{ $plo->id }}" @disabled(! $canEdit) class="select">
                            <option value="">Not claimed</option>
                            @foreach ($levels as $level)
                                <option value="{{ $level->value }}">{{ $level->value }} — {{ $level->label() }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <input type="text" wire:model="notes.{{ $plo->id }}" @disabled(! $canEdit)
                               class="input" placeholder="Which output shows this?">
                    </td>
                    <td class="text-center">
                        @if (isset($validated[$plo->id]))
                            <span class="pill bg-emerald-50 text-status-ontrack ring-emerald-200">{{ number_format($validated[$plo->id], 2) }}</span>
                        @else
                            <span class="text-xs text-slate-400">Pending</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if ($canEdit)
        <div class="px-5 py-4 border-t border-slate-200">
            <button wire:click="save" class="btn-primary">Save claims</button>
        </div>
    @endif
</div>
