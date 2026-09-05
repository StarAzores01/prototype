<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Programs sit above Trainings ("Activities" in the UI — the trainings
 * table/Training model keep their names; see program_id on trainings).
 * A program is locked (is_locked=true) immediately on creation; only an EC
 * unlocking it can change budget/timeline, which is then audited in
 * program_amendments.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('area', 120);
            $table->date('timeline_start');
            $table->date('timeline_end');
            $table->decimal('budget_allocated', 12, 2);
            $table->enum('status', ['Proposed', 'Approved', 'Ongoing', 'Completed'])->default('Proposed');
            $table->boolean('is_locked')->default(true);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programs');
    }
};
