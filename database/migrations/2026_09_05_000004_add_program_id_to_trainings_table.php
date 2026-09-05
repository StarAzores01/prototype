<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Programs sit above Trainings ("Activities" in the UI — trainings keeps its
 * table/model name unchanged, same pattern as the trainer/"Project Leader"
 * label split). Nullable because pre-existing trainings predate programs and
 * shouldn't be orphaned by this migration; whether to backfill them under a
 * program is a later decision.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trainings', function (Blueprint $table) {
            $table->foreignId('program_id')->nullable()->after('id')
                ->constrained('programs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('trainings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('program_id');
        });
    }
};
