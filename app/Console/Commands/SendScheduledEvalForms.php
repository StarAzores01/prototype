<?php

namespace App\Console\Commands;

use App\Models\EvalForm;
use App\Services\EvalFormNotifier;
use Illuminate\Console\Command;

class SendScheduledEvalForms extends Command
{
    protected $signature   = 'eval:send-scheduled';
    protected $description = 'Auto-send evaluation forms whose send_date is today.';

    public function handle(): int
    {
        $today = now()->format('Y-m-d');

        $forms = EvalForm::with('training')
            ->whereDate('send_date', $today)
            ->whereNull('sent_at')
            ->get();

        if ($forms->isEmpty()) {
            $this->info('No evaluation forms scheduled for today.');
            return self::SUCCESS;
        }

        foreach ($forms as $form) {
            if (! $form->training) {
                $this->warn("Form #{$form->id} has no training — skipped.");
                continue;
            }

            $notified = EvalFormNotifier::sendNow($form);

            $this->info("Sent form #{$form->id} for activity \"{$form->training->title}\" ({$notified} beneficiaries notified).");
        }

        return self::SUCCESS;
    }
}
