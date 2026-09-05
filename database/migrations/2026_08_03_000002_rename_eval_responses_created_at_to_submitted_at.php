<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Same drift as skills_responses (see 2026_08_03_000001_...): the original
 * eval_responses schema (pathrive_db.sql) has `submitted_at`, not
 * `created_at` — the initial migration used Blueprint::timestamps() instead.
 * ec/evaluations.php's response views sort/display by submitted_at.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE eval_responses RENAME COLUMN created_at TO submitted_at');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE eval_responses RENAME COLUMN submitted_at TO created_at');
    }
};
