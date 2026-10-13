<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Evaluator forms submitted before they were sent automatically never
     * reached the participants. Create the participant survey for each one
     * that has an activity, so enrolled beneficiaries can see it.
     */
    public function up(): void
    {
        $rating = ['5 - Excellent', '4 - Very Good', '3 - Good', '2 - Fair', '1 - Poor'];

        $rows = DB::table('impact_assessments')
            ->whereNotNull('form_data')
            ->whereNull('participant_form_id')
            ->whereNotNull('training_id')
            ->get();

        foreach ($rows as $row) {
            $questions = json_decode($row->form_data, true);
            if (! is_array($questions) || ! $questions) {
                continue;
            }

            $fields = array_map(function ($q) use ($rating) {
                $isRating = ($q['type'] ?? '') === 'rating';

                return [
                    'label'    => $q['label'] ?? '',
                    'type'     => $isRating ? 'radio' : ($q['type'] ?? 'text'),
                    'required' => ! empty($q['required']),
                    'options'  => $isRating ? $rating : ($q['options'] ?? []),
                ];
            }, $questions);

            $formId = DB::table('impact_assessment_forms')->insertGetId([
                'training_id' => $row->training_id,
                'title'       => $row->title,
                'fields'      => json_encode($fields),
                'created_by'  => $row->evaluator_id,
                'sent_at'     => now(),
                'due_date'    => $row->due_date,
                'created_at'  => now(),
            ]);

            DB::table('impact_assessments')->where('id', $row->id)->update(['participant_form_id' => $formId]);

            $beneficiaryIds = DB::table('participants')
                ->where('training_id', $row->training_id)
                ->whereNotNull('beneficiary_id')
                ->distinct()
                ->pluck('beneficiary_id');

            foreach ($beneficiaryIds as $beneficiaryId) {
                DB::table('notifications')->insert([
                    'user_id'     => $beneficiaryId,
                    'role'        => 'beneficiary',
                    'training_id' => $row->training_id,
                    'message'     => 'A new Impact Assessment survey has been sent to you.',
                    'link'        => '/beneficiary/impact-assessment',
                    'created_at'  => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Not reversible: the surveys may already have responses.
    }
};
