<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicSite\Concerns\ResolvesPublicNavData;

class TrainingsPublicController extends Controller
{
    use ResolvesPublicNavData;

    public function index()
    {
        return view('public.trainings-public', $this->publicNavData());
    }
}
