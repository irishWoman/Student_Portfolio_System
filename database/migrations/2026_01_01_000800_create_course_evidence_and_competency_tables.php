<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SECTION 4 and SECTION 5 RECORDS
 * -----------------------------------------------------------------------------
 * course_evidence_records    - one row per course output the student offers
 * technical_competency_records - the student's stage per competency per year
 * professional_development_records - Section 11 entries
 * research_records           - Section 7 artifacts that need their own row
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_evidence_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_id')->constrained()->cascadeOnDelete();
            $table->foreignId('curriculum_course_id')->constrained();
            $table->foreignId('course_learning_outcome_id')->nullable()->constrained()->nullOnDelete();
            $table->string('clo_statement')->nullable()->comment('Free text when the CLO is not yet in the catalogue');
            $table->string('assessment_activity');            // Programming project, lab practical...
            $table->string('output_title');
            $table->decimal('score', 6, 2)->nullable();
            $table->decimal('score_max', 6, 2)->nullable()->default(100);
            $table->unsignedTinyInteger('claimed_level')->nullable(); // student's own claim
            $table->unsignedTinyInteger('validated_level')->nullable(); // evaluator's decision
            $table->foreignId('evaluator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('evaluated_on')->nullable();
            $table->text('reflection_note')->nullable();
            $table->text('improvement_action')->nullable();
            $table->timestamps();
            $table->index(['portfolio_id', 'curriculum_course_id']);
        });

        // Which PLOs a single course-evidence row is offered against.
        Schema::create('course_evidence_plo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_evidence_record_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plo_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['course_evidence_record_id', 'plo_id'], 'course_evidence_plo_unique');
        });

        Schema::create('technical_competency_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_id')->constrained()->cascadeOnDelete();
            $table->foreignId('competency_id')->constrained()->cascadeOnDelete();
            $table->string('claimed_stage');                  // CompetencyStage value
            $table->string('validated_stage')->nullable();
            $table->text('evidence_note')->nullable();
            $table->foreignId('evaluator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['portfolio_id', 'competency_id'], 'tech_competency_unique');
        });

        Schema::create('professional_development_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_id')->constrained()->cascadeOnDelete();
            $table->enum('kind', [
                'seminar', 'workshop', 'conference', 'certification', 'competition',
                'hackathon', 'webinar', 'industry_training', 'leadership', 'publication', 'other',
            ]);
            $table->string('title');
            $table->string('organizer')->nullable();
            $table->date('held_on')->nullable();
            $table->unsignedSmallInteger('hours')->nullable();
            // A certificate alone earns nothing. This field is required and is
            // what the evaluator rates; the certificate is only the receipt.
            $table->text('competency_demonstrated');
            $table->unsignedTinyInteger('claimed_level')->nullable();
            $table->unsignedTinyInteger('validated_level')->nullable();
            $table->foreignId('evaluator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('research_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('venue')->nullable();
            $table->date('completed_on')->nullable();
            $table->text('abstract')->nullable();
            $table->text('methodology')->nullable();
            $table->text('findings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('research_records');
        Schema::dropIfExists('professional_development_records');
        Schema::dropIfExists('technical_competency_records');
        Schema::dropIfExists('course_evidence_plo');
        Schema::dropIfExists('course_evidence_records');
    }
};
