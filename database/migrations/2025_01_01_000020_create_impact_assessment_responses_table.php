<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('impact_assessment_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('impact_assessment_forms')->cascadeOnDelete();
            $table->foreignId('training_id')->constrained('trainings')->cascadeOnDelete();
            $table->foreignId('beneficiary_id')->constrained('beneficiaries')->cascadeOnDelete();
            $table->json('responses');
            $table->timestamp('submitted_at')->useCurrent();
            $table->unique(['form_id', 'beneficiary_id'], 'uq_ia_resp');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('impact_assessment_responses');
    }
};
