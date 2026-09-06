<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Single-row settings model for the public homepage's video montage.
 * Always read/written through current(), which pins the row to id 1 —
 * there is only ever one homepage video, unlike the per-program montage.
 */
class HomepageVideo extends Model
{
    protected $fillable = [
        'video_url',
        'video_title',
        'updated_by',
    ];

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** The one homepage video row, or null if EC hasn't set one yet. */
    public static function current(): ?self
    {
        return static::find(1);
    }
}
