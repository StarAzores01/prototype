<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('impact_assessments', function (Blueprint $table) {
            // Set by the evaluator when submitting; carried onto the
            // participant survey. participant_form_id links the submission to
            // the survey that was created from it (automatically for built
            // forms, by the EC for uploaded files).
            $table->date('due_date')->nullable()->after('description');
            $table->unsignedBigInteger('participant_form_id')->nullable()->after('due_date');
        });

        Schema::table('impact_assessment_forms', function (Blueprint $table) {
            $table->date('due_date')->nullable()->after('sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('impact_assessment_forms', function (Blueprint $table) {
            $table->dropColumn('due_date');
        });
        Schema::table('impact_assessments', function (Blueprint $table) {
            $table->dropColumn(['due_date', 'participant_form_id']);
        });
    }
};
