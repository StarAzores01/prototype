<?php

namespace App\Http\Controllers\Evaluator;

use App\Http\Controllers\Controller;
use App\Models\ImpactAssessment;
use App\Models\ImpactAssessmentForm;
use App\Models\Notification;
use App\Models\Participant;
use App\Models\Training;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ImpactAssessmentController extends Controller
{
    private array $allowedTypes = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];

    private int $maxFileSize = 5 * 1024 * 1024; // 5 MB

    public function index(Request $request)
    {
        $evaluatorId = Auth::guard('web')->id();
        $q = trim($request->query('q', ''));

        $assessmentsQuery = ImpactAssessment::with('training.program')->where('evaluator_id', $evaluatorId);

        if ($q) {
            $assessmentsQuery->where('title', 'like', "%{$q}%");
        }

        return view('evaluator.impact_assessment', [
            'activePage'   => 'impact_assessment',
            'assessments'  => $assessmentsQuery->orderByDesc('submitted_at')->get(),
            'trainings'    => Training::where('status', 'Completed')->orderBy('title')->get(['id', 'title']),
            'q'            => $q,
        ]);
    }

    public function store(Request $request)
    {
        $action = $request->input('action');

        return match ($action) {
            'submit' => $this->submit($request),
            'delete' => $this->delete($request),
            default  => back(),
        };
    }

    private function submit(Request $request)
    {
        $data = Validator::make($request->all(), [
            'title'           => 'required|string|max:200',
            'description'     => 'nullable|string',
            // The activity decides which participants receive the survey, and
            // the due date is what those participants are given to answer by.
            'training_id'     => 'required|integer|exists:trainings,id',
            'due_date'        => 'required|date|after_or_equal:today',
            // mimes: content-sniffs the actual bytes (via fileinfo), not just the
            // claimed filename extension — a renamed .php/.html can't pass this.
            'assessment_file' => 'nullable|file|max:5120|mimes:pdf,doc,docx,jpg,jpeg,png',
            'mode'            => 'nullable|in:file,form',
            'form_label'      => 'nullable|array',
            'form_label.*'    => 'nullable|string|max:255',
            'form_type'       => 'nullable|array',
            'form_options'    => 'nullable|array',
            'form_required'   => 'nullable|array',
        ], [
            'assessment_file.uploaded' => 'The file is too large. Please choose a file under 5 MB (or use Build a Form).',
            'assessment_file.max'      => 'The file is too large. Please choose a file under 5 MB.',
            'assessment_file.mimes'    => 'Only PDF, DOC, DOCX, JPG or PNG files are allowed.',
        ])->validate();

        // "Build a Form" mode: the questions are stored as JSON on the
        // assessment instead of an uploaded file. Each of the form_* arrays
        // posts exactly one entry per question row, so they line up by index.
        $formData = null;
        if (($data['mode'] ?? 'file') === 'form') {
            $labels  = $request->input('form_label', []);
            $types   = $request->input('form_type', []);
            $options = $request->input('form_options', []);
            $required = $request->input('form_required', []);
            $allowed = ['text', 'textarea', 'radio', 'select', 'rating'];

            $formData = [];
            foreach ($labels as $i => $label) {
                if (trim((string) $label) === '') {
                    continue;
                }
                $type = in_array($types[$i] ?? 'text', $allowed, true) ? $types[$i] : 'text';
                $isRequired = ($required[$i] ?? '0') === '1';

                $formData[] = [
                    'label'   => trim($label),
                    'type'    => $type,
                    'required' => $isRequired,
                    'options' => in_array($type, ['radio', 'select'], true)
                        ? array_values(array_filter(array_map('trim', explode("\n", (string) ($options[$i] ?? '')))))
                        : [],
                ];
            }

            if (! $formData) {
                return back()->withInput()->with('error', 'Please add at least one question to your form.');
            }
        }

        // File mode needs a real file. Without this check, an upload PHP had
        // rejected (too big for the server's limit, say) was silently dropped
        // and the assessment was saved with "No file".
        if ($formData === null) {
            $upload = $request->file('assessment_file');
            if (! $upload || ! $upload->isValid()) {
                $reason = $upload ? $upload->getErrorMessage() : 'No file was chosen.';

                return back()->withInput()->with('error', 'Your file was not uploaded: '.$reason.' Please choose a PDF, DOC, DOCX, JPG or PNG file under 5 MB, or use Build a Form.');
            }
        }

        $fileName = null;
        $originalName = null;
        $fileType = null;
        $fileSize = 0;

        if ($formData === null && $request->hasFile('assessment_file')) {
            $file = $request->file('assessment_file');
            $ext = strtolower($file->getClientOriginalExtension());

            if (! in_array($ext, $this->allowedTypes, true)) {
                return back()->with('error', 'Only PDF, DOC, DOCX, JPG, PNG files are allowed.');
            }
            if ($file->getSize() > $this->maxFileSize) {
                return back()->with('error', 'File must be under 5 MB.');
            }

            $fileName = 'ia_'.bin2hex(random_bytes(8)).'.'.$ext;
            $originalName = $file->getClientOriginalName();
            $fileType = $ext;
            $fileSize = $file->getSize();
            $file->storeAs('uploads', $fileName, 'local');
        }

        $assessment = ImpactAssessment::create([
            'evaluator_id'  => Auth::guard('web')->id(),
            'training_id'   => $data['training_id'],
            'due_date'      => $data['due_date'],
            'title'         => $data['title'],
            'description'   => $data['description'] ?? null,
            'form_data'     => $formData,
            'file_name'     => $fileName,
            'original_name' => $originalName,
            'file_type'     => $fileType,
            'file_size'     => $fileSize,
            'status'        => 'Submitted',
            'submitted_at'  => now(),
        ]);

        return redirect()->route('evaluator.impact_assessment')->with('success', 'Impact assessment submitted. The Extension Coordinator will review it and create the participant form.');
    }

    private function delete(Request $request)
    {
        $assessment = ImpactAssessment::where('id', (int) $request->input('assessment_id'))
            ->where('evaluator_id', Auth::guard('web')->id())
            ->first();

        if ($assessment) {
            if ($assessment->file_name) {
                Storage::disk('local')->delete('uploads/'.$assessment->file_name);
            }
            $assessment->delete();

            return redirect()->route('evaluator.impact_assessment')->with('success', 'Assessment deleted.');
        }

        return redirect()->route('evaluator.impact_assessment');
    }
}
