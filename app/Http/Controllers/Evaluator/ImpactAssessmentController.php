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

    private int $maxFileSize = 10 * 1024 * 1024; // 10 MB, same as the original

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
            'assessment_file' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png',
            'mode'            => 'nullable|in:file,form',
            'form_label'      => 'nullable|array',
            'form_label.*'    => 'nullable|string|max:255',
            'form_type'       => 'nullable|array',
            'form_options'    => 'nullable|array',
            'form_required'   => 'nullable|array',
            'form_answer'     => 'nullable|array',
            'form_answer.*'   => 'nullable|string|max:5000',
        ])->validate();

        // "Build a Form" mode: questions + the evaluator's answers are stored
        // as JSON on the assessment instead of an uploaded file. Each of the
        // four form_* arrays posts exactly one entry per question row, so
        // they line up by index.
        $formData = null;
        if (($data['mode'] ?? 'file') === 'form') {
            $labels  = $request->input('form_label', []);
            $types   = $request->input('form_type', []);
            $options = $request->input('form_options', []);
            $answers = $request->input('form_answer', []);
            $required = $request->input('form_required', []);
            $allowed = ['text', 'textarea', 'radio', 'select', 'rating'];

            $formData = [];
            foreach ($labels as $i => $label) {
                if (trim((string) $label) === '') {
                    continue;
                }
                $type = in_array($types[$i] ?? 'text', $allowed, true) ? $types[$i] : 'text';
                $isRequired = ($required[$i] ?? '0') === '1';
                $answer = trim((string) ($answers[$i] ?? ''));

                if ($isRequired && $answer === '') {
                    return back()->withInput()->with('error', 'Please answer every required question: "'.trim($label).'".');
                }

                $formData[] = [
                    'label'   => trim($label),
                    'type'    => $type,
                    'required' => $isRequired,
                    'options' => in_array($type, ['radio', 'select'], true)
                        ? array_values(array_filter(array_map('trim', explode("\n", (string) ($options[$i] ?? '')))))
                        : [],
                    'answer'  => $answer,
                ];
            }

            if (! $formData) {
                return back()->withInput()->with('error', 'Please add at least one question to your form.');
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
                return back()->with('error', 'File must be under 10 MB.');
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

        // Build a Form: the evaluator already wrote the questions, so the EC
        // has nothing left to build — the survey goes straight to the
        // activity's participants (sent date = now, due date = the one the
        // evaluator just set). The evaluator's own answers are not copied.
        if ($formData) {
            $survey = ImpactAssessmentForm::create([
                'training_id' => $assessment->training_id,
                'title'       => $assessment->title,
                'fields'      => array_map(function ($q) {
                    return [
                        'label'    => $q['label'],
                        'type'     => $q['type'] === 'rating' ? 'radio' : $q['type'],
                        'required' => $q['required'],
                        'options'  => $q['type'] === 'rating'
                            ? ['5 - Excellent', '4 - Very Good', '3 - Good', '2 - Fair', '1 - Poor']
                            : $q['options'],
                    ];
                }, $formData),
                'created_by'  => Auth::guard('web')->id(),
                'sent_at'     => now(),
                'due_date'    => $data['due_date'],
            ]);

            $assessment->update(['participant_form_id' => $survey->id]);

            $beneficiaryIds = Participant::where('training_id', $survey->training_id)
                ->whereNotNull('beneficiary_id')
                ->distinct()
                ->pluck('beneficiary_id');

            foreach ($beneficiaryIds as $beneficiaryId) {
                Notification::create([
                    'user_id'     => $beneficiaryId,
                    'role'        => 'beneficiary',
                    'training_id' => $survey->training_id,
                    'message'     => 'A new Impact Assessment survey has been sent to you.',
                    'link'        => route('beneficiary.impact_assessment'),
                ]);
            }

            return redirect()->route('evaluator.impact_assessment')
                ->with('success', 'Impact assessment submitted and sent to the participants ('.$beneficiaryIds->count().').');
        }

        return redirect()->route('evaluator.impact_assessment')->with('success', 'Impact assessment submitted. The Extension Coordinator will create the participant form.');
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
