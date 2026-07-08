<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Training;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    /**
     * List documents uploaded for the training, plus the upload form.
     */
    public function index(Request $request, Training $training): View
    {
        $this->authorizeTrainingAccess($request, $training);

        $training->load(['documents.uploadedBy']);

        return view('documents.index', [
            'training' => $training,
            'routePrefix' => $this->routePrefix($request),
        ]);
    }

    /**
     * Upload a new document for the training.
     */
    public function store(Request $request, Training $training): RedirectResponse
    {
        $this->authorizeTrainingAccess($request, $training);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'visibility' => ['required', Rule::in(['public', 'private'])],
            'file' => [
                'required',
                'file',
                'mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg',
                'max:5120', // 5MB, in kilobytes
            ],
        ]);

        $file = $validated['file'];
        $path = $file->store("documents/{$training->id}", 'public');

        $training->documents()->create([
            'uploaded_by' => $request->user()->id,
            'title' => $validated['title'],
            'visibility' => $validated['visibility'],
            'file_path' => $path,
            'file_type' => $file->getClientOriginalExtension(),
        ]);

        return redirect()
            ->route($this->routePrefix($request).'.documents.index', $training)
            ->with('status', 'Document uploaded successfully.');
    }

    /**
     * Download/view the document's file.
     */
    public function download(Request $request, Training $training, Document $document): StreamedResponse
    {
        $this->authorizeTrainingAccess($request, $training);
        $this->ensureDocumentBelongsToTraining($training, $document);

        return Storage::disk('public')->download(
            $document->file_path,
            $document->title.'.'.$document->file_type
        );
    }

    /**
     * Delete the document and its stored file.
     */
    public function destroy(Request $request, Training $training, Document $document): RedirectResponse
    {
        $this->authorizeTrainingAccess($request, $training);
        $this->ensureDocumentBelongsToTraining($training, $document);

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return redirect()
            ->route($this->routePrefix($request).'.documents.index', $training)
            ->with('status', 'Document deleted.');
    }

    /**
     * Extension Coordinators may manage documents for any training.
     * Project Leaders may only manage documents for trainings they
     * are assigned to.
     */
    private function authorizeTrainingAccess(Request $request, Training $training): void
    {
        if ($request->user()->role === 'project_leader') {
            abort_unless($training->project_leader_id === $request->user()->id, 403);
        }
    }

    /**
     * Guard against downloading/deleting a document via the wrong
     * training's URL.
     */
    private function ensureDocumentBelongsToTraining(Training $training, Document $document): void
    {
        abort_unless($document->training_id === $training->id, 404);
    }

    /**
     * Route name prefix differs by role since EC and PL have separate
     * URL namespaces for the same underlying document feature.
     */
    private function routePrefix(Request $request): string
    {
        return $request->user()->role === 'project_leader'
            ? 'project-leader.trainings'
            : 'extension-coordinator.trainings';
    }
}
