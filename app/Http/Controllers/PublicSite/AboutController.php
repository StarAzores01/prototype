<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicSite\Concerns\ResolvesPublicNavData;

class AboutController extends Controller
{
    use ResolvesPublicNavData;

    public function index()
    {
        return view('public.about', $this->publicNavData());
    }
}
