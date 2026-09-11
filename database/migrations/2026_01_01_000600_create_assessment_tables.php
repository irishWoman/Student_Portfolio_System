<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ASSESSMENT
 * -----------------------------------------------------------------------------
 * assessments        - one assessed artifact (a course output, a project, OJT...)
 * assessment_plo_scores - the per-PLO level awarded for that artifact. This is
 *                      the table the attainment maths reads.
 * faculty_evaluations / faculty_evaluation_scores - the Section X form.
 *
 * Student self-assessment and faculty validation both land in
 * assessment_plo_scores, distinguished by `is_self_assessment`. Only validated,
 * non-self rows count as direct evidence.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_id')->constrained()->cascadeOnDelete();
            $table->nullableMorphs('assessable');            // what was assessed
            $table->string('type');                           // AssessmentType value
            $table->string('title');
            $table->foreignId('curriculum_course_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('course_learning_outcome_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('raw_score', 6, 2)->nullable();
            $table->decimal('max_score', 6, 2)->nullable();
            $table->foreignId('evaluator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('assessed_on')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->index(['portfolio_id', 'type']);
        });

        Schema::create('assessment_plo_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plo_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('level');             // AttainmentLevel 1-4
            $table->boolean('is_self_assessment')->default(false);
            $table->boolean('is_validated')->default(false);
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->text('validator_note')->nullable();
            $table->timestamps();
            $table->unique(['assessment_id', 'plo_id', 'is_self_assessment'], 'assessment_plo_unique');
        });

        Schema::create('faculty_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_id')->constrained()->cascadeOnDelete();
            $table->foreignId('evaluator_id')->constrained('users');
            $table->string('artifact_assessed');
            $table->foreignId('curriculum_course_id')->nullable()->constrained()->nullOnDelete();
            $table->date('evaluated_on');
            $table->text('overall_comment')->nullable();
            $table->decimal('overall_rating', 4, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('faculty_evaluation_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculty_evaluation_id')->constrained()->cascadeOnDelete();
            $table->string('criterion');                      // key from config('portfolio.faculty_criteria')
            $table->unsignedTinyInteger('rating');             // 1-4
            $table->text('comment')->nullable();               // required below the threshold
            $table->timestamps();
            $table->unique(['faculty_evaluation_id', 'criterion'], 'faculty_eval_criterion_unique');
        });

        // Indirect assessment is stored apart so it can never be averaged into
        // the direct attainment figure by accident.
        Schema::create('indirect_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('plo_id')->constrained()->cascadeOnDelete();
            $table->string('source');                          // AssessmentType indirect value
            $table->unsignedTinyInteger('level');
            $table->text('comment')->nullable();
            $table->date('collected_on')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indirect_assessments');
        Schema::dropIfExists('faculty_evaluation_scores');
        Schema::dropIfExists('faculty_evaluations');
        Schema::dropIfExists('assessment_plo_scores');
        Schema::dropIfExists('assessments');
    }
};
