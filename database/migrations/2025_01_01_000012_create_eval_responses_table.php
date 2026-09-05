<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eval_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('eval_forms')->cascadeOnDelete();
            $table->foreignId('training_id')->constrained('trainings')->cascadeOnDelete();
            $table->foreignId('beneficiary_id')->constrained('beneficiaries')->cascadeOnDelete();
            $table->json('responses');
            $table->timestamps();
            $table->unique(['form_id', 'beneficiary_id'], 'uq_eval_resp');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eval_responses');
    }
};
