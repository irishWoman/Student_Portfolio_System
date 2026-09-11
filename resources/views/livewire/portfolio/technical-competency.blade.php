{{-- Section 5. Competency grid grouped by category. --}}
<div class="space-y-5">
    @foreach ($categories as $category)
        <div class="card">
            <div class="card-header">{{ $category->name }}</div>
            <table class="data-table">
                <thead>
                    <tr><th class="w-1/4">Competency</th><th class="w-56">Stage reached</th><th>Evidence</th><th class="w-32">Validated</th></tr>
                </thead>
                <tbody>
                    @foreach ($category->competencies as $competency)
                        <tr>
                            <td class="font-medium">{{ $competency->name }}</td>
                            <td>
                                <select wire:model="stages.{{ $competency->id }}" @disabled(! $canEdit) class="select">
                                    <option value="">Not claimed</option>
                                    @foreach ($stageOptions as $stage)
                                        <option value="{{ $stage->value }}">{{ $stage->label() }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="text" wire:model="notes.{{ $competency->id }}" @disabled(! $canEdit)
                                       class="input" placeholder="Which output shows this?">
                            </td>
                            <td class="text-center text-xs">
                                {{ $validated[$competency->id]?->validated_stage?->label() ?? '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach

    @if ($canEdit)
        <button wire:click="save" class="btn-primary">Save competencies</button>
    @endif
</div>
