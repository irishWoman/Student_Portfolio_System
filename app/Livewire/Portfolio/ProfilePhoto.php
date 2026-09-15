<?php

namespace App\Livewire\Portfolio;

use App\Models\Portfolio;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * SECTION 1 — 2x2 ID PHOTO
 * -----------------------------------------------------------------------------
 * One photo per student, not per portfolio year: it identifies the person, not
 * this year's work. Stored on the `public` disk (config/filesystems.php already
 * reserves it for "student photos, institution logo") so it can be shown inline
 * here and dropped straight into the exported cover page.
 */
class ProfilePhoto extends Component
{
    use WithFileUploads;

    public Portfolio $portfolio;

    public bool $canEdit = true;

    public $photo;

    protected function rules(): array
    {
        return [
            'photo' => ['required', 'image', 'max:2048', 'dimensions:min_width=150,min_height=150'],
        ];
    }

    public function save(): void
    {
        if (! $this->canEdit) {
            return;
        }

        $this->validate();

        $student = $this->portfolio->student;

        if ($student->photo_path) {
            Storage::disk('public')->delete($student->photo_path);
        }

        $student->update(['photo_path' => $this->photo->store('photos', 'public')]);

        $this->reset('photo');
        $this->dispatch('section-saved', section: 1);
    }

    public function render()
    {
        return view('livewire.portfolio.profile-photo', [
            'student' => $this->portfolio->student,
        ]);
    }
}
