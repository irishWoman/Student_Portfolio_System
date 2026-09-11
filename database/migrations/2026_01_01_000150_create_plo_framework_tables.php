<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PLO FRAMEWORK
 * -----------------------------------------------------------------------------
 * plos                    - the 14 Computer Engineering PLOs
 * plo_rubric_descriptors  - measurable descriptor per PLO per level (1-4)
 * competency_categories   - Programming, Digital Systems, Embedded, ...
 * competencies            - the individual skills inside each category
 * competency_plo          - which PLOs a competency contributes to
 *
 * Runs before the academic structure migration because clo_plo references plos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plos', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('number')->unique(); // 1..14
            $table->string('title');                         // Engineering Knowledge
            $table->text('statement');
            $table->timestamps();
        });

        Schema::create('plo_rubric_descriptors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plo_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('level');             // 1..4
            $table->text('descriptor');
            $table->timestamps();
            $table->unique(['plo_id', 'level']);
        });

        Schema::create('competency_categories', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();                  // programming
            $table->string('name');                           // Programming
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('competencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competency_category_id')->constrained()->cascadeOnDelete();
            $table->string('name');                           // C / C++
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('competency_plo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competency_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plo_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['competency_id', 'plo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competency_plo');
        Schema::dropIfExists('competencies');
        Schema::dropIfExists('competency_categories');
        Schema::dropIfExists('plo_rubric_descriptors');
        Schema::dropIfExists('plos');
    }
};
