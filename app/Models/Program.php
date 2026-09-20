<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Program extends Model
{
    protected $fillable = [
        'title', 'description', 'area', 'timeline_start', 'timeline_end',
        'extended_end_date', 'budget_allocated', 'status', 'created_by',
        'cover_image', 'video_url', 'video_title',
    ];

    protected function casts(): array
    {
        return [
            'timeline_start'     => 'date',
            'timeline_end'       => 'date',
            'extended_end_date'  => 'date',
            'budget_allocated'   => 'decimal:2',
        ];
    }

    /**
     * budget_allocated, timeline_start and timeline_end are all fixed for
     * good at creation â€” no amendment path, no EC override, nothing. This
     * guard is the last line of defense: even a raw ->update([...]) or a
     * tinker session can't move any of them once the row exists, regardless
     * of what any future controller change might accidentally allow through
     * validation. The only sanctioned way to move a program's effective end
     * date is extended_end_date (see effective_end_date below), set via the
     * EC-only "Extend Timeline" action â€” it's a separate column precisely so
     * it can change without ever touching the original timeline.
     */
    protected static function booted(): void
    {
        static::saving(function (self $program) {
            if ($program->exists && $program->isDirty(['budget_allocated', 'timeline_start', 'timeline_end'])) {
                throw new \RuntimeException('budget_allocated, timeline_start, and timeline_end are permanently fixed at creation and cannot be modified. Use extended_end_date to extend the program\'s effective end date instead.');
            }
            // Status is always computed from dates — overwrite whatever was passed.
            $today = now()->startOfDay();
            $start = $program->timeline_start?->startOfDay();
            $end   = ($program->extended_end_date ?? $program->timeline_end)?->startOfDay();

            if (! $start || $today->lt($start)) {
                $program->status = 'Proposed';
            } elseif (! $end || $today->lte($end)) {
                $program->status = 'Ongoing';
            } else {
                $program->status = 'Completed';
            }
        });
    }

    /**
     * Status is fully automatic — derived from timeline_start / effective_end_date.
     * - No dates set                           → Proposed
     * - Today < timeline_start                 → Proposed
     * - timeline_start ≤ today ≤ effective_end → Ongoing
     * - Today > effective_end                  → Completed
     */
    public function getStatusAttribute(): string
    {
        $today = now()->startOfDay();
        $start = $this->timeline_start?->startOfDay();
        $end   = $this->effective_end_date?->startOfDay();

        if (! $start || $today->lt($start)) {
            return 'Proposed';
        }
        if (! $end || $today->lte($end)) {
            return 'Ongoing';
        }
        return 'Completed';
    }

    /** What every "end date"/"deadline" display should read app-wide â€” the extension if one exists, else the original timeline_end. */
    public function getEffectiveEndDateAttribute()
    {
        return $this->extended_end_date ?? $this->timeline_end;
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Activities under this program (the trainings table â€” see class-level note in Training). */
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
     * should use this â€” matches Training::scopeVisibleToTrainer()'s idea,
     * but a Program has no trainer_id shortcut column, so it's team-pivot
     * only. True for lead and member alike; use isLeadUser() to tell them
     * apart where the distinction matters (team actions).
     */
    public function scopeVisibleToTrainer($query, int $trainerId)
    {
        return $query->whereHas('teamMembers', fn ($q) => $q->where('user_id', $trainerId));
    }

    /** Used to gate lead-only actions (Manage Team, status changes) to this program's Project Lead. */
    public function isLeadUser(int $userId): bool
    {
        return $this->lead()->where('users.id', $userId)->exists();
    }

    /** Every Activity Log entry for this program. */
    public function logs()
    {
        return $this->hasMany(ProgramLog::class);
    }

    /** True if the given user is lead OR member â€” the membership check behind Trainer\ProgramController's detail-page 403. */
    public function isVisibleTo(int $userId): bool
    {
        return $this->teamMembers()->where('users.id', $userId)->exists();
    }
}

