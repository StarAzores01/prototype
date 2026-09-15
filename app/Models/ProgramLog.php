<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Append-only activity log row for a Program.
 *
 * Rows are immutable once written — never update or delete them.
 * No updated_at, no soft-deletes, intentionally.
 *
 * The static ::record() method on ProgramLogService is the ONLY
 * sanctioned write path; do not call ProgramLog::create() directly
 * from controllers.
 */
class ProgramLog extends Model
{
    // Immutable — no updated_at
    public $timestamps = false;

    protected $fillable = [
        'program_id',
        'training_id',
        'user_id',
        'actor_name',
        'actor_role',
        'action',
        'entity_type',
        'entity_id',
        'item_name',
        'item_type',
        'location_name',
        'details',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'details'    => 'array',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * The Activity this log entry is scoped to, if any.
     * Uses the Training model (same table — "Activity" is the UI label).
     */
    public function activity()
    {
        return $this->belongsTo(Training::class, 'training_id');
    }

    /**
     * The staff user who performed the action.
     * May be null if the user was later deleted.
     */
    public function actor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}