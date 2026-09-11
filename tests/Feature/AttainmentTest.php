<?php

namespace Tests\Feature;

use App\Models\Plo;
use App\Services\PloAttainmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke test for the part of the system that must not be wrong: the attainment
 * computation. Extend this first if you build on the project.
 */
class AttainmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_portfolio_produces_attainment_snapshots(): void
    {
        $this->seed();

        $student = \App\Models\Student::where('student_number', '2101391')->firstOrFail();
        $portfolio = $student->portfolios()->latest('academic_year_id')->firstOrFail();

        $snapshots = app(PloAttainmentService::class)->recomputeForPortfolio($portfolio);

        $this->assertCount(Plo::count(), $snapshots);

        // PLO 13 has validated course evidence in the demo data, so it must score.
        $this->assertNotNull($snapshots[13]->direct_score);
    }

    public function test_self_assessment_alone_never_counts_as_direct_evidence(): void
    {
        $this->seed();

        $student = \App\Models\Student::where('student_number', '2101402')->firstOrFail();
        $portfolio = $student->portfolios()->latest('academic_year_id')->firstOrFail();

        $snapshots = app(PloAttainmentService::class)->recomputeForPortfolio($portfolio);

        // This student submitted nothing validated, so every PLO is under-assessed.
        $this->assertSame('under_assessed', $snapshots[1]->flag);
    }
}
