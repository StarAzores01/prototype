<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicSite\Concerns\ResolvesPublicNavData;
use App\Models\HomepageVideo;

class LandingController extends Controller
{
    use ResolvesPublicNavData;

    /**
     * The shared feed/marketing page. All 4 roles land here after login
     * (see AuthenticatedSessionController::store()) instead of being bounced
     * straight to their dashboard, so the nav here always reflects whichever
     * guard (web or beneficiary) is currently authenticated and offers a
     * "Go to Dashboard" link — identically for all 4 roles.
     */
    public function index()
    {
        return view('public.landing', $this->publicNavData() + [
            'homepageVideo' => HomepageVideo::current(),
        ]);
    }
}
