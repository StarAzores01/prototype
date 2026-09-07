<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * EC-editable text/media content for the public-facing pages. See the
 * page_contents migration and Ec\PageContentController.
 *
 * The only thing public Blade views should ever call is the static
 * get() helper below — never query this model directly from a view.
 * get() is read on every single public page load (landing, about,
 * contact, trainings-public, choose-role, privacy, terms), so it's
 * cached indefinitely per (page_key, section_key) and only invalidated
 * by PageContentController when EC actually saves a change — see
 * forgetCache().
 */
class PageContent extends Model
{
    protected $fillable = [
        'page_key',
        'section_key',
        'content_type',
        'content_value',
        'updated_by',
    ];

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Look up one section's current content, falling back to $default
     * when EC hasn't edited that section yet (no row) or cleared it back
     * to blank (row exists with a null/empty content_value).
     *
     * Returns the raw stored value for every content_type — for 'image'
     * that's a ready-to-use public URL, for 'video_link' a raw URL
     * string, for 'text' the text itself (rendered with {!! !!} by
     * callers, matching the trust level EC-authored content already has
     * elsewhere in this app — e.g. training/program descriptions).
     */
    public static function get(string $pageKey, string $sectionKey, ?string $default = null): ?string
    {
        $value = Cache::rememberForever(
            static::cacheKey($pageKey, $sectionKey),
            fn () => static::query()
                ->where('page_key', $pageKey)
                ->where('section_key', $sectionKey)
                ->value('content_value')
        );

        return $value !== null && $value !== '' ? $value : $default;
    }

    /**
     * Create/update one section's row and evict its cache entry so the
     * next public page load picks up the new value immediately.
     */
    public static function put(string $pageKey, string $sectionKey, string $contentType, ?string $contentValue, ?int $updatedBy): self
    {
        $row = static::updateOrCreate(
            ['page_key' => $pageKey, 'section_key' => $sectionKey],
            ['content_type' => $contentType, 'content_value' => $contentValue, 'updated_by' => $updatedBy]
        );

        static::forgetCache($pageKey, $sectionKey);

        return $row;
    }

    public static function forgetCache(string $pageKey, string $sectionKey): void
    {
        Cache::forget(static::cacheKey($pageKey, $sectionKey));
    }

    private static function cacheKey(string $pageKey, string $sectionKey): string
    {
        return "page_content:{$pageKey}:{$sectionKey}";
    }
}
