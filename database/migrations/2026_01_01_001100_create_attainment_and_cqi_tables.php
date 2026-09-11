<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ATTAINMENT SNAPSHOTS AND CQI
 * -----------------------------------------------------------------------------
 * plo_attainment_snapshots - the computed figure per student per PLO per year.
 *   Snapshots are recomputed on demand and cached here so dashboards stay fast
 *   and, more importantly, so the department keeps a dated record of what the
 *   figure was at the time of an accreditation visit.
 * cqi_actions - the Section XI improvement loop.
 * audit_logs  - who changed what; accreditors ask.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plo_attainment_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plo_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('year_level');
            $table->decimal('direct_score', 4, 2)->nullable();    // weighted mean, 1-4
            $table->decimal('indirect_score', 4, 2)->nullable();  // reported beside, never merged
            $table->unsignedSmallInteger('evidence_count')->default(0);
            $table->unsignedSmallInteger('valid_evidence_count')->default(0);
            $table->string('flag')->nullable();                   // on_track | watch | under_assessed | at_risk
            $table->timestamp('computed_at')->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'academic_year_id', 'plo_id'], 'attainment_snapshot_unique');
        });

        Schema::create('cqi_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plo_id')->constrained()->cascadeOnDelete();
            $table->text('gap_description');
            $table->text('evidence_summary')->nullable();
            $table->text('possible_cause')->nullable();
            $table->text('intervention')->nullable();
            $table->string('responsible_unit')->nullable();
            $table->date('target_date')->nullable();
            $table->date('reassessment_date')->nullable();
            $table->enum('status', ['open', 'in_progress', 'closed'])->default('open');
            $table->decimal('baseline_score', 4, 2)->nullable();
            $table->decimal('reassessed_score', 4, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->nullableMorphs('subject');
            $table->json('context')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('cqi_actions');
        Schema::dropIfExists('plo_attainment_snapshots');
    }
};
