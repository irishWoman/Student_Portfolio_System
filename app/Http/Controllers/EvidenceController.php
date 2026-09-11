<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\EvidenceFile;
use App\Models\Portfolio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Evidence upload, download and removal.
 *
 * Files never sit under public/. Every download goes through this controller so
 * the portfolio policy runs first; that is the only thing standing between a
 * guessed URL and another student's capstone.
 */
class EvidenceController extends Controller
{
    /** Extensions the department accepts as evidence. */
    protected array $allowed = [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'png', 'jpg', 'jpeg', 'gif', 'zip', 'txt', 'md', 'csv', 'ino', 'c', 'cpp', 'py',
    ];

    public function store(Request $request, Portfolio $portfolio)
    {
        $this->authorize('update', $portfolio);

        $data = $request->validate([
            'file' => ['required', 'file', 'max:20480', 'mimes:'.implode(',', $this->allowed)],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:1000'],
            'external_url' => ['nullable', 'url', 'max:300'],
            'attachable_type' => ['nullable', 'string'],
            'attachable_id' => ['nullable', 'integer'],
            'plos' => ['array'],
            'plos.*' => ['integer', 'exists:plos,id'],
        ]);

        $file = $request->file('file');

        // Stored path is scoped by portfolio so cleanup and auditing are simple.
        $path = $file->store("portfolio-{$portfolio->id}", 'evidence');

        $evidence = $portfolio->evidenceFiles()->create([
            'attachable_type' => $data['attachable_type'] ?? null,
            'attachable_id' => $data['attachable_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'original_name' => $file->getClientOriginalName(),
            'stored_path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'size_bytes' => $file->getSize(),
            'external_url' => $data['external_url'] ?? null,
            'uploaded_by' => $request->user()->id,
        ]);

        $evidence->plos()->sync($data['plos'] ?? []);

        AuditLog::record('evidence.uploaded', $evidence, ['portfolio_id' => $portfolio->id]);

        return back()->with('status', 'Evidence uploaded.');
    }

    public function download(Request $request, EvidenceFile $evidence)
    {
        $this->authorize('view', $evidence->portfolio);

        abort_unless(Storage::disk('evidence')->exists($evidence->stored_path), 404, 'That file is no longer on the server.');

        AuditLog::record('evidence.downloaded', $evidence);

        return Storage::disk('evidence')->download($evidence->stored_path, $evidence->original_name);
    }

    public function destroy(Request $request, EvidenceFile $evidence)
    {
        $this->authorize('update', $evidence->portfolio);

        // A rated file is part of an assessment record, so it stays.
        abort_if($evidence->quality_rating !== null, 422, 'This file has already been rated and cannot be removed.');

        Storage::disk('evidence')->delete($evidence->stored_path);
        AuditLog::record('evidence.deleted', $evidence);
        $evidence->delete();

        return back()->with('status', 'Evidence removed.');
    }

    /** Evaluator applies the Evidence Quality Scale. */
    public function rate(Request $request, EvidenceFile $evidence)
    {
        $this->authorize('evaluate', $evidence->portfolio);

        $data = $request->validate([
            'quality_rating' => ['required', 'integer', 'between:1,4'],
            'quality_remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $evidence->update($data + [
            'rated_by' => $request->user()->id,
            'rated_at' => now(),
        ]);

        AuditLog::record('evidence.rated', $evidence, ['rating' => $data['quality_rating']]);

        return back()->with('status', 'Evidence quality recorded.');
    }
}
