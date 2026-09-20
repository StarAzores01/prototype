<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A "Post & Announcement" authored by the Extension Coordinator, managed
 * under Manage Public Site Content → Posts and shown on the public landing
 * page's Posts & Announcements section. See Ec\PostController.
 */
class Post extends Model
{
    protected $fillable = [
        'title', 'description', 'image', 'status', 'author_id', 'views', 'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'views'        => 'integer',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** Only posts EC has published are shown on the public site. */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    /** Short excerpt for card/list previews. */
    public function excerpt(int $limit = 140): string
    {
        return \Illuminate\Support\Str::limit(strip_tags((string) $this->description), $limit);
    }
}
