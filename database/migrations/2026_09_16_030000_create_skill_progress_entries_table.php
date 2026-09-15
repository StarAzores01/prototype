<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Repeated skills-application journal for beneficiaries — replaces the
 * one-time Google-Forms-style skills survey (skills_forms/skills_responses,
 * left untouched below) for the beneficiary-facing workflow. A beneficiary
 * adds one row per activity/outcome, as many times as they like, instead of
 * answering a single locked survey once.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skill_progress_entries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('beneficiary_id')
                ->constrained('beneficiaries')
                ->cascadeOnDelete();

            // Optional link to the activity/training the skill came from —
            // not required, since a beneficiary may apply a skill outside
            // any specific tracked activity.
            $table->foreignId('training_id')
                ->nullable()
                ->constrained('trainings')
                ->nullOnDelete();

            $table->string('activity_name', 255);
            $table->text('description')->nullable();
            $table->date('activity_date');

            // 'Income Generating' | 'Employment / Work' | 'Community Service'
            // | 'Personal Development' | 'Training Application' | 'Other'
            $table->string('outcome_type', 60);

            $table->decimal('service_fee', 12, 2)->default(0);
            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->index('beneficiary_id');
            $table->index('activity_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skill_progress_entries');
    }
};
