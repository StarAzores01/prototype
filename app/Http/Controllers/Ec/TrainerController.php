<?php

namespace App\Http\Controllers\Ec;

use App\Http\Controllers\Controller;
use App\Models\TrainerWhitelist;
use App\Models\Training;
use App\Models\User;
use App\Support\IdGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * "Trainers" in the schema/role system == "Project Leader" in the UI.
 * The original app already displayed this label inconsistently (DB/role
 * says "trainer", every visible string says "Project Leader") — kept the
 * same split here rather than renaming the role itself, which would touch
 * auth, whitelist logic, and every other module.
 */
class TrainerController extends Controller
{
    private array $areas = [
        'Mechanical Technology', 'Automotive Technology', 'Computer Technology', 'Electronics Technology',
        'Culinary Technology', 'Apparel and Fashion Technology', 'Print Media Technology', 'Information Technology',
    ];

    public function index(Request $request)
    {
        $q = trim($request->query('q', ''));

        $trainersQuery = User::where('role', 'trainer')
            ->withCount(['trainings as training_count']);

        if ($q) {
            $trainersQuery->where(function ($query) use ($q) {
                $query->where('first_name', 'like', "%{$q}%")
                    ->orWhere('last_name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        $trainers = $trainersQuery->orderBy('last_name')->orderBy('first_name')->get();

        // Generate PL-YYYY-NNN display IDs based on row order per year,
        // same scheme as the original (id_number itself is assigned at
        // registration time from the whitelist entry, this is a display
        // fallback for accounts created before that existed).
        $trainerIds = [];
        $yearGroups = [];
        foreach ($trainers as $tr) {
            $yr = $tr->created_at?->format('Y') ?? now()->format('Y');
            $yearGroups[$yr][] = $tr->id;
        }
        foreach ($yearGroups as $yr => $ids) {
            foreach ($ids as $seq => $id) {
                $trainerIds[$id] = 'PL-' . $yr . '-' . str_pad((string) ($seq + 1), 3, '0', STR_PAD_LEFT);
            }
        }

        $whitelist = TrainerWhitelist::orderBy('last_name')->orderBy('first_name')->get();

        return view('ec.trainers', [
            'activePage' => 'trainers',
            'trainers'   => $trainers,
            'trainerIds' => $trainerIds,
            'whitelist'  => $whitelist,
            'areas'      => $this->areas,
            'q'          => $q,
        ]);
    }

    public function store(Request $request)
    {
        $action = $request->input('action');

        return match ($action) {
            'toggle'          => $this->toggle($request),
            'edit_trainer'    => $this->editTrainer($request),
            'add_whitelist'   => $this->addWhitelist($request),
            'remove_whitelist' => $this->removeWhitelist($request),
            default           => back(),
        };
    }

    private function toggle(Request $request)
    {
        $userId = (int) $request->input('user_id');
        $user = User::where('id', $userId)->where('role', 'trainer')->first();

        if ($user) {
            $user->update(['is_active' => ! $user->is_active]);
        }

        return back()->with('success', 'Project Leader access updated.');
    }

    private function editTrainer(Request $request)
    {
        $data = Validator::make($request->all(), [
            'user_id'    => 'required|integer',
            'first_name' => 'required|string|max:80',
            'last_name'  => 'required|string|max:80',
            'email'      => 'required|email|max:120',
            'position'   => 'required|string',
            'id_number'  => 'nullable|string|max:40',
        ])->validate();

        User::where('id', $data['user_id'])->where('role', 'trainer')->update([
            'first_name' => $data['first_name'],
            'last_name'  => $data['last_name'],
            'email'      => $data['email'],
            'position'   => $data['position'],
            'id_number'  => $data['id_number'] ?? '',
        ]);

        return back()->with('success', 'Project Leader updated.');
    }

    private function addWhitelist(Request $request)
    {
        $data = Validator::make($request->all(), [
            'first_name'     => 'required|string|max:80',
            'last_name'      => 'required|string|max:80',
            'specialization' => 'required|string',
        ])->validate();

        $dup = TrainerWhitelist::whereRaw('LOWER(first_name) = LOWER(?)', [$data['first_name']])
            ->whereRaw('LOWER(last_name) = LOWER(?)', [$data['last_name']])
            ->exists();

        if ($dup) {
            return back()->with('error', 'This Project Leader is already on the approved list.');
        }

        // ID must be unique across both trainer_whitelist AND users
        // (someone may already be registered under this prefix/year).
        $trId = DB::transaction(function () {
            $year = date('Y');
            $like = 'PL-' . $year . '-%';

            $lastWl = TrainerWhitelist::where('id_number', 'like', $like)
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

            return 'PL-' . $year . '-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
        });

        TrainerWhitelist::create([
            'first_name'     => $data['first_name'],
            'last_name'      => $data['last_name'],
            'specialization' => $data['specialization'],
            'id_number'      => $trId,
        ]);

        return back()->with('success', "{$data['first_name']} {$data['last_name']} added to the approved Project Leaders list. Assigned ID: {$trId}");
    }

    private function removeWhitelist(Request $request)
    {
        TrainerWhitelist::where('id', $request->input('whitelist_id'))
            ->where('is_registered', false)
            ->delete();

        return back()->with('success', 'Project Leader removed from approved list.');
    }
}
