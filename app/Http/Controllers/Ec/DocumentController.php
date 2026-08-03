<?php

namespace App\Http\Controllers\Ec;

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
    private array $allowedTypes = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'mp4'];

    private int $maxFileSize = 20 * 1024 * 1024; // 20 MB, same as the original MAX_FILE_SIZE

    public function index(Request $request)
    {
        $q = trim($request->query('q', ''));

        $docsQuery = Document::with(['training', 'uploader']);
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

    private function upload(Request $request)
    {
        if (! $request->hasFile('file')) {
            return back()->with('error', 'Please choose a file to upload.');
        }

        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension());

        if (! in_array($ext, $this->allowedTypes, true)) {
            return back()->with('error', 'File type not allowed.');
        }
        if ($file->getSize() > $this->maxFileSize) {
            return back()->with('error', 'File exceeds 20 MB limit.');
        }

        $storedName = uniqid('doc_') . '.' . $ext;
        $file->storeAs('uploads', $storedName, 'public');

        $visibility = in_array($request->input('visibility'), ['private', 'ec_trainer', 'public'], true)
            ? $request->input('visibility')
            : 'public';

        Document::create([
            'original_name' => $file->getClientOriginalName(),
            'file_name'     => $storedName,
            'file_type'     => $ext,
            'file_size'     => $file->getSize(),
            'training_id'   => $request->input('training_id') ?: null,
            'uploaded_by'   => Auth::guard('web')->id(),
            'visibility'    => $visibility,
        ]);

        return redirect()->route('ec.documents')->with('success', 'File uploaded successfully.');
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
            Storage::disk('public')->delete('uploads/' . $doc->file_name);
            $doc->delete();

            return redirect()->route('ec.documents')->with('success', 'Document deleted.');
        }

        return redirect()->route('ec.documents');
    }
}
