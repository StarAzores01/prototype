<?php

namespace App\Http\Controllers\Trainer;

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

    public function index()
    {
        $trainerId = Auth::guard('web')->id();

        $myDocs = Document::with('training')
            ->where('uploaded_by', $trainerId)
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
        $sharedDocs = Document::with(['training', 'uploader'])
            ->where('uploaded_by', '!=', $trainerId)
            ->whereIn('visibility', ['public', 'ec_trainer'])
            ->where(function ($q) use ($trainerId) {
                $q->whereNull('program_id')->whereNull('activity_id')
                    ->orWhereHas('program', fn ($p) => $p->visibleToTrainer($trainerId))
                    ->orWhereHas('activity', fn ($a) => $a->visibleToTrainer($trainerId));
            })
            ->orderByDesc('created_at')
            ->get();

        return view('trainer.documents', [
            'activePage'  => 'documents',
            'myDocs'      => $myDocs,
            'sharedDocs'  => $sharedDocs,
            'myTrainings' => Training::visibleToTrainer($trainerId)->orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function store(Request $request)
    {
        $action = $request->input('action');

        return match ($action) {
            'upload'         => $this->upload($request),
            'set_visibility' => $this->setVisibility($request),
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
}
