<?php

namespace App\Http\Controllers\Ec;

use App\Http\Controllers\Concerns\GroupsDocumentsByProgram;
use App\Http\Controllers\Concerns\HandlesDocumentUploads;
use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Training;
use App\Services\ProgramLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DocumentController extends Controller
{
    use HandlesDocumentUploads;
    use GroupsDocumentsByProgram;

    /**
     * Split into "My Documents" (uploaded by the current EC user) vs.
     * "Shared Documents" (uploaded by anyone else — other EC accounts,
     * Project Leaders) — same split Trainer\DocumentController already
     * uses for its own Documents page. EC still has full, unrestricted
     * manage rights (set visibility / archive / delete) over BOTH
     * sections, unlike the Trainer version where "Shared Documents" is
     * read-only — that's enforced by the store() actions below having no
     * ownership check, exactly as before this split. `?archived=1` only
     * ever affects "My Documents", matching Trainer: archiving is a
     * personal, per-document action, so "Shared Documents" always shows
     * everyone else's active documents regardless of the toggle.
     */
    public function index(Request $request)
    {
        $ecId = Auth::guard('web')->id();
        $q = trim($request->query('q', ''));
        $archived = $request->boolean('archived');

        $applySearch = function ($query) use ($q) {
            if (! $q) {
                return;
            }
            $query->where(function ($w) use ($q) {
                $w->where('original_name', 'like', "%{$q}%")
                    ->orWhereHas('training', fn ($t) => $t->where('title', 'like', "%{$q}%"))
                    ->orWhereHas('program', fn ($p) => $p->where('title', 'like', "%{$q}%"))
                    ->orWhereHas('activity', fn ($a) => $a->where('title', 'like', "%{$q}%"));
            });
        };

        $myDocsQuery = Document::with(['training', 'program', 'activity', 'uploader', 'archivedBy'])
            ->where('uploaded_by', $ecId)
            ->when($archived, fn ($query) => $query->archived(), fn ($query) => $query->active());
        $applySearch($myDocsQuery);
        $myDocs = $myDocsQuery->orderByDesc('created_at')->get();

        $sharedDocsQuery = Document::with(['training', 'program', 'activity', 'uploader', 'archivedBy'])
            ->where('uploaded_by', '!=', $ecId)
            ->active();
        $applySearch($sharedDocsQuery);
        $sharedDocs = $sharedDocsQuery->orderByDesc('created_at')->get();

        $myGroups = $this->groupDocumentsByProgram($myDocs);
        $sharedGroups = $this->groupDocumentsByProgram($sharedDocs);

        return view('ec.documents', [
            'activePage'          => 'documents',
            'trainings'           => Training::orderBy('title')->get(['id', 'title']),
            'q'                   => $q,
            'archived'            => $archived,
            'myProgramGroups'     => $myGroups['programGroups'],
            'myGeneral'           => $myGroups['general'],
            'myCount'             => $myDocs->count(),
            'sharedProgramGroups' => $sharedGroups['programGroups'],
            'sharedGeneral'       => $sharedGroups['general'],
            'sharedCount'         => $sharedDocs->count(),
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
        [$ok, $error, $document] = $this->storeDocumentUpload($request, Auth::guard('web')->id(), [
            'training_id' => $request->input('training_id') ?: null,
        ]);

        if (! $ok) {
            return back()->with('error', $error);
        }

        ProgramLogService::recordDocument(
            $document,
            $document->isLink() ? ProgramLogService::ACTION_ADDED_LINK : ProgramLogService::ACTION_UPLOADED_FILE
        );

        return redirect()->route('ec.documents')->with('success', 'Document uploaded successfully.');
    }

    private function setVisibility(Request $request)
    {
        $data = Validator::make($request->all(), [
            'doc_id'     => 'required|exists:documents,id',
            'visibility' => ['required', Rule::in(['private', 'ec_trainer', 'public'])],
        ])->validate();

        Document::where('id', $data['doc_id'])->update(['visibility' => $data['visibility']]);

        return redirect()->route('ec.documents');
    }

    private function delete(Request $request)
    {
        $doc = Document::find($request->input('doc_id'));
        if ($doc) {
            // Snapshot + log BEFORE deleting — the log row stores its own
            // copy of name/type/location, so it stays readable after the
            // Document row (and, if it's a file, the stored file) are gone.
            ProgramLogService::recordDocument($doc, ProgramLogService::ACTION_DELETED);

            if ($doc->file_name) {
                Storage::disk('local')->delete('uploads/'.$doc->file_name);
            }
            $doc->delete();

            return redirect()->route('ec.documents')->with('success', 'Document deleted.');
        }

        return redirect()->route('ec.documents');
    }

    /**
     * EC can archive any document — same unrestricted floor as delete()/
     * setVisibility() above, no extra ownership check (unlike the Trainer
     * version, which only allows a trainer to archive their own uploads).
     * Archiving never touches the file itself or its visibility — it just
     * removes the row from the default active list; it stays fully
     * downloadable through its existing link.
     */
    private function archive(Request $request)
    {
        $doc = Document::find((int) $request->input('doc_id'));
        if ($doc) {
            $doc->update(['archived_at' => now(), 'archived_by' => Auth::guard('web')->id()]);

            ProgramLogService::recordDocument($doc, ProgramLogService::ACTION_ARCHIVED);

            return redirect()->route('ec.documents')->with('success', 'Document archived.');
        }

        return redirect()->route('ec.documents');
    }

    private function unarchive(Request $request)
    {
        $doc = Document::find((int) $request->input('doc_id'));
        if ($doc) {
            $doc->update(['archived_at' => null, 'archived_by' => null]);

            ProgramLogService::recordDocument($doc, ProgramLogService::ACTION_RESTORED);

            return redirect()->route('ec.documents')->with('success', 'Document restored.');
        }

        return redirect()->route('ec.documents');
    }
}
