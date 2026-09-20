<?php

namespace App\Models;

use App\Support\PagePreview;
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
        'published_value',
        'updated_by',
    ];

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Look up one section's current LIVE (published) content, falling
     * back to $default when EC hasn't published a value for that section
     * yet (no row, or the row's published_value is null/blank).
     *
     * This is the only lookup public Blade views should ever call — it
     * deliberately reads published_value, never content_value, so a
     * draft edit EC hasn't published yet never leaks onto the public
     * site. See put() for the draft write path and publishAll() for how
     * a draft becomes published.
     *
     * Returns the raw stored value for every content_type — for 'image'
     * that's a ready-to-use public URL, for 'video_link' a raw URL
     * string, for 'text' the text itself (rendered with {!! !!} by
     * callers, matching the trust level EC-authored content already has
     * elsewhere in this app — e.g. training/program descriptions).
     *
     * When EC's own "View Live" preview is active (see App\Support\
     * PagePreview), this instead reads content_value — her latest saved
     * draft, even if it hasn't been published yet — bypassing the public
     * cache entirely so the preview never shows a stale row. Nothing
     * about this path is reachable by a regular visitor.
     */
    public static function get(string $pageKey, string $sectionKey, ?string $default = null): ?string
    {
        if (PagePreview::active()) {
            $row = static::query()
                ->where('page_key', $pageKey)
                ->where('section_key', $sectionKey)
                ->first();

            $value = $row?->content_value ?? $row?->published_value;

            return $value !== null && $value !== '' ? $value : $default;
        }

        $value = Cache::rememberForever(
            static::cacheKey($pageKey, $sectionKey),
            fn () => static::query()
                ->where('page_key', $pageKey)
                ->where('section_key', $sectionKey)
                ->value('published_value')
        );

        return $value !== null && $value !== '' ? $value : $default;
    }

    /**
     * Create/update one section's DRAFT value (content_value). Does not
     * touch published_value and does not evict the public get() cache —
     * the change only reaches the public site once publishAll() runs.
     */
    public static function put(string $pageKey, string $sectionKey, string $contentType, ?string $contentValue, ?int $updatedBy): self
    {
        return static::updateOrCreate(
            ['page_key' => $pageKey, 'section_key' => $sectionKey],
            ['content_type' => $contentType, 'content_value' => $contentValue, 'updated_by' => $updatedBy]
        );
    }

    /** Whether this row's draft differs from what's currently live. */
    public function hasUnpublishedChanges(): bool
    {
        return (string) $this->content_value !== (string) $this->published_value;
    }

    /**
     * Copies every row's draft (content_value) into its published_value
     * and evicts each changed row's public-read cache — called from
     * inside Ec\PublishController@publishAll's transaction. Returns the
     * number of rows actually published (skips rows already in sync).
     */
    public static function publishAll(): int
    {
        $published = 0;

        static::query()->get()->each(function (self $row) use (&$published) {
            if (! $row->hasUnpublishedChanges()) {
                return;
            }

            $row->published_value = $row->content_value;
            $row->save();
            static::forgetCache($row->page_key, $row->section_key);
            $published++;
        });

        return $published;
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
