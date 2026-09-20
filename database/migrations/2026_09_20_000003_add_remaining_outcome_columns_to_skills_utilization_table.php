<?php

use App\Models\SkillProgressEntry;
use App\Models\SkillsUtilization;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * skills_utilization originally only had columns for 3 of the 6
 * SkillProgressEntry::OUTCOME_TYPES (Personal Development, Income
 * Generating, Employment / Work) — Community Service, Training
 * Application, and Other had nowhere to be counted, so a beneficiary who
 * logged one of those never showed up anywhere in the Skills Utilization
 * overview. This adds the 3 missing columns and immediately backfills
 * every training's row (see SkillsUtilization::recomputeForTraining()),
 * so previously-invisible entries appear right away rather than waiting
 * for the next progress-entry edit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('skills_utilization', function (Blueprint $table) {
            $table->decimal('community_service_pct', 5, 2)->default(0)->after('nc2_cert_pct');
            $table->decimal('training_application_pct', 5, 2)->default(0)->after('community_service_pct');
            $table->decimal('other_pct', 5, 2)->default(0)->after('training_application_pct');
        });

        SkillProgressEntry::whereNotNull('training_id')
            ->distinct()
            ->pluck('training_id')
            ->each(fn (int $trainingId) => SkillsUtilization::recomputeForTraining($trainingId));
    }

    public function down(): void
    {
        Schema::table('skills_utilization', function (Blueprint $table) {
            $table->dropColumn(['community_service_pct', 'training_application_pct', 'other_pct']);
        });
    }
};
