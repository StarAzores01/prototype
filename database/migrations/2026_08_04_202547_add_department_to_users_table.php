<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Evaluators need a college/department value distinct from `position`,
     * which is a hard CHECK constraint restricted to academic ranks
     * (Professor/Assistant Professor/Instructor) meant for trainers.
     * EvaluatorController was writing department names into `position` and
     * crashing on every save — this gives evaluators their own
     * unconstrained column instead, and drops the NOT NULL on `position`
     * since evaluators have no academic rank to put there.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('department', 120)->nullable()->after('position');
        });

        DB::statement('ALTER TABLE users ALTER COLUMN position DROP NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE users ALTER COLUMN position SET NOT NULL');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('department');
        });
    }
};
