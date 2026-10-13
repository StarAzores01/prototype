<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('impact_assessments', function (Blueprint $table) {
            // An evaluator can now build a form inside "Submit Assessment"
            // instead of uploading a file. Its questions and the evaluator's
            // answers are stored together as JSON:
            // [{label, type, options[], answer}, ...]. Null for file uploads.
            $table->json('form_data')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('impact_assessments', function (Blueprint $table) {
            $table->dropColumn('form_data');
        });
    }
};
