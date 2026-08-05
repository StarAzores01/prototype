<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\ImpactAssessment;
use App\Models\TrainingDoc;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Authenticated, permission-checked replacement for the old direct
 * asset('storage/uploads/...') links. Previously every uploaded file was a
 * fully public, unauthenticated static URL regardless of its declared
 * visibility — this closes that gap by streaming the file through a route
 * that checks the requester's role first.
 *
 * Covers all three upload tables that had public links in the Blade views:
 * documents (has a real visibility tier), training_docs and
 * impact_assessments (no visibility column — access is simply "any
 * authenticated user of any of the 4 roles", the same floor as
 * documents.visibility === 'public'). All three routes are registered
 * behind the auth:web,beneficiary middleware, so a fully logged-out
 * request never reaches this controller at all.
 */
class FileDownloadController extends Controller
{
    public function document(Document $document)
    {
        $this->authorizeVisibility($document->visibility);

        return $this->stream($document->file_name, $document->original_name);
    }

    public function trainingDoc(TrainingDoc $trainingDoc)
    {
        $this->authorizeAnyRole();

        return $this->stream($trainingDoc->file_name, $trainingDoc->caption ?: $trainingDoc->file_name);
    }

    public function impactAssessment(ImpactAssessment $impactAssessment)
    {
        $this->authorizeAnyRole();

        abort_if(! $impactAssessment->file_name, 404);

        return $this->stream($impactAssessment->file_name, $impactAssessment->original_name ?: $impactAssessment->file_name);
    }

    /**
     * private = EC only. ec_trainer = EC + Project Leader (trainer).
     * public = any authenticated user, any of the 4 roles.
     */
    private function authorizeVisibility(string $visibility): void
    {
        $role = $this->currentRole();
        abort_if($role === null, 403);

        $allowed = match ($visibility) {
            'private' => $role === 'extension_coordinator',
            'ec_trainer' => in_array($role, ['extension_coordinator', 'trainer'], true),
            'public' => true,
            default => false,
        };

        abort_unless($allowed, 403);
    }

    private function authorizeAnyRole(): void
    {
        abort_if($this->currentRole() === null, 403);
    }

    /** 'extension_coordinator' | 'trainer' | 'evaluator' | 'beneficiary' | null */
    private function currentRole(): ?string
    {
        if (Auth::guard('web')->check()) {
            return Auth::guard('web')->user()->role;
        }

        if (Auth::guard('beneficiary')->check()) {
            return 'beneficiary';
        }

        return null;
    }

    /**
     * Uploads live on the "local" disk (storage/app/private/uploads), not
     * "public" — the public disk is symlinked to public/storage and would
     * make every file fetchable by a guessed filename with zero auth,
     * regardless of anything this controller checks. Moving uploads off the
     * public disk is what actually makes the visibility rules above mean
     * anything; the route+auth check alone would just be a second front
     * door next to one that's still wide open.
     *
     * ?download=1 forces a "Save As" download; otherwise renders inline
     * (matches the original's two link styles).
     */
    private function stream(string $fileName, ?string $downloadName = null): StreamedResponse
    {
        $path = 'uploads/'.$fileName;

        abort_unless(Storage::disk('local')->exists($path), 404);

        return request()->boolean('download')
            ? Storage::disk('local')->download($path, $downloadName)
            : Storage::disk('local')->response($path, $downloadName);
    }
}
