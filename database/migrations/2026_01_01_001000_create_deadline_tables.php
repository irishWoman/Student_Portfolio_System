<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DEADLINES
 * -----------------------------------------------------------------------------
 * deadline_sets - a named set of dates for one academic year and year level,
 *                 e.g. "AY 2026-2027, Year 4". Cloning a set is how the chair
 *                 rolls the schedule forward to the next year.
 * deadlines     - one row per section per scope (semester checkpoint or
 *                 year-end). A null section_number means "the whole portfolio".
 * deadline_extensions - per-student approved extensions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deadline_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('year_level');
            $table->string('label');
            $table->boolean('is_published')->default(false);
            $table->timestamps();
            $table->unique(['academic_year_id', 'program_id', 'year_level'], 'deadline_set_unique');
        });

        Schema::create('deadlines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deadline_set_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('section_number')->nullable()->comment('Null = whole portfolio');
            $table->string('scope');                          // DeadlineScope value
            $table->string('title');
            $table->dateTime('due_at');
            $table->unsignedTinyInteger('grace_days')->default(7);
            $table->boolean('locks_editing')->default(false)->comment('True = hard lock at due date');
            $table->text('instructions')->nullable();
            $table->timestamps();
            $table->index(['deadline_set_id', 'due_at']);
        });

        Schema::create('deadline_extensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deadline_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->dateTime('extended_to');
            $table->text('reason')->nullable();
            $table->enum('status', ['requested', 'approved', 'denied'])->default('requested');
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->unique(['deadline_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deadline_extensions');
        Schema::dropIfExists('deadlines');
        Schema::dropIfExists('deadline_sets');
    }
};
