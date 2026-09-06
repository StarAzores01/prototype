<?php

namespace App\Http\Controllers\Ec;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\HomepageVideo;
use App\Models\Program;
use App\Models\SkillsUtilization;
use App\Models\Training;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DashboardController extends Controller
{
    public function index()
    {
        $recentTrainings = Training::with('trainer')
            ->withCount('participants as enrolled')
            ->orderByDesc('created_at')
            ->get();

        $latestDocs = Document::with('training')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $skills = [
            'personal'   => round((float) (SkillsUtilization::avg('personal_use_pct') ?? 72), 1),
            'income'     => round((float) (SkillsUtilization::avg('income_gen_pct') ?? 55), 1),
            'employment' => round((float) (SkillsUtilization::avg('employment_pct') ?? 38), 1),
        ];

        // Trainers + Programs listed in the "Create Activity" modal (shared partial).
        $trainers = \App\Models\User::where('role', 'trainer')->where('is_active', true)->get(['id', 'first_name', 'last_name']);
        $programs = Program::orderBy('title')->get(['id', 'title']);

        return view('ec.dashboard', [
            'activePage'       => 'dashboard',
            'recentTrainings'  => $recentTrainings,
            'latestDocs'       => $latestDocs,
            'skills'           => $skills,
            'trainers'         => $trainers,
            'programs'         => $programs,
            'userFirstName'    => Auth::guard('web')->user()->first_name,
            'homepageVideo'    => HomepageVideo::current(),
        ]);
    }

    public function store(Request $request)
    {
        $action = $request->input('action');

        return match ($action) {
            'upload_homepage_video' => $this->uploadHomepageVideo($request),
            default                 => back(),
        };
    }

    /**
     * Save / replace the public homepage's video montage. Same convention
     * as Program's (now-removed) per-program video: an external link
     * (YouTube, Google Drive, Vimeo, etc.) — the system stores the link,
     * not the file. EC only — enforced by the route middleware
     * (role:extension_coordinator), same as every other Ec\* write.
     */
    private function uploadHomepageVideo(Request $request)
    {
        $data = Validator::make($request->all(), [
            'video_url'   => 'required|url|max:2048',
            'video_title' => 'nullable|string|max:255',
        ])->validate();

        HomepageVideo::query()->updateOrCreate(['id' => 1], [
            'video_url'   => $data['video_url'],
            'video_title' => $data['video_title'] ?? null,
            'updated_by'  => Auth::guard('web')->id(),
        ]);

        return redirect()->route('ec.dashboard')->with('success', 'Homepage video montage updated.');
    }
}
