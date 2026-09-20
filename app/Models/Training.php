<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\BudgetItem;

/**
 * This is an "Activity" in the UI — the table/model name stays `trainings`/
 * `Training` on purpose (same pattern as the trainer/"Project Leader" label
 * split) so existing controllers/views referencing it don't break.
 */
class Training extends Model
{
    protected $fillable = [
        'title', 'area', 'description', 'date_start', 'date_end', 'status',
        'trainer_id', 'target_participants', 'budget_allocated', 'budget_used', 'created_by',
        'program_id', 'cover_image',
        // "Featured on public site" — EC's draft selection under Manage
        // Public Site Content → Trainings (see Ec\FeaturedActivityController).
        // is_featured/featured_image are the working draft; published_*
        // only change when Ec\PublishController@publishAll calls
        // publishFeature() below. See the
        // add_featured_fields_to_trainings_table migration.
        'is_featured', 'featured_image', 'published_is_featured', 'published_featured_image',
    ];

    protected function casts(): array
    {
        return [
            'date_start'             => 'date',
            'date_end'               => 'date',
            'budget_allocated'       => 'decimal:2',
            'budget_used'            => 'decimal:2',
            'target_participants'    => 'integer',
            'is_featured'            => 'boolean',
            'published_is_featured'  => 'boolean',
        ];
    }

    /**
     * budget_allocated is fixed for good at creation — only budget_used is
     * ever editable afterward. This guard is the last line of defense: even
     * a raw ->update(['budget_allocated' => ...]) or a tinker session can't
     * move it once the row exists, regardless of what any future controller
     * change might accidentally allow through validation.
     */
    protected static function booted(): void
    {
        static::saving(function (self $training) {
            if ($training->exists && $training->isDirty('budget_allocated')) {
                throw new \RuntimeException('budget_allocated is permanently fixed at creation and cannot be modified.');
            }
            // Status is always computed from dates — overwrite whatever was passed.
            $today = now()->startOfDay();
            $start = $training->date_start?->startOfDay();
            $end   = $training->date_end?->startOfDay();

            if (! $start || $today->lt($start)) {
                $training->status = 'Proposed';
            } elseif (! $end || $today->lte($end)) {
                $training->status = 'Ongoing';
            } else {
                $training->status = 'Completed';
            }
        });
    }

    /**
     * Status is fully automatic — derived from date_start / date_end.
     * - No dates set         → Proposed
     * - Today < date_start  → Proposed
     * - date_start ≤ today ≤ date_end (or no end date) → Ongoing
     * - Today > date_end    → Completed
     */
    public function getStatusAttribute(): string
    {
        $today = now()->startOfDay();
        $start = $this->date_start?->startOfDay();
        $end   = $this->date_end?->startOfDay();

        if (! $start || $today->lt($start)) {
            return 'Proposed';
        }
        if (! $end || $today->lte($end)) {
            return 'Ongoing';
        }
        return 'Completed';
    }

    public function trainer()
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    /**
     * Whether this activity's featured draft (is_featured/featured_image)
     * differs from what's currently published/live. NOT named isDirty() —
     * that collides with Eloquent's own Model::isDirty() and previously
     * crashed production when a model reused that name for a custom check.
     */
    public function hasUnpublishedFeatureChanges(): bool
    {
        return (bool) $this->is_featured !== (bool) $this->published_is_featured
            || (string) $this->featured_image !== (string) $this->published_featured_image;
    }

    /** Copies the featured working-draft columns into their published_* counterparts. */
    public function publishFeature(): void
    {
        $this->published_is_featured   = $this->is_featured;
        $this->published_featured_image = $this->featured_image;
        $this->save();
    }

    /** Only activities EC has featured AND published show up on the public site. */
    public function scopeFeaturedOnPublicSite($query)
    {
        return $query->where('published_is_featured', true);
    }

    /**
     * Same as scopeFeaturedOnPublicSite(), except while EC's own "View
     * Live" preview is active (see App\Support\PagePreview) it switches to
     * her draft selection (is_featured) instead of the published one, so
     * she sees exactly what she's about to publish. Public controllers
     * should call this rather than scopeFeaturedOnPublicSite() directly.
     */
    public function scopeVisibleOnPublicSite($query)
    {
        return \App\Support\PagePreview::active()
            ? $query->where('is_featured', true)
            : $query->where('published_is_featured', true);
    }

    /**
     * The featured photo to actually render — her draft image while
     * previewing, the published/live one for every real visitor.
     */
    public function getDisplayFeaturedImageAttribute(): ?string
    {
        return \App\Support\PagePreview::active() ? $this->featured_image : $this->published_featured_image;
    }

    /**
     * Every Trainer-role query that used to do where('trainer_id', $id)
     * should use this instead — trainer_id is kept in sync with the
     * activity_team_members 'lead' automatically (see EcTrainingController),
     * but a 'member' only ever shows up in activity_team_members, not in the
     * trainer_id column. This scope matches either, so both lead and member
     * see and can act on the activity.
     */
    public function scopeVisibleToTrainer($query, int $trainerId)
    {
        return $query->where(function ($q) use ($trainerId) {
            $q->where('trainer_id', $trainerId)
                ->orWhereHas('teamMembers', fn ($tm) => $tm->where('user_id', $trainerId));
        });
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function budgetItems()
    {
        return $this->hasMany(BudgetItem::class)->orderBy('created_at');
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    /** Every trainer assigned to this activity, lead and members alike. Use lead()/members() to split them. */
    public function teamMembers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'activity_team_members')
            ->withPivot('member_role')
            ->withTimestamps();
    }

    /** The single trainer leading this activity (enforced app-side: exactly 1 per activity). */
    public function lead(): BelongsToMany
    {
        return $this->teamMembers()->wherePivot('member_role', 'lead');
    }

    /** The supporting trainers (enforced app-side: at most 3 per activity). */
    public function members(): BelongsToMany
    {
        return $this->teamMembers()->wherePivot('member_role', 'member');
    }

    /** Used to gate the "Change Display Picture" action to EC + this activity's lead. */
    public function isLeadUser(int $userId): bool
    {
        return $this->lead()->where('users.id', $userId)->exists();
    }

    public function participants()
    {
        return $this->hasMany(Participant::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function trainingDocs()
    {
        return $this->hasMany(TrainingDoc::class);
    }

    public function evaluations()
    {
        return $this->hasMany(Evaluation::class);
    }

    public function attendance()
    {
        return $this->hasMany(Attendance::class);
    }

    public function skillsUtilization()
    {
        return $this->hasMany(SkillsUtilization::class);
    }

    public function evalForms()
    {
        return $this->hasMany(EvalForm::class);
    }

    public function skillsForms()
    {
        return $this->hasMany(SkillsForm::class);
    }

    public function impactAssessmentForms()
    {
        return $this->hasMany(ImpactAssessmentForm::class);
    }
}
