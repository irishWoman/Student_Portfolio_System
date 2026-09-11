{{--
    Curriculum mapping screen: PLO coverage across the program, a filterable
    course list, and the CLO editor for the selected course.
--}}
<div class="space-y-5">

    {{-- ---------------------------------------------------- Coverage strip --}}
    <div class="card">
        <div class="card-header">PLO coverage across the curriculum</div>
        <p class="px-5 py-3 text-sm text-slate-600 bg-cream-100 border-b border-cream-200">
            An outcome no course claims to teach cannot be attained. Red means nothing in the checklist maps to it.
        </p>
        <div class="p-4 grid gap-2 grid-cols-2 sm:grid-cols-4 lg:grid-cols-7">
            @foreach ($coverage as $number => $row)
                <div class="border px-3 py-2 {{ $row['courses'] === 0 ? 'border-red-300 bg-red-50' : 'border-slate-200 bg-white' }}">
                    <p class="text-xs font-semibold {{ $row['courses'] === 0 ? 'text-status-risk' : 'text-navy-800' }}">
                        PLO {{ $number }}
                    </p>
                    <p class="text-[11px] text-slate-500 leading-tight">{{ \Illuminate\Support\Str::limit($row['plo']->title, 28) }}</p>
                    <p class="text-xs mt-1">
                        {{ $row['courses'] }} course{{ $row['courses'] === 1 ? '' : 's' }}
                        @if ($row['highest'])
                            <span class="text-slate-400">· to level {{ $row['highest'] }}</span>
                        @endif
                    </p>
                </div>
            @endforeach
        </div>
    </div>

    <div class="grid gap-5 lg:grid-cols-[22rem_1fr] items-start">

        {{-- -------------------------------------------------- Course list --}}
        <div class="card">
            <div class="card-header">Courses</div>

            <div class="p-4 space-y-3 border-b border-slate-200">
                <input type="text" wire:model.live.debounce.400ms="search" class="input" placeholder="Search code or title">

                <div class="flex gap-2">
                    <select wire:model.live="yearFilter" class="select">
                        <option value="">All years</option>
                        @foreach ([1, 2, 3, 4] as $level)
                            <option value="{{ $level }}">Year {{ $level }}</option>
                        @endforeach
                    </select>
                    <select wire:model.live="termFilter" class="select">
                        <option value="">All terms</option>
                        <option value="first_semester">First sem</option>
                        <option value="second_semester">Second sem</option>
                        <option value="summer">Summer</option>
                    </select>
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" wire:model.live="majorsOnly" class="border-slate-300 text-navy-800 focus:ring-navy-600">
                    Major courses only
                </label>
            </div>

            <div class="max-h-[32rem] overflow-y-auto divide-y divide-slate-100">
                @forelse ($courses as $item)
                    <button wire:click="selectCourse({{ $item->id }})"
                            class="w-full text-left px-4 py-3 hover:bg-cream-50 transition {{ $courseId === $item->id ? 'bg-navy-100 border-l-2 border-amber-500' : '' }}">
                        <p class="text-sm font-medium text-slate-800">{{ $item->code }}</p>
                        <p class="text-xs text-slate-500">{{ $item->title }}</p>
                        <p class="text-[11px] mt-1 {{ $item->learning_outcomes_count ? 'text-slate-500' : 'text-status-risk' }}">
                            Year {{ $item->year_level }} ·
                            {{ $item->learning_outcomes_count }} CLO{{ $item->learning_outcomes_count === 1 ? '' : 's' }}
                        </p>
                    </button>
                @empty
                    <p class="p-4 text-sm text-slate-500">No courses match those filters.</p>
                @endforelse
            </div>
        </div>

        {{-- ------------------------------------------------- CLO editor --}}
        <div class="space-y-5">
            @if (! $course)
                <div class="card p-5 text-sm text-slate-500">Choose a course to map its outcomes.</div>
            @else
                <div class="card">
                    <div class="card-header">{{ $course->code }} — {{ $course->title }}</div>
                    <div class="px-5 py-3 text-sm text-slate-600 border-b border-slate-200">
                        Year {{ $course->year_level }} ·
                        {{ str($course->term_kind)->replace('_', ' ')->title() }} ·
                        {{ $course->units }} unit{{ $course->units === 1 ? '' : 's' }}
                        @unless ($course->is_major)
                            <span class="pill bg-slate-100 text-slate-600 ring-slate-300 ml-2">Not a major course</span>
                        @endunless
                    </div>

                    @error('clos') <p class="px-5 py-3 text-sm text-status-risk">{{ $message }}</p> @enderror

                    @if ($clos->isEmpty())
                        <p class="p-5 text-sm text-slate-500">
                            No outcomes recorded yet. Nothing in this course can produce attainment data until at least one is mapped.
                        </p>
                    @else
                        <table class="data-table">
                            <thead><tr><th class="w-20">Code</th><th>Outcome</th><th class="w-56">Mapped PLOs</th><th></th></tr></thead>
                            <tbody>
                                @foreach ($clos as $clo)
                                    <tr>
                                        <td class="font-medium">{{ $clo->code }}</td>
                                        <td>{{ $clo->statement }}</td>
                                        <td class="text-xs">
                                            @foreach ($clo->plos as $plo)
                                                <span class="pill bg-white ring-slate-300 mr-1 mb-1">
                                                    PLO {{ $plo->number }}
                                                    <span class="text-slate-400 ml-1">L{{ $plo->pivot->target_level }}</span>
                                                </span>
                                            @endforeach
                                        </td>
                                        <td class="text-right whitespace-nowrap">
                                            <button wire:click="editClo({{ $clo->id }})" class="text-xs text-navy-700 underline underline-offset-2">Edit</button>
                                            <button wire:click="deleteClo({{ $clo->id }})" class="text-xs text-status-risk underline underline-offset-2 ml-2">Delete</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

                <div class="card">
                    <div class="card-header">{{ $editingCloId ? 'Edit outcome' : 'Add outcome' }}</div>

                    <div class="p-5 space-y-4">
                        <div class="grid gap-4 sm:grid-cols-[8rem_1fr]">
                            <div>
                                <label class="field-label">Code</label>
                                <input type="text" wire:model="cloCode" class="input">
                                @error('cloCode') <p class="text-sm text-status-risk mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="field-label">Outcome statement</label>
                                <textarea wire:model="cloStatement" rows="2" class="textarea"
                                          placeholder="e.g. Design and simulate a combinational circuit meeting a given specification"></textarea>
                                <p class="field-help">Start with a verb a student can be assessed against: design, analyse, implement, evaluate.</p>
                                @error('cloStatement') <p class="text-sm text-status-risk mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="field-label">Maps to</label>
                            @error('selectedPlos') <p class="text-sm text-status-risk mb-2">{{ $message }}</p> @enderror

                            <div class="grid gap-2 sm:grid-cols-2">
                                @foreach ($plos as $plo)
                                    <div class="border border-slate-200 px-3 py-2 {{ ($selectedPlos[$plo->id] ?? false) ? 'bg-cream-50 border-navy-600' : 'bg-white' }}">
                                        <label class="flex items-start gap-2 text-sm cursor-pointer">
                                            <input type="checkbox" wire:model.live="selectedPlos.{{ $plo->id }}"
                                                   class="mt-0.5 border-slate-300 text-navy-800 focus:ring-navy-600">
                                            <span>
                                                <span class="font-medium text-navy-800">PLO {{ $plo->number }}</span>
                                                <span class="text-slate-600">— {{ $plo->title }}</span>
                                            </span>
                                        </label>

                                        @if ($selectedPlos[$plo->id] ?? false)
                                            <div class="mt-2 pl-6">
                                                <label class="text-xs text-slate-500">Target level for this course</label>
                                                <select wire:model="targetLevels.{{ $plo->id }}" class="select mt-1">
                                                    @foreach (\App\Support\Enums\AttainmentLevel::cases() as $level)
                                                        <option value="{{ $level->value }}">
                                                            {{ $level->value }} — {{ $level->matrixLabel() }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="px-5 py-4 border-t border-slate-200 flex gap-3">
                        <button wire:click="saveClo" class="btn-primary">{{ $editingCloId ? 'Update outcome' : 'Add outcome' }}</button>
                        @if ($editingCloId)
                            <button wire:click="resetCloForm" class="btn-quiet">Cancel</button>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
