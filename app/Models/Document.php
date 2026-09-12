<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class Document extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'file_name', 'original_name', 'file_type', 'file_size',
        'training_id', 'uploaded_by', 'visibility',
        'program_id', 'activity_id', 'link_url', 'link_type',
        'archived_at', 'archived_by',
    ];

    protected function casts(): array
    {
        return [
            'created_at'  => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // A document is either an uploaded file or a link — enforced here
        // (not a DB check constraint; see the 2026_09_05_000006 migration).
        // Controllers should still validate this explicitly via a Form
        // Request for a proper user-facing error before it ever gets here.
        static::saving(function (Document $document) {
            if (blank($document->file_name) && blank($document->link_url)) {
                throw new InvalidArgumentException(
                    'A document must have either a file_name (uploaded file) or a link_url (link).'
                );
            }
        });
    }

    public function training()
    {
        return $this->belongsTo(Training::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    /** Aliased for clarity — a Training row, referred to as an "Activity" in the UI. */
    public function activity()
    {
        return $this->belongsTo(Training::class, 'activity_id');
    }

    public function archivedBy()
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    public function isLink(): bool
    {
        return blank($this->file_name) && filled($this->link_url);
    }

    public function isArchived(): bool
    {
        return ! is_null($this->archived_at);
    }

    /** Default scope for every normal listing — excludes archived documents. */
    public function scopeActive($query)
    {
        return $query->whereNull('archived_at');
    }

    /** Used by the "Archived Documents" toggle — only archived documents. */
    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }
}
