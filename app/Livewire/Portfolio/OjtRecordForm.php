<?php

namespace App\Livewire\Portfolio;

use App\Models\OjtRecord;
use App\Models\OjtSupervisorEvaluation;
use App\Models\Portfolio;
use App\Services\PortfolioCompletionService;
use Livewire\Component;

/**
 * SECTION 9 — INDUSTRY / OJT PORTFOLIO
 * -----------------------------------------------------------------------------
 * COME 1163, 240 hours. The student records the placement; the supervisor's
 * rating arrives through a one-time link the student generates here and emails
 * to their supervisor. The company never needs an account.
 */
class OjtRecordForm extends Component
{
    public Portfolio $portfolio;

    public bool $canEdit = true;

    public array $form = [
        'company_name' => '', 'company_address' => '', 'industry' => '',
        'supervisor_name' => '', 'supervisor_position' => '', 'supervisor_email' => '',
        'started_on' => null, 'ended_on' => null, 'completed_hours' => 0,
        'objectives' => '', 'responsibilities' => '', 'work_outputs' => '', 'student_reflection' => '',
    ];

    public ?string $supervisorLink = null;

    public function mount(Portfolio $portfolio, bool $canEdit = true): void
    {
        $this->portfolio = $portfolio;
        $this->canEdit = $canEdit;

        if ($record = $portfolio->ojtRecord) {
            foreach (array_keys($this->form) as $key) {
                $value = $record->{$key};
                $this->form[$key] = $value instanceof \Illuminate\Support\Carbon ? $value->toDateString() : $value;
            }

            if ($evaluation = $record->supervisorEvaluation) {
                $this->supervisorLink = $evaluation->isOpen()
                    ? route('ojt.supervisor', $evaluation->access_token)
                    : null;
            }
        }
    }

    public function save(): void
    {
        if (! $this->canEdit) {
            return;
        }

        $data = $this->validate([
            'form.company_name' => ['required', 'string', 'max:200'],
            'form.company_address' => ['nullable', 'string', 'max:250'],
            'form.industry' => ['nullable', 'string', 'max:120'],
            'form.supervisor_name' => ['required', 'string', 'max:180'],
            'form.supervisor_position' => ['nullable', 'string', 'max:150'],
            'form.supervisor_email' => ['required', 'email', 'max:180'],
            'form.started_on' => ['nullable', 'date'],
            'form.ended_on' => ['nullable', 'date', 'after_or_equal:form.started_on'],
            'form.completed_hours' => ['required', 'integer', 'between:0,2000'],
            'form.objectives' => ['nullable', 'string', 'max:2000'],
            'form.responsibilities' => ['required', 'string', 'max:2000'],
            'form.work_outputs' => ['nullable', 'string', 'max:2000'],
            'form.student_reflection' => ['nullable', 'string', 'max:3000'],
        ])['form'];

        OjtRecord::updateOrCreate(
            ['portfolio_id' => $this->portfolio->id],
            $data + ['required_hours' => 240]
        );

        app(PortfolioCompletionService::class)->recomputeSection($this->portfolio->entryFor(9));

        $this->dispatch('section-saved', section: 9);
    }

    /** Generate (or regenerate) the supervisor's single-use evaluation link. */
    public function issueSupervisorLink(): void
    {
        $record = $this->portfolio->ojtRecord;

        if (! $record) {
            $this->addError('form.company_name', 'Save the placement details first.');

            return;
        }

        $evaluation = OjtSupervisorEvaluation::issueToken($record);
        $this->supervisorLink = route('ojt.supervisor', $evaluation->access_token);
    }

    public function render()
    {
        return view('livewire.portfolio.ojt-record', [
            'record' => $this->portfolio->ojtRecord?->fresh('supervisorEvaluation'),
        ]);
    }
}
