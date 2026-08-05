<?php

namespace App\Http\Controllers\Ec;

use App\Http\Controllers\Controller;
use App\Models\EvaluatorWhitelist;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class EvaluatorController extends Controller
{
    private array $departments = [
        'College of Administration, Business, Hospitality, and Accountancy',
        'College of Agriculture',
        'College of Allied Medicine',
        'College of Arts and Sciences',
        'College of Engineering',
        'College of Industrial Technology',
        'College of Teacher Education',
    ];

    public function index(Request $request)
    {
        $q = trim($request->query('q', ''));

        $evaluatorsQuery = User::where('role', 'evaluator');
        if ($q) {
            $evaluatorsQuery->where(function ($query) use ($q) {
                $query->where('first_name', 'like', "%{$q}%")
                    ->orWhere('last_name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('id_number', 'like', "%{$q}%");
            });
        }
        $evaluators = $evaluatorsQuery->orderBy('first_name')->get();

        $whitelist = EvaluatorWhitelist::orderByDesc('created_at')->get();

        return view('ec.evaluators', [
            'activePage'  => 'evaluators',
            'evaluators'  => $evaluators,
            'whitelist'   => $whitelist,
            'departments' => $this->departments,
            'q'           => $q,
        ]);
    }

    public function store(Request $request)
    {
        $action = $request->input('action');

        return match ($action) {
            'toggle'           => $this->toggle($request),
            'edit_evaluator'   => $this->editEvaluator($request),
            'add_whitelist'    => $this->addWhitelist($request),
            'remove_whitelist' => $this->removeWhitelist($request),
            default            => back(),
        };
    }

    private function toggle(Request $request)
    {
        $user = User::where('id', (int) $request->input('user_id'))->where('role', 'evaluator')->first();
        if ($user) {
            $user->update(['is_active' => ! $user->is_active]);
        }

        return back()->with('success', 'Evaluator access updated.');
    }

    private function editEvaluator(Request $request)
    {
        $data = Validator::make($request->all(), [
            'user_id'    => 'required|integer|exists:users,id',
            'first_name' => 'required|string|max:80',
            'last_name'  => 'required|string|max:80',
            'email'      => 'required|email|max:120|unique:users,email,'.$request->input('user_id'),
            'department' => ['nullable', Rule::in($this->departments)],
            'id_number'  => 'nullable|string|max:40',
        ])->validate();

        User::where('id', $data['user_id'])->where('role', 'evaluator')->update([
            'first_name' => $data['first_name'],
            'last_name'  => $data['last_name'],
            'email'      => $data['email'],
            // Own column now — was previously (incorrectly) written into
            // `position`, which is a CHECK constraint restricted to academic
            // ranks for trainers and would reject any department name.
            'department' => $data['department'] ?? null,
            'id_number'  => $data['id_number'] ?? '',
        ]);

        return back()->with('success', 'Evaluator updated.');
    }

    private function addWhitelist(Request $request)
    {
        $data = Validator::make($request->all(), [
            'first_name' => 'required|string|max:80',
            'last_name'  => 'required|string|max:80',
            'department' => 'required|string',
        ])->validate();

        $dup = EvaluatorWhitelist::whereRaw('LOWER(first_name) = LOWER(?)', [$data['first_name']])
            ->whereRaw('LOWER(last_name) = LOWER(?)', [$data['last_name']])
            ->exists();

        if ($dup) {
            return back()->with('error', 'This evaluator is already on the approved list.');
        }

        $evId = DB::transaction(function () {
            $year = date('Y');
            $like = 'EV-' . $year . '-%';

            $lastWl = EvaluatorWhitelist::where('id_number', 'like', $like)
                ->orderByDesc('id_number')->lockForUpdate()->value('id_number');
            $lastUsr = User::where('id_number', 'like', $like)
                ->orderByDesc('id_number')->lockForUpdate()->value('id_number');

            $seq = 1;
            foreach ([$lastWl, $lastUsr] as $last) {
                if ($last) {
                    $parts = explode('-', $last);
                    $n = (int) end($parts);
                    if ($n >= $seq) {
                        $seq = $n + 1;
                    }
                }
            }

            return 'EV-' . $year . '-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
        });

        EvaluatorWhitelist::create([
            'first_name' => $data['first_name'],
            'last_name'  => $data['last_name'],
            'department' => $data['department'],
            'id_number'  => $evId,
        ]);

        return back()->with('success', "{$data['first_name']} {$data['last_name']} added to the approved evaluators list. Assigned ID: {$evId}");
    }

    private function removeWhitelist(Request $request)
    {
        EvaluatorWhitelist::where('id', $request->input('whitelist_id'))
            ->where('is_registered', false)
            ->delete();

        return back()->with('success', 'Evaluator removed from approved list.');
    }
}
