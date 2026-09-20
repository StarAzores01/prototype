<?php

use App\Models\SkillProgressEntry;
use App\Models\SkillsUtilization;
use Illuminate\Database\Migrations\Migration;

/**
 * One-off data backfill — not a schema change. Recomputes skills_utilization
 * for every training that already has beneficiary progress-journal entries
 * on file, so the EC/Trainer dashboards' "Skills Utilization" card reflects
 * real (already-submitted) data immediately instead of only starting to
 * fill in from the next new/edited/deleted entry onward. See
 * SkillsUtilization::recomputeForTraining(), which is what now keeps this
 * table in sync going forward (called from Beneficiary\SkillsController).
 */
return new class extends Migration
{
    public function up(): void
    {
        SkillProgressEntry::whereNotNull('training_id')
            ->distinct()
            ->pluck('training_id')
            ->each(fn (int $trainingId) => SkillsUtilization::recomputeForTraining($trainingId));
    }

    public function down(): void
    {
        // Data backfill only — nothing to reverse.
    }
};
