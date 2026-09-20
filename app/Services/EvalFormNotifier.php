<?php

namespace App\Services;

use App\Models\EvalForm;
use App\Models\Notification;
use App\Models\Participant;

/**
 * Centralised "send" path for Evaluation Forms (App\Models\EvalForm).
 *
 * "Sending" a form means: stamping sent_at (so it becomes visible on the
 * beneficiary's Evaluations page — see Beneficiary\EvaluationController,
 * which gates visibility on whereNotNull('sent_at')) and creating a
 * Notification row for the activity's Project Leader and every enrolled
 * beneficiary.
 *
 * Two call sites need this exact behaviour, so it lives here instead of
 * being duplicated:
 *  - App\Console\Commands\SendScheduledEvalForms — the daily 08:00 sweep
 *    that catches forms whose send_date has arrived.
 *  - App\Http\Controllers\Ec\EvaluationController::saveForm() — sends a
 *    form immediately, at creation/edit time, when the EC schedules it
 *    for today (or an already-past date), instead of making beneficiaries
 *    wait for the next scheduler run.
 */
class EvalFormNotifier
{
    /**
     * Send the given form now: stamp sent_at and notify the trainer +
     * every enrolled beneficiary. No-op if it was already sent.
     *
     * Returns the number of beneficiaries notified (0 if already sent or
     * the form has no training).
     */
    public static function sendNow(EvalForm $form): int
    {
        if ($form->sent_at !== null) {
            return 0;
        }

        $training = $form->training;
        if (! $training) {
            return 0;
        }

        $form->update(['sent_at' => now()]);

        $message = "An evaluation form has been sent for activity: {$training->title}";

        // Notify the activity's Project Leader (trainer_id).
        if ($training->trainer_id) {
            Notification::create([
                'user_id'     => $training->trainer_id,
                'role'        => 'trainer',
                'training_id' => $training->id,
                'message'     => $message,
                'link'        => route('trainer.evaluations'),
            ]);
        }

        // Notify every enrolled beneficiary — this is what makes the form
        // appear on their Evaluations page.
        $beneficiaryIds = Participant::where('training_id', $training->id)
            ->whereNotNull('beneficiary_id')
            ->distinct()
            ->pluck('beneficiary_id');

        foreach ($beneficiaryIds as $beneficiaryId) {
            Notification::create([
                'user_id'     => $beneficiaryId,
                'role'        => 'beneficiary',
                'training_id' => $training->id,
                'message'     => $message,
                'link'        => route('beneficiary.evaluations'),
            ]);
        }

        return $beneficiaryIds->count();
    }
}
