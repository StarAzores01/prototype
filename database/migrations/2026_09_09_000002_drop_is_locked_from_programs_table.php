<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Retires the "Request Amendment Lock" feature. is_locked was always true in
 * practice — nothing in the app ever set it false, so it never actually
 * gated anything. The real protections now live entirely in
 * Program::booted() (budget_allocated/timeline_start/timeline_end are all
 * permanently immutable after creation) and in the new "Extend Timeline"
 * flow (see 2026_09_09_000001) — no boolean flag needed for either.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->dropColumn('is_locked');
        });
    }

    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->boolean('is_locked')->default(true)->after('status');
        });
    }
};
