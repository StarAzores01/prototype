<?php

namespace App\Http\Controllers\Ec;

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

    public function index(Request $request)
    {
        $q = trim($request->query('q', ''));

        $docsQuery = Document::with(['training', 'program', 'activity', 'uploader']);
        if ($q) {
            $docsQuery->where(function ($query) use ($q) {
                $query->where('original_name', 'like', "%{$q}%")
                    ->orWhereHas('training', fn ($t) => $t->where('title', 'like', "%{$q}%"));
            });
        }
        $docs = $docsQuery->orderByDesc('created_at')->get();

        return view('ec.documents', [
            'activePage' => 'documents',
            'docs'       => $docs,
            'trainings'  => Training::orderBy('title')->get(['id', 'title']),
            'q'          => $q,
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
}
