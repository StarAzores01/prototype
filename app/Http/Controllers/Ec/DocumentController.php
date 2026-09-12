<?php

namespace App\Http\Controllers\Ec;

use App\Http\Controllers\Concerns\GroupsDocumentsByProgram;
use App\Http\Controllers\Concerns\HandlesDocumentUploads;
use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Training;
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
     * Grouped by Parent Program (see GroupsDocumentsByProgram) — replaces
     * the old group-by-upload-date scheme entirely. `?archived=1` switches
     * the whole list to the Archived Documents view; EC can see and act on
     * every document either way, no ownership restriction (unlike the
     * Trainer version).
     */
    public function index(Request $request)
    {
        $q = trim($request->query('q', ''));
        $archived = $request->boolean('archived');

        $docsQuery = Document::with(['training', 'program', 'activity', 'uploader', 'archivedBy'])
            ->when($archived, fn ($query) => $query->archived(), fn ($query) => $query->active());

        if ($q) {
            $docsQuery->where(function ($query) use ($q) {
                $query->where('original_name', 'like', "%{$q}%")
                    ->orWhereHas('training', fn ($t) => $t->where('title', 'like', "%{$q}%"))
                    ->orWhereHas('program', fn ($p) => $p->where('title', 'like', "%{$q}%"))
                    ->orWhereHas('activity', fn ($a) => $a->where('title', 'like', "%{$q}%"));
            });
        }

        $docs = $docsQuery->orderByDesc('created_at')->get();
        $totalCount = $docs->count();

        return view('ec.documents', array_merge([
            'activePage' => 'documents',
            'trainings'  => Training::orderBy('title')->get(['id', 'title']),
            'q'          => $q,
            'archived'   => $archived,
            'totalCount' => $totalCount,
        ], $this->groupDocumentsByProgram($docs)));
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
            return back()->with('error', $error);
        }

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

            return redirect()->route('ec.documents')->with('success', 'Document archived.');
        }

        return redirect()->route('ec.documents');
    }

    private function unarchive(Request $request)
    {
        $doc = Document::find((int) $request->input('doc_id'));
        if ($doc) {
            $doc->update(['archived_at' => null, 'archived_by' => null]);

            return redirect()->route('ec.documents')->with('success', 'Document restored.');
        }

        return redirect()->route('ec.documents');
    }
}
