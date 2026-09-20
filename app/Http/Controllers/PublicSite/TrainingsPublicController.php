<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicSite\Concerns\ResolvesPublicNavData;
use App\Models\Training;

class TrainingsPublicController extends Controller
{
    use ResolvesPublicNavData;

    public function index()
    {
        // Only Activities the EC has explicitly featured under Manage
        // Public Site Content → Trainings AND published — same data source
        // as the landing page's "Courses Offered" section (see
        // PublicSite\LandingController) — not every Activity in the system.
        // visibleOnPublicSite() switches to EC's own unpublished draft
        // selection while her "View Live" preview is active (see
        // App\Support\PagePreview), so she sees what she's about to
        // publish; every other visitor still only ever sees published rows.
        $trainings = Training::with('trainer')
            ->visibleOnPublicSite()
            ->orderByRaw("CASE status WHEN 'Ongoing' THEN 0 WHEN 'Proposed' THEN 1 WHEN 'Completed' THEN 2 ELSE 3 END")
            ->orderBy('date_start')
            ->get();

        return view('public.trainings-public', $this->publicNavData() + [
            'trainings' => $trainings,
        ]);
    }
}
