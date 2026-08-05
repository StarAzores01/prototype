<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Training;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class DocumentController extends Controller
{
    private array $allowedTypes = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'mp4'];

    private int $maxFileSize = 20 * 1024 * 1024; // 20 MB, same as the original MAX_FILE_SIZE

    public function index()
    {
        $trainerId = Auth::guard('web')->id();

        $myDocs = Document::with('training')
            ->where('uploaded_by', $trainerId)
            ->orderByDesc('created_at')
            ->get();

        $sharedDocs = Document::with(['training', 'uploader'])
            ->where('uploaded_by', '!=', $trainerId)
            ->whereIn('visibility', ['public', 'ec_trainer'])
            ->orderByDesc('created_at')
            ->get();

        return view('trainer.documents', [
            'activePage'  => 'documents',
            'myDocs'      => $myDocs,
            'sharedDocs'  => $sharedDocs,
            'myTrainings' => Training::where('trainer_id', $trainerId)->orderBy('title')->get(['id', 'title']),
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
            return redirect()->route('trainer.documents');
        }

        Validator::make($request->all(), [
            'training_id' => 'nullable|integer|exists:trainings,id',
            // mimes: content-sniffs the actual bytes (via fileinfo), not just the
            // claimed filename extension — a renamed .php/.html can't pass this.
            'file'        => 'file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,mp4',
        ])->validate();

        $file = $request->file('file');
        $orig = $file->getClientOriginalName();
        $ext = strtolower($file->getClientOriginalExtension());

        if (! in_array($ext, $this->allowedTypes, true)) {
            return redirect()->route('trainer.documents')->with('error', 'File type not allowed.');
        }
        if ($file->getSize() > $this->maxFileSize) {
            return redirect()->route('trainer.documents')->with('error', 'File exceeds 20 MB limit.');
        }

        // random_bytes instead of uniqid() — uniqid() is time-based and
        // guessable, which mattered once file URLs were made access-checked
        // rather than fully public (see FileDownloadController).
        $stored = 'doc_'.bin2hex(random_bytes(8)).'.'.$ext;
        $file->storeAs('uploads', $stored, 'local');

        $visibility = in_array($request->input('visibility'), ['private', 'ec_trainer', 'public'], true)
            ? $request->input('visibility')
            : 'public';

        Document::create([
            'original_name' => $orig,
            'file_name'     => $stored,
            'file_type'     => $ext,
            'file_size'     => $file->getSize(),
            'training_id'   => $request->input('training_id') ?: null,
            'uploaded_by'   => Auth::guard('web')->id(),
            'visibility'    => $visibility,
        ]);

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
            Storage::disk('local')->delete('uploads/'.$doc->file_name);
            $doc->delete();

            return redirect()->route('trainer.documents')->with('success', 'Document deleted.');
        }

        return redirect()->route('trainer.documents');
    }
}
