<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PORTFOLIO CORE
 * -----------------------------------------------------------------------------
 * portfolios      - one per student per academic year (the yearly cycle)
 * section_entries - one row per section per portfolio; `payload` holds the
 *                   answers of config-driven sections as JSON, while
 *                   table-based sections keep their rows in their own tables
 *                   and use this row only for status and review notes.
 *
 * Storing flat answers as JSON is deliberate: the specification's question set
 * changes between accreditation cycles, and a JSON payload lets the department
 * add a question without a migration. Anything that has to be *queried*
 * (scores, levels, PLO links) gets a real column in a real table instead.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('year_level');        // year level during that AY
            $table->enum('status', ['draft', 'submitted', 'returned', 'validated'])->default('draft');
            $table->unsignedTinyInteger('completion_percent')->default(0);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_late')->default(false);
            $table->text('overall_remarks')->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'academic_year_id']);
        });

        Schema::create('section_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('section_number');    // 1..12, matches config/portfolio.php
            $table->string('section_key');                    // student_profile, ...
            $table->json('payload')->nullable();              // answers for field-driven sections
            $table->enum('status', ['draft', 'submitted', 'returned', 'validated'])->default('draft');
            $table->unsignedTinyInteger('completion_percent')->default(0);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reviewer_notes')->nullable();
            $table->boolean('is_late')->default(false);
            $table->timestamps();
            $table->unique(['portfolio_id', 'section_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('section_entries');
        Schema::dropIfExists('portfolios');
    }
};
