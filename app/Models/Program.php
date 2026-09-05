<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Program extends Model
{
    protected $fillable = [
        'title', 'description', 'area', 'timeline_start', 'timeline_end',
        'budget_allocated', 'status', 'is_locked', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'timeline_start'   => 'date',
            'timeline_end'     => 'date',
            'budget_allocated' => 'decimal:2',
            'is_locked'        => 'boolean',
        ];
    }

    /**
     * budget_allocated is fixed for good at creation — no amendment path, no
     * EC override, nothing. This guard is the last line of defense: even a
     * raw ->update(['budget_allocated' => ...]) or a tinker session can't
     * move it once the row exists, regardless of what any future controller
     * change might accidentally allow through validation.
     */
    protected static function booted(): void
    {
        static::saving(function (self $program) {
            if ($program->exists && $program->isDirty('budget_allocated')) {
                throw new \RuntimeException('budget_allocated is permanently fixed at creation and cannot be modified.');
            }
        });
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Activities under this program (the trainings table — see class-level note in Training). */
    public function trainings()
    {
        return $this->hasMany(Training::class);
    }

    public function amendments()
    {
        return $this->hasMany(ProgramAmendment::class);
    }

    /** Every assigned trainer, lead and members alike. Use lead()/members() to split them. */
    public function teamMembers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'program_team_members')
            ->withPivot('member_role')
            ->withTimestamps();
    }

    /** The single trainer leading this program (enforced app-side: exactly 1 per program). */
    public function lead(): BelongsToMany
    {
        return $this->teamMembers()->wherePivot('member_role', 'lead');
    }

    /** The supporting trainers (enforced app-side: at most 3 per program). */
    public function members(): BelongsToMany
    {
        return $this->teamMembers()->wherePivot('member_role', 'member');
    }

    /**
     * Every Trainer-role query that lists/gates a trainer's own programs
     * should use this — matches Training::scopeVisibleToTrainer()'s idea,
     * but a Program has no trainer_id shortcut column, so it's team-pivot
     * only. True for lead and member alike; use isLeadUser() to tell them
     * apart where the distinction matters (unlock/team actions).
     */
    public function scopeVisibleToTrainer($query, int $trainerId)
    {
        return $query->whereHas('teamMembers', fn ($q) => $q->where('user_id', $trainerId));
    }

    /** Used to gate lead-only actions (Request Unlock & Amend, Manage Team) to this program's Project Lead. */
    public function isLeadUser(int $userId): bool
    {
        return $this->lead()->where('users.id', $userId)->exists();
    }

    /** True if the given user is lead OR member — the membership check behind Trainer\ProgramController's detail-page 403. */
    public function isVisibleTo(int $userId): bool
    {
        return $this->teamMembers()->where('users.id', $userId)->exists();
    }
}
