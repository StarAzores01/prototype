<?php

namespace App\Http\Controllers\Evaluator;

use App\Http\Controllers\Controller;
use App\Models\ImpactAssessment;
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

        $assessmentsQuery = ImpactAssessment::with('training')->where('evaluator_id', $evaluatorId);

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
            'training_id'     => 'nullable|integer|exists:trainings,id',
            'assessment_file' => 'nullable|file',
        ])->validate();

        $fileName = null;
        $originalName = null;
        $fileType = null;
        $fileSize = 0;

        if ($request->hasFile('assessment_file')) {
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
            $file->storeAs('uploads', $fileName, 'public');
        }

        ImpactAssessment::create([
            'evaluator_id'  => Auth::guard('web')->id(),
            'training_id'   => $data['training_id'] ?? null,
            'title'         => $data['title'],
            'description'   => $data['description'] ?? null,
            'file_name'     => $fileName,
            'original_name' => $originalName,
            'file_type'     => $fileType,
            'file_size'     => $fileSize,
            'status'        => 'Submitted',
            'submitted_at'  => now(),
        ]);

        return redirect()->route('evaluator.impact_assessment')->with('success', 'Impact assessment submitted successfully.');
    }

    private function delete(Request $request)
    {
        $assessment = ImpactAssessment::where('id', (int) $request->input('assessment_id'))
            ->where('evaluator_id', Auth::guard('web')->id())
            ->first();

        if ($assessment) {
            if ($assessment->file_name) {
                Storage::disk('public')->delete('uploads/'.$assessment->file_name);
            }
            $assessment->delete();

            return redirect()->route('evaluator.impact_assessment')->with('success', 'Assessment deleted.');
        }

        return redirect()->route('evaluator.impact_assessment');
    }
}
