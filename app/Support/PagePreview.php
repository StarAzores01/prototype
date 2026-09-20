<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;

/**
 * Powers the "View Live" preview in Manage Public Site Content: when EC
 * clicks it, the public page opens with ?ec_preview=1 appended, and every
 * draft-aware read below (PageContent::get(), Training's public-site
 * queries/accessors) switches from the published/live value to EC's own
 * unpublished draft, so she can see exactly what she's about to publish
 * before clicking "Publish All Changes" — without any other visitor ever
 * seeing draft content.
 *
 * Gated to the ?ec_preview=1 query flag AND an authenticated
 * extension_coordinator on the "web" guard, so the flag can never leak
 * draft content to the public even if a link carrying it is shared.
 */
class PagePreview
{
    public static function active(): bool
    {
        return request()?->boolean('ec_preview')
            && Auth::guard('web')->check()
            && Auth::guard('web')->user()->role === 'extension_coordinator';
    }
}
