<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pivot: which trainers are assigned to a program, and in what capacity.
 *
 * The "exactly 1 lead + at most 3 members per program" rule is deliberately
 * NOT a DB constraint (Postgres can't express "exactly 1 row per group"
 * cleanly without a partial unique index doing double duty as a business
 * rule) — it's enforced in the controller layer once that's built. Likewise
 * "user_id must have role=trainer" is an application-level check, not an FK
 * to a filtered table. The unique index below only prevents the same user
 * being added twice to the same program — a plain data-integrity guard, not
 * the lead/member-count rule.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('programs')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('member_role', ['lead', 'member']);
            $table->timestamps();

            $table->unique(['program_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_team_members');
    }
};
