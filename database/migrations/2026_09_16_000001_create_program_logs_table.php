<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only activity log for a Program.
 *
 * Records document actions (upload, link, archive, restore, delete) taken
 * inside a specific Program context.  Each row is a snapshot — actor name
 * and role are stored directly so the log remains readable even after a
 * user is deactivated or deleted.
 *
 * Column notes
 * ────────────
 * program_id     The owning Program.  cascadeOnDelete so orphan rows do
 *                not accumulate when a program is hard-deleted.
 *
 * training_id    The Activity the document belongs to, when applicable.
 *                Nullable; nullOnDelete so the log entry survives if the
 *                activity is later deleted (we still have program_id and
 *                the location_name snapshot).
 *
 * user_id        Nullable FK to users. nullOnDelete so historical log
 *                entries survive deactivated/deleted staff accounts.
 *
 * actor_name     Snapshot of user full name at the time of the action.
 *                Must be stored — user_id alone is not enough because the
 *                user row (and its name) can change or be deleted.
 *
 * actor_role     Human-readable role label snapshot: "Extension Coordinator",
 *                "Project Leader", or "Evaluator".  Not the raw DB value.
 *
 * action         What happened: 'uploaded_file', 'added_link', 'archived',
 *                'restored', 'deleted'.  Plain VARCHAR — no ENUM so adding
 *                new actions never requires a schema migration.
 *
 * entity_type    What kind of thing was acted on: 'Document'.  Reserved for
 *                future expansion (e.g. 'Training', 'Program').
 *
 * entity_id      The PK of the acted-on row at log time.  No FK constraint
 *                — the row may be deleted (especially for 'deleted' actions).
 *
 * item_name      Snapshot of the document's display name (original_name or
 *                link title) at log time.
 *
 * item_type      File extension (pdf, xlsx, …) or link type
 *                (gdrive, youtube, external) — whichever applies.
 *
 * location_name  Human-readable scope: the Activity title, or "Program
 *                Repository" for program-level documents.
 *
 * details        Optional JSONB for supplementary metadata. Never contains
 *                passwords, tokens, filesystem paths, or secrets.
 *
 * created_at     Set by the DB default (useCurrent).  No updated_at — rows
 *                are immutable once written.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('program_id')
                ->constrained('programs')
                ->cascadeOnDelete();

            $table->foreignId('training_id')
                ->nullable()
                ->constrained('trainings')
                ->nullOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('actor_name', 255);
            $table->string('actor_role', 60);

            // Action performed (see ProgramLogService::ACTION_* constants)
            $table->string('action', 60);

            // What kind of entity was acted on
            $table->string('entity_type', 60)->default('Document');

            // PK of the acted-on row — no FK, subject may be deleted
            $table->unsignedBigInteger('entity_id')->nullable();

            // Snapshot of the document name / link title
            $table->string('item_name', 255)->nullable();

            // File extension or link type
            $table->string('item_type', 60)->nullable();

            // Human-readable scope (activity title or "Program Repository")
            $table->string('location_name', 255)->nullable();

            // Optional supplementary metadata — safe fields only
            $table->jsonb('details')->nullable();

            $table->timestamp('created_at')->useCurrent();

            // Indexes for the two most common query patterns
            $table->index('program_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_logs');
    }
};