<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * EVIDENCE VAULT
 * -----------------------------------------------------------------------------
 * Files are polymorphic: the same table serves course evidence, design
 * projects, OJT, capstone and professional development. `evidence_plo` records
 * which PLOs a file is offered as proof of, and the quality rating the
 * evaluator gives it.
 *
 * Files live on the private `evidence` disk and are streamed through a
 * controller, never linked directly.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evidence_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_id')->constrained()->cascadeOnDelete();
            $table->nullableMorphs('attachable');            // CourseEvidence, Project, OjtRecord...
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('original_name');
            $table->string('stored_path');
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('size_bytes');
            $table->string('external_url')->nullable()->comment('For artifacts that live elsewhere, e.g. a repository');
            $table->unsignedTinyInteger('quality_rating')->nullable()->comment('EvidenceQuality 1-4, set by evaluator');
            $table->text('quality_remarks')->nullable();
            $table->foreignId('rated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rated_at')->nullable();
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();
            $table->index(['portfolio_id', 'quality_rating']);
        });

        Schema::create('evidence_plo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evidence_file_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plo_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['evidence_file_id', 'plo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidence_plo');
        Schema::dropIfExists('evidence_files');
    }
};
