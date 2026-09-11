<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A student record is the academic identity attached to a user account.
 * Section 1 profile answers live in section_entries like every other section;
 * only the registrar-style facts sit here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->constrained();
            $table->string('student_number')->unique();       // 2101391
            $table->string('last_name');
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->unsignedTinyInteger('year_level')->default(1);
            $table->string('section')->nullable();
            $table->string('photo_path')->nullable();
            $table->year('admitted_year')->nullable();
            $table->foreignId('adviser_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('standing', ['active', 'loa', 'graduated', 'inactive'])->default('active');
            $table->timestamps();
            $table->index(['program_id', 'year_level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
