<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ACADEMIC STRUCTURE
 * -----------------------------------------------------------------------------
 * programs           - BSCpE (curriculum version 2023), extendable to other programs
 * academic_years     - "2026-2027", with the date the portfolio closes
 * terms              - First/Second Semester and Summer inside an academic year
 * curriculum_courses - the official checklist, seeded from the USL BSCPE 2023 curriculum
 * course_learning_outcomes - CLOs per course; faculty maintain these
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();            // BSCPE
            $table->string('title');                     // Bachelor of Science in Computer Engineering
            $table->string('department')->nullable();
            $table->string('curriculum_version')->default('2023');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('label')->unique();           // 2026-2027
            $table->date('starts_on');
            $table->date('ends_on');
            // The hard close: portfolios for this AY must be submitted before it.
            $table->date('portfolio_due_on')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });

        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->enum('kind', ['first_semester', 'second_semester', 'summer']);
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamps();
            $table->unique(['academic_year_id', 'kind']);
        });

        Schema::create('curriculum_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->string('code');                      // COME 1134
            $table->string('title');                     // Embedded System
            $table->unsignedTinyInteger('units')->default(3);
            $table->unsignedTinyInteger('year_level');   // 1..4
            $table->enum('term_kind', ['first_semester', 'second_semester', 'summer']);
            // Courses flagged as major carry the portfolio's required evidence.
            $table->boolean('is_major')->default(false);
            $table->timestamps();
            $table->unique(['program_id', 'code']);
            $table->index(['year_level', 'term_kind']);
        });

        Schema::create('course_learning_outcomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curriculum_course_id')->constrained()->cascadeOnDelete();
            $table->string('code')->nullable();          // CLO1
            $table->text('statement');
            $table->timestamps();
        });

        // CLO -> PLO mapping, with the level the course is expected to reach.
        Schema::create('clo_plo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_learning_outcome_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plo_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('target_level')->default(2); // AttainmentLevel value
            $table->timestamps();
            $table->unique(['course_learning_outcome_id', 'plo_id'], 'clo_plo_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clo_plo');
        Schema::dropIfExists('course_learning_outcomes');
        Schema::dropIfExists('curriculum_courses');
        Schema::dropIfExists('terms');
        Schema::dropIfExists('academic_years');
        Schema::dropIfExists('programs');
    }
};
