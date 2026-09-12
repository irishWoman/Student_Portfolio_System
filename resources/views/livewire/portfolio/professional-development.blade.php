{{-- Section 11. A certificate is the receipt; the competency is the evidence. --}}
<div class="space-y-5">
    <div class="card">
        <div class="card-header">{{ $editingId ? 'Edit activity' : 'Add activity' }}</div>
        <div class="p-5 grid gap-4 sm:grid-cols-2">
            <div>
                <label for="pd-kind" class="field-label">Type <span class="text-status-risk">*</span></label>
                <select id="pd-kind" wire:model="kind" @disabled(! $canEdit) class="select">
                    @foreach (['seminar', 'workshop', 'conference', 'certification', 'competition', 'hackathon', 'webinar', 'industry_training', 'leadership', 'publication', 'other'] as $option)
                        <option value="{{ $option }}">{{ str($option)->replace('_', ' ')->title() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="pd-title" class="field-label">Title <span class="text-status-risk">*</span></label>
                <input id="pd-title" type="text" wire:model="title" @disabled(! $canEdit) class="input">
                @error('title') <p class="text-sm text-status-risk mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="field-label">Organizer</label>
                <input type="text" wire:model="organizer" @disabled(! $canEdit) class="input">
            </div>
            <div class="flex gap-3">
                <div class="flex-1">
                    <label class="field-label">Date</label>
                    <input type="date" wire:model="heldOn" @disabled(! $canEdit) class="input">
                </div>
                <div class="w-28">
                    <label class="field-label">Hours</label>
                    <input type="number" wire:model="hours" @disabled(! $canEdit) class="input">
                </div>
            </div>
            <div class="sm:col-span-2">
                <label for="pd-competency" class="field-label">What can you now do that you could not before? <span class="text-status-risk">*</span></label>
                <textarea id="pd-competency" wire:model="competencyDemonstrated" rows="3" @disabled(! $canEdit) class="textarea"></textarea>
                <p class="field-help">Attendance alone earns no attainment. Describe the competency and where you applied it.</p>
                @error('competencyDemonstrated') <p class="text-sm text-status-risk mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
        @if ($canEdit)
            <div class="px-5 py-4 border-t border-slate-200">
                <button wire:click="save" class="btn-primary">{{ $editingId ? 'Update' : 'Add activity' }}</button>
            </div>
        @endif
    </div>

    <div class="card">
        <div class="card-header">Recorded activities ({{ $records->count() }})</div>
        @if ($records->isEmpty())
            <p class="p-5 text-sm text-slate-500">Nothing recorded yet.</p>
        @else
            <table class="data-table">
                <thead><tr><th>Activity</th><th>Type</th><th>Date</th><th>Competency demonstrated</th><th></th></tr></thead>
                <tbody>
                    @foreach ($records as $record)
                        <tr>
                            <td class="font-medium">{{ $record->title }}<span class="block text-xs text-slate-500">{{ $record->organizer }}</span></td>
                            <td class="text-xs">{{ $record->kindLabel() }}</td>
                            <td class="text-xs">{{ $record->held_on?->format('M j, Y') ?? '—' }}</td>
                            <td class="text-xs">{{ \Illuminate\Support\Str::limit($record->competency_demonstrated, 120) }}</td>
                            <td class="text-right whitespace-nowrap">
                                @if ($canEdit)
                                    <button wire:click="edit({{ $record->id }})" class="text-xs text-navy-700 underline underline-offset-2">Edit</button>
                                    <button wire:click="delete({{ $record->id }})" class="text-xs text-status-risk underline underline-offset-2 ml-2">Remove</button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
