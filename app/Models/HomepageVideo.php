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

    /**
     * Resolve video_url into an inline, autoplay-muted embed when the link
     * is from a platform we know how to embed (YouTube, Vimeo, or a direct
     * video file). Returns null for anything else (Google Drive, Facebook,
     * Dropbox, or any link we don't recognize) — those keep falling back to
     * the existing "open in a new tab" placeholder, since a generic link
     * can't be embedded or autoplayed inline.
     *
     * @return array{type: string, src: string}|null
     */
    public function embed(): ?array
    {
        $url = trim((string) $this->video_url);
        if ($url === '') {
            return null;
        }

        // YouTube: watch?v=ID, youtu.be/ID, /embed/ID, /shorts/ID
        if (preg_match('#(?:youtube(?:-nocookie)?\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([\w-]{11})#i', $url, $m)) {
            return [
                'type' => 'youtube',
                'src'  => "https://www.youtube-nocookie.com/embed/{$m[1]}?autoplay=1&mute=1&playsinline=1&rel=0",
            ];
        }

        // Vimeo: vimeo.com/12345678 (with or without /video/)
        if (preg_match('#vimeo\.com/(?:video/)?(\d+)#i', $url, $m)) {
            return [
                'type' => 'vimeo',
                'src'  => "https://player.vimeo.com/video/{$m[1]}?autoplay=1&muted=1&playsinline=1",
            ];
        }

        // Direct video file link
        if (preg_match('#\.(mp4|webm|ogg|mov)(?:\?.*)?$#i', $url)) {
            return ['type' => 'file', 'src' => $url];
        }

        return null;
    }
}
