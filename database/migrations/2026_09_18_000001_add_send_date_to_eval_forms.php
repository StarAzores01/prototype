<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eval_forms', function (Blueprint $table) {
            // The date the form should be automatically sent to beneficiaries.
            // Constrained to the parent activity's date_start–date_end range
            // at the application level (not a DB constraint, since date_end
            // is on a different table). Nullable so existing rows aren't broken.
            $table->date('send_date')->nullable()->after('sent_at');

            // Allow multiple forms per training (one per send_date).
            // The old code enforced one-form-per-training in PHP; that
            // constraint is dropped here so users can schedule several forms
            // across different days of the same activity.
            $table->unique(['training_id', 'send_date'], 'eval_forms_training_send_date_unique');
        });
    }

    public function down(): void
    {
        Schema::table('eval_forms', function (Blueprint $table) {
            $table->dropUnique('eval_forms_training_send_date_unique');
            $table->dropColumn('send_date');
        });
    }
};
