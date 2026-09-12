<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Concerns\GroupsDocumentsByProgram;
use App\Http\Controllers\Concerns\HandlesDocumentUploads;
use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Training;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    use HandlesDocumentUploads;
    use GroupsDocumentsByProgram;

    /**
     * Both cards are now grouped by Parent Program (see
     * GroupsDocumentsByProgram), replacing the old group-by-upload-date
     * scheme. The `?archived=1` toggle only ever affects "My Documents" —
     * archiving is a personal, ownership-scoped action (see archive()/
     * unarchive() below), so "Shared Documents" always shows other
     * trainers' active documents regardless of the toggle.
     */
    public function index(Request $request)
    {
        $trainerId = Auth::guard('web')->id();
        $archived = $request->boolean('archived');

        $myDocs = Document::with(['training', 'program', 'activity'])
            ->where('uploaded_by', $trainerId)
            ->when($archived, fn ($query) => $query->archived(), fn ($query) => $query->active())
            ->orderByDesc('created_at')
            ->get();

        // A doc scoped to a specific Program's or Activity's repository is
        // only "shared" to trainers actually on that team — visibility
        // (public/ec_trainer) governs the file itself, but a program/
        // activity scope narrows *who sees it listed here* on top of that,
        // otherwise every trainer company-wide would see every program's
        // internal documents just because they're a trainer. A doc with
        // neither scope (the plain dashboard-level "general" upload) is
        // unaffected — it's visible exactly as before.
        $sharedDocs = Document::with(['training', 'uploader', 'program', 'activity'])
            ->where('uploaded_by', '!=', $trainerId)
            ->whereIn('visibility', ['public', 'ec_trainer'])
            ->active()
            ->where(function ($q) use ($trainerId) {
                $q->whereNull('program_id')->whereNull('activity_id')
                    ->orWhereHas('program', fn ($p) => $p->visibleToTrainer($trainerId))
                    ->orWhereHas('activity', fn ($a) => $a->visibleToTrainer($trainerId));
            })
            ->orderByDesc('created_at')
            ->get();

        $myGroups = $this->groupDocumentsByProgram($myDocs);
        $sharedGroups = $this->groupDocumentsByProgram($sharedDocs);

        return view('trainer.documents', [
            'activePage'           => 'documents',
            'archived'             => $archived,
            'myProgramGroups'      => $myGroups['programGroups'],
            'myGeneral'            => $myGroups['general'],
            'myCount'              => $myDocs->count(),
            'sharedProgramGroups'  => $sharedGroups['programGroups'],
            'sharedGeneral'        => $sharedGroups['general'],
            'sharedCount'          => $sharedDocs->count(),
            'myTrainings'          => Training::visibleToTrainer($trainerId)->orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function store(Request $request)
    {
        $action = $request->input('action');

        return match ($action) {
            'upload'         => $this->upload($request),
            'set_visibility' => $this->setVisibility($request),
            'archive'        => $this->archive($request),
            'unarchive'      => $this->unarchive($request),
            'delete'         => $this->delete($request),
            default          => back(),
        };
    }

    /**
     * Dashboard-level "general" upload — file or link, program_id and
     * activity_id always null here (the legacy training_id dropdown is
     * unaffected and still optional).
     */
    private function upload(Request $request)
    {
        [$ok, $error] = $this->storeDocumentUpload($request, Auth::guard('web')->id(), [
            'training_id' => $request->input('training_id') ?: null,
        ]);

        if (! $ok) {
            return redirect()->route('trainer.documents')->with('error', $error);
        }

        return redirect()->route('trainer.documents')->with('success', 'Document uploaded.');
    }

    private function setVisibility(Request $request)
    {
        $trainerId = Auth::guard('web')->id();
        $vis = $request->input('visibility', 'public');

        if (in_array($vis, ['private', 'ec_trainer', 'public'], true)) {
            // Only allow changing own documents.
            Document::where('id', (int) $request->input('doc_id'))
                ->where('uploaded_by', $trainerId)
                ->update(['visibility' => $vis]);
        }

        return redirect()->route('trainer.documents');
    }

    private function delete(Request $request)
    {
        $trainerId = Auth::guard('web')->id();
        $doc = Document::where('id', (int) $request->input('doc_id'))->where('uploaded_by', $trainerId)->first();

        if ($doc) {
            if ($doc->file_name) {
                Storage::disk('local')->delete('uploads/'.$doc->file_name);
            }
            $doc->delete();

            return redirect()->route('trainer.documents')->with('success', 'Document deleted.');
        }

        return redirect()->route('trainer.documents');
    }

    /**
     * Ownership-scoped exactly like delete()/setVisibility() above — a
     * trainer can only archive their own uploads, never someone else's
     * (that's what keeps "Shared Documents" unaffected by this action).
     * Archiving never touches the file or its visibility — it just removes
     * the row from the default active list; it stays fully downloadable
     * through its existing link.
     */
    private function archive(Request $request)
    {
        $trainerId = Auth::guard('web')->id();
        $doc = Document::where('id', (int) $request->input('doc_id'))->where('uploaded_by', $trainerId)->first();

        if ($doc) {
            $doc->update(['archived_at' => now(), 'archived_by' => $trainerId]);

            return redirect()->route('trainer.documents')->with('success', 'Document archived.');
        }

        return redirect()->route('trainer.documents');
    }

    private function unarchive(Request $request)
    {
        $trainerId = Auth::guard('web')->id();
        $doc = Document::where('id', (int) $request->input('doc_id'))->where('uploaded_by', $trainerId)->first();

        if ($doc) {
            $doc->update(['archived_at' => null, 'archived_by' => null]);

            return redirect()->route('trainer.documents')->with('success', 'Document restored.');
        }

        return redirect()->route('trainer.documents');
    }
}
