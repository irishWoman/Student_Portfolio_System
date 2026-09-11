{{--
    Evidence panel. Shared by every section so a student can attach a file
    wherever they happen to be working, rather than navigating to a separate
    uploads screen.
--}}
<div class="card mt-6">
    <div class="card-header">Evidence files</div>

    @if ($portfolio->evidenceFiles->isEmpty())
        <p class="p-4 text-sm text-slate-500">
            No files yet. Attach the output itself — source code, a report, a photo of the prototype — not just a certificate.
        </p>
    @else
        <table class="data-table">
            <thead><tr><th>Title</th><th>File</th><th>PLOs</th><th>Quality</th><th></th></tr></thead>
            <tbody>
                @foreach ($portfolio->evidenceFiles as $file)
                    <tr>
                        <td>
                            <p class="font-medium">{{ $file->title }}</p>
                            @if ($file->description)<p class="text-xs text-slate-500">{{ $file->description }}</p>@endif
                        </td>
                        <td class="text-xs">
                            <a href="{{ route('evidence.download', $file) }}" class="text-navy-700 underline underline-offset-2">{{ $file->original_name }}</a>
                            <span class="text-slate-400 block">{{ $file->humanSize() }}</span>
                        </td>
                        <td class="text-xs">{{ $file->plos->map(fn ($p) => $p->number)->implode(', ') ?: '—' }}</td>
                        <td>
                            @if ($file->quality())
                                <span class="pill {{ $file->countsTowardAttainment() ? 'bg-emerald-50 text-status-ontrack ring-emerald-200' : 'bg-slate-100 text-slate-600 ring-slate-300' }}">
                                    {{ $file->quality()->label() }}
                                </span>
                            @else
                                <span class="text-xs text-slate-400">Not yet rated</span>
                            @endif
                        </td>
                        <td class="text-right">
                            @if ($canEdit && ! $file->quality_rating)
                                <form method="POST" action="{{ route('evidence.destroy', $file) }}">
                                    @csrf @method('DELETE')
                                    <button class="text-xs text-status-risk underline underline-offset-2">Remove</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if ($canEdit)
        <form method="POST" action="{{ route('evidence.store', $portfolio) }}" enctype="multipart/form-data"
              class="p-4 border-t border-slate-200 grid gap-3 sm:grid-cols-2">
            @csrf
            <div>
                <label class="field-label">What is this?</label>
                <input name="title" class="input" required placeholder="e.g. 4-bit adder schematic and simulation log">
            </div>
            <div>
                <label class="field-label">File</label>
                <input type="file" name="file" class="input" required>
                <p class="field-help">Up to 20 MB. Documents, images, archives or source files.</p>
            </div>
            <div class="sm:col-span-2">
                <label class="field-label">Which PLOs does it demonstrate?</label>
                <div class="flex flex-wrap gap-2">
                    @foreach (\App\Models\Plo::orderBy('number')->get() as $plo)
                        <label class="pill bg-white ring-slate-300 cursor-pointer">
                            <input type="checkbox" name="plos[]" value="{{ $plo->id }}" class="mr-1 border-slate-300 text-navy-800 focus:ring-navy-600">
                            {{ $plo->code() }}
                        </label>
                    @endforeach
                </div>
            </div>
            <div class="sm:col-span-2">
                <button class="btn-primary">Upload evidence</button>
            </div>
        </form>
    @endif
</div>
