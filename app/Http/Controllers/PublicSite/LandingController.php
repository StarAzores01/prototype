<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class LandingController extends Controller
{
    /**
     * Matches eclandingpage.php: only bounces an already-logged-in EC
     * straight to their dashboard. Other roles hitting the public homepage
     * while logged in still see the marketing page, exactly as the
     * original did (no equivalent check for trainer/evaluator/beneficiary).
     */
    public function index()
    {
        if (Auth::guard('web')->check() && Auth::guard('web')->user()->role === 'extension_coordinator') {
            return redirect()->route('ec.dashboard');
        }

        return view('public.landing');
    }
}
