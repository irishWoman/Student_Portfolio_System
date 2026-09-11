<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DESIGN PROJECTS AND REFLECTIONS
 * -----------------------------------------------------------------------------
 * projects        - a major design project (Section 6) or capstone
 * design_elements - the 20 design-cycle entries for that project
 * reflections     - the 10 reflection answers (Section 12), attached to any
 *                   project, OJT record or course evidence via a morph
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_id')->constrained()->cascadeOnDelete();
            $table->foreignId('curriculum_course_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->enum('kind', ['course_project', 'design_project', 'research', 'capstone'])->default('design_project');
            $table->string('role')->nullable();               // Team lead, member
            $table->unsignedTinyInteger('team_size')->nullable();
            $table->date('started_on')->nullable();
            $table->date('completed_on')->nullable();
            $table->text('summary')->nullable();
            $table->timestamps();
        });

        Schema::create('design_elements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('element_key');                    // config('portfolio.design_elements') key
            $table->unsignedTinyInteger('position');           // 1..20, preserves the cycle order
            $table->text('content')->nullable();
            $table->timestamps();
            $table->unique(['project_id', 'element_key']);
        });

        Schema::create('reflections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_id')->constrained()->cascadeOnDelete();
            $table->nullableMorphs('reflectable');            // Project, OjtRecord, CourseEvidence...
            $table->string('subject');                         // what is being reflected on
            $table->json('answers');                           // keyed by config('portfolio.reflection_prompts')
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reflections');
        Schema::dropIfExists('design_elements');
        Schema::dropIfExists('projects');
    }
};
