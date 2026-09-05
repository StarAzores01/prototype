<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Training;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ModuleController extends Controller
{
    private array $allowedTypes = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'mp4'];

    private int $maxFileSize = 20 * 1024 * 1024; // 20 MB, same as the original MAX_FILE_SIZE

    public function index()
    {
        $trainerId = Auth::guard('web')->id();

        $docs = Document::with('training')
            ->where('uploaded_by', $trainerId)
            ->orderByDesc('created_at')
            ->get();

        $totalSize = $docs->sum('file_size');
        $pdfCount = $docs->where('file_type', 'pdf')->count();
        $vidCount = $docs->where('file_type', 'mp4')->count();
        $imgCount = $docs->whereIn('file_type', ['jpg', 'jpeg', 'png'])->count();

        return view('trainer.modules', [
            'activePage'  => 'modules',
            'docs'        => $docs,
            'myTrainings' => Training::visibleToTrainer($trainerId)->orderBy('title')->get(['id', 'title']),
            'totalSize'   => $totalSize,
            'pdfCount'    => $pdfCount,
            'vidCount'    => $vidCount,
            'imgCount'    => $imgCount,
        ]);
    }

    public function store(Request $request)
    {
        $action = $request->input('action');

        return match ($action) {
            'upload' => $this->upload($request),
            'delete' => $this->delete($request),
            default  => back(),
        };
    }

    private function upload(Request $request)
    {
        if (! $request->hasFile('file')) {
            return redirect()->route('trainer.modules');
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
            return redirect()->route('trainer.modules')->with('error', 'File type not allowed.');
        }
        if ($file->getSize() > $this->maxFileSize) {
            return redirect()->route('trainer.modules')->with('error', 'File exceeds 20 MB limit.');
        }

        // random_bytes instead of uniqid() — uniqid() is time-based and
        // guessable, which mattered once file URLs were made access-checked
        // rather than fully public (see FileDownloadController).
        $stored = 'mod_'.bin2hex(random_bytes(8)).'.'.$ext;
        $file->storeAs('uploads', $stored, 'local');

        Document::create([
            'original_name' => $orig,
            'file_name'     => $stored,
            'file_type'     => $ext,
            'file_size'     => $file->getSize(),
            'training_id'   => $request->input('training_id') ?: null,
            'uploaded_by'   => Auth::guard('web')->id(),
        ]);

        return redirect()->route('trainer.modules')->with('success', 'Module uploaded successfully.');
    }

    private function delete(Request $request)
    {
        $trainerId = Auth::guard('web')->id();
        $doc = Document::where('id', (int) $request->input('doc_id'))->where('uploaded_by', $trainerId)->first();

        if ($doc) {
            Storage::disk('local')->delete('uploads/'.$doc->file_name);
            $doc->delete();

            return redirect()->route('trainer.modules')->with('success', 'Module deleted.');
        }

        return redirect()->route('trainer.modules');
    }
}
