<?php

namespace App\Http\Controllers\Ec\Concerns;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Validator as ValidatorContract;

/**
 * Shared by ProgramController and TrainingController — both assign a team
 * via a pivot table (program_team_members / activity_team_members) using
 * the exact same "exactly 1 lead, up to 3 members, all trainers, no
 * overlap" rule.
 */
trait ValidatesTeamRoles
{
    /**
     * The lead/member selects always render 3 member slots; unused ones
     * submit "" — strip those before validating so "integer|exists" doesn't
     * choke on them.
     */
    private function scrubMemberIds(Request $request): void
    {
        $request->merge([
            'member_ids' => array_values(array_filter(
                (array) $request->input('member_ids', []),
                fn ($v) => $v !== null && $v !== ''
            )),
        ]);
    }

    /**
     * Validates the "exactly 1 lead, up to 3 members, all trainers, no
     * overlap" rule server-side — the actual enforcement point, not just the
     * form JS. Runs as a Validator::after() hook so it can add field-specific
     * errors the blade form already knows how to display.
     */
    private function validateTeamRoles(ValidatorContract $validator, int $leadId, array $memberIds): void
    {
        $memberIds = array_map('intval', $memberIds);

        if ($leadId && in_array($leadId, $memberIds, true)) {
            $validator->errors()->add('member_ids', 'The Project Lead cannot also be listed as a team member.');
        }

        $ids = array_filter(array_unique([$leadId, ...$memberIds]));
        if ($ids) {
            $trainerIds = User::where('role', 'trainer')->whereIn('id', $ids)->pluck('id')->all();

            if ($leadId && ! in_array($leadId, $trainerIds, true)) {
                $validator->errors()->add('lead_id', 'The Project Lead must be a trainer.');
            }
            foreach ($memberIds as $memberId) {
                if (! in_array($memberId, $trainerIds, true)) {
                    $validator->errors()->add('member_ids', 'Every team member must be a trainer.');
                    break;
                }
            }
        }
    }
}
