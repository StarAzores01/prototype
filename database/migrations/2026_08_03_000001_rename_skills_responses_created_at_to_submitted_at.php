<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The original skills_responses table (pathrive_db.sql) has `submitted_at`,
 * not `created_at` — the initial migration used Blueprint::timestamps()
 * instead, which drifted from the actual schema ec/skills.php reads
 * (sr.submitted_at). Renaming rather than editing the already-run create
 * migration, per CONTINUE.md's schema-patch convention.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE skills_responses RENAME COLUMN created_at TO submitted_at');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE skills_responses RENAME COLUMN submitted_at TO created_at');
    }
};
