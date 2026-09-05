<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pivot: which trainers are assigned to a training ("Activity" in the UI —
 * the trainings table/Training model name stay unchanged). Same lead + up to
 * 3 members pattern as program_team_members, and the same caveat: the
 * lead/member-count rule and the "user_id must have role=trainer" check are
 * both application-level (controller), not DB constraints.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_id')->constrained('trainings')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('member_role', ['lead', 'member']);
            $table->timestamps();

            $table->unique(['training_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_team_members');
    }
};
