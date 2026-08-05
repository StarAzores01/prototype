<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Models\EvalForm;
use App\Models\Participant;
use App\Models\SkillsForm;
use App\Models\Training;
use App\Models\TrainingDoc;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ActivityController extends Controller
{
    private array $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    private int $maxFileSize = 20 * 1024 * 1024; // 20 MB, same as the original MAX_FILE_SIZE

    public function index(Request $request)
    {
        $trainerId = Auth::guard('web')->id();

        $myTrainings = Training::where('trainer_id', $trainerId)
            ->orderByDesc('date_start')
            ->get(['id', 'title', 'area', 'date_start', 'status']);

        $selectedId = (int) $request->query('training', $myTrainings->first()?->id ?? 0);

        $training = null;
        $participants = collect();
        $evalForm = null;
        $evalResponses = collect();
        $skillsForm = null;
        $skillsResponses = collect();
        $photos = collect();

        if ($selectedId) {
            $training = Training::where('id', $selectedId)->where('trainer_id', $trainerId)->first();
        }

        if ($training) {
            $participants = Participant::where('training_id', $selectedId)->orderBy('full_name')->get();

            $evalForm = EvalForm::where('training_id', $selectedId)->whereNotNull('sent_at')->first();
            if ($evalForm) {
                $evalResponses = $evalForm->responses()->with('beneficiary')->orderBy('submitted_at')->get();
            }

            $skillsForm = SkillsForm::where('training_id', $selectedId)->whereNotNull('sent_at')->first();
            if ($skillsForm) {
                $skillsResponses = $skillsForm->responses()->with('beneficiary')->orderBy('submitted_at')->get();
            }

            $photos = TrainingDoc::where('training_id', $selectedId)->orderBy('created_at')->get();
        }

        return view('trainer.activity', [
            'activePage'      => 'activity',
            'trainerName'     => Auth::guard('web')->user()->full_name,
            'myTrainings'     => $myTrainings,
            'selectedId'      => $selectedId,
            'training'        => $training,
            'participants'    => $participants,
            'evalForm'        => $evalForm,
            'evalResponses'   => $evalResponses,
            'skillsForm'      => $skillsForm,
            'skillsResponses' => $skillsResponses,
            'photos'          => $photos,
        ]);
    }

    public function store(Request $request)
    {
        $action = $request->input('action');

        return match ($action) {
            'upload_doc' => $this->uploadDoc($request),
            'delete_doc' => $this->deleteDoc($request),
            default      => back(),
        };
    }

    private function uploadDoc(Request $request)
    {
        $trainerId = Auth::guard('web')->id();
        $trainingId = (int) $request->input('training_id');

        Validator::make($request->all(), [
            'caption' => 'nullable|string|max:255',
            // mimes: content-sniffs the actual bytes (via fileinfo), not just the
            // claimed filename extension — a renamed .php/.html can't pass this.
            'photo'   => 'nullable|file|mimes:jpg,jpeg,png,gif,webp',
        ])->validate();

        $caption = trim((string) $request->input('caption', ''));

        $training = Training::where('id', $trainingId)->where('trainer_id', $trainerId)->first();

        if ($training && $request->hasFile('photo')) {
            $file = $request->file('photo');
            $ext = strtolower($file->getClientOriginalExtension());

            if (! in_array($ext, $this->allowedTypes, true)) {
                return redirect()->route('trainer.activity', ['training' => $trainingId])
                    ->with('error', 'Only image files are allowed (JPG, PNG, GIF, WEBP).');
            }
            if ($file->getSize() > $this->maxFileSize) {
                return redirect()->route('trainer.activity', ['training' => $trainingId])
                    ->with('error', 'File exceeds 20 MB limit.');
            }

            // random_bytes instead of uniqid() — uniqid() is time-based and
            // guessable, which mattered once file URLs were made access-checked
            // rather than fully public (see FileDownloadController).
            $stored = 'tdoc_'.bin2hex(random_bytes(8)).'.'.$ext;
            $file->storeAs('uploads', $stored, 'local');

            TrainingDoc::create([
                'training_id' => $trainingId,
                'caption'     => $caption,
                'file_name'   => $stored,
                'file_type'   => $ext,
                'uploaded_by' => $trainerId,
            ]);

            return redirect()->route('trainer.activity', ['training' => $trainingId])->with('success', 'Photo uploaded.');
        }

        return redirect()->route('trainer.activity', ['training' => $trainingId]);
    }

    private function deleteDoc(Request $request)
    {
        $trainerId = Auth::guard('web')->id();
        $docId = (int) $request->input('doc_id');
        $trainingId = (int) $request->input('training_id');

        $doc = TrainingDoc::where('id', $docId)
            ->whereHas('training', fn ($t) => $t->where('trainer_id', $trainerId))
            ->first();

        if ($doc) {
            Storage::disk('local')->delete('uploads/'.$doc->file_name);
            $doc->delete();

            return redirect()->route('trainer.activity', ['training' => $trainingId])->with('success', 'Photo removed.');
        }

        return redirect()->route('trainer.activity', ['training' => $trainingId]);
    }
}
