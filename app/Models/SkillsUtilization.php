<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkillsUtilization extends Model
{
    protected $table = 'skills_utilization';
    public $timestamps = false;

    protected $fillable = [
        'training_id', 'personal_use_pct', 'income_gen_pct', 'employment_pct', 'nc2_cert_pct',
        'community_service_pct', 'training_application_pct', 'other_pct', 'recorded_at',
    ];

    protected function casts(): array
    {
        return ['recorded_at' => 'datetime'];
    }

    public function training()
    {
        return $this->belongsTo(Training::class);
    }

    /**
     * How each SkillProgressEntry::OUTCOME_TYPES value maps onto this
     * table's percentage columns — one column per outcome type, so every
     * option a beneficiary can log shows up somewhere in the Skills
     * Utilization overview (EC dashboard and Trainer/Project Leader
     * "Skills Utilization Overview").
     */
    private const OUTCOME_TYPE_MAP = [
        'personal_use_pct'         => 'Personal Development',
        'income_gen_pct'           => 'Income Generating',
        'employment_pct'           => 'Employment / Work',
        'community_service_pct'    => 'Community Service',
        'training_application_pct' => 'Training Application',
        'other_pct'                => 'Other',
    ];

    /**
     * Recomputes and saves this training's skills_utilization row from its
     * beneficiaries' own progress journal (SkillProgressEntry.outcome_type
     * — see Beneficiary\SkillsController, the only place that writes
     * progress entries). Each percentage is "of the training's enrolled
     * participants, how many have logged at least one entry of this
     * outcome type" — a beneficiary who logs the same outcome twice is
     * only counted once.
     *
     * Called after every progress-entry create/update/delete that touches
     * this training (see Beneficiary\SkillsController) — there is no
     * scheduled job recomputing this, so it only ever reflects entries
     * actually on file at the time one was last saved.
     *
     * $trainingId is nullable because a progress entry can be logged
     * without an activity attached (general skill use, not tied to a
     * specific training) — those entries never feed into any training's
     * row, since there is nothing to attribute them to.
     */
    public static function recomputeForTraining(?int $trainingId): void
    {
        if (! $trainingId) {
            return;
        }

        $totalParticipants = Participant::where('training_id', $trainingId)->count();

        if ($totalParticipants === 0) {
            static::where('training_id', $trainingId)->delete();

            return;
        }

        $counts = SkillProgressEntry::where('training_id', $trainingId)
            ->whereIn('outcome_type', array_values(self::OUTCOME_TYPE_MAP))
            ->distinct()
            ->get(['beneficiary_id', 'outcome_type'])
            ->groupBy('outcome_type')
            ->map(fn ($rows) => $rows->pluck('beneficiary_id')->unique()->count());

        $percentages = [];
        foreach (self::OUTCOME_TYPE_MAP as $column => $outcomeType) {
            $percentages[$column] = round((($counts[$outcomeType] ?? 0) / $totalParticipants) * 100, 1);
        }

        static::updateOrCreate(
            ['training_id' => $trainingId],
            $percentages + ['recorded_at' => now()]
        );
    }
}
