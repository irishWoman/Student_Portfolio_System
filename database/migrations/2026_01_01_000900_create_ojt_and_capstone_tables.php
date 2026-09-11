<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SECTION 9 and SECTION 10
 * -----------------------------------------------------------------------------
 * ojt_records                - COME 1163, 240 hours
 * ojt_supervisor_evaluations - filled by the company supervisor through a
 *                              signed link; no account is created for them
 * capstone_records           - CpE Practice and Design 1 & 2
 * capstone_criteria_scores   - per-criterion score mapped to a PLO
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ojt_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_id')->constrained()->cascadeOnDelete();
            $table->string('company_name');
            $table->string('company_address')->nullable();
            $table->string('industry')->nullable();
            $table->string('supervisor_name')->nullable();
            $table->string('supervisor_position')->nullable();
            $table->string('supervisor_email')->nullable();
            $table->date('started_on')->nullable();
            $table->date('ended_on')->nullable();
            $table->unsignedSmallInteger('required_hours')->default(240);
            $table->unsignedSmallInteger('completed_hours')->default(0);
            $table->text('objectives')->nullable();
            $table->text('responsibilities')->nullable();
            $table->text('work_outputs')->nullable();
            $table->text('student_reflection')->nullable();
            $table->timestamps();
        });

        Schema::create('ojt_supervisor_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ojt_record_id')->constrained()->cascadeOnDelete();
            // Signed-link token: lets a supervisor submit once, without a login.
            $table->string('access_token', 64)->unique();
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedTinyInteger('technical_competency')->nullable();
            $table->unsignedTinyInteger('professional_behavior')->nullable();
            $table->unsignedTinyInteger('communication')->nullable();
            $table->unsignedTinyInteger('teamwork')->nullable();
            $table->unsignedTinyInteger('safety')->nullable();
            $table->unsignedTinyInteger('engineering_analysis')->nullable();
            $table->unsignedTinyInteger('documentation')->nullable();
            $table->unsignedTinyInteger('professional_growth')->nullable();
            $table->text('strengths')->nullable();
            $table->text('areas_for_improvement')->nullable();
            $table->string('signed_by')->nullable();
            $table->timestamps();
        });

        Schema::create('capstone_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('problem_definition')->nullable();
            $table->text('literature_review')->nullable();
            $table->text('requirements')->nullable();
            $table->text('system_architecture')->nullable();
            $table->text('design')->nullable();
            $table->text('implementation')->nullable();
            $table->text('testing')->nullable();
            $table->text('validation')->nullable();
            $table->text('cost_analysis')->nullable();
            $table->text('risk_assessment')->nullable();
            $table->text('ethics')->nullable();
            $table->text('sustainability')->nullable();
            $table->text('documentation_note')->nullable();
            $table->date('proposal_defended_on')->nullable();
            $table->date('final_defended_on')->nullable();
            $table->string('adviser_name')->nullable();
            $table->timestamps();
        });

        Schema::create('capstone_criteria_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('capstone_record_id')->constrained()->cascadeOnDelete();
            $table->string('criterion');
            $table->foreignId('plo_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('rating')->nullable();   // 1-4
            $table->text('comment')->nullable();
            $table->foreignId('evaluator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capstone_criteria_scores');
        Schema::dropIfExists('capstone_records');
        Schema::dropIfExists('ojt_supervisor_evaluations');
        Schema::dropIfExists('ojt_records');
    }
};
