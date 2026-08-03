<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skills_utilization', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_id')->constrained('trainings')->cascadeOnDelete();
            $table->decimal('personal_use_pct', 5, 2)->default(0);
            $table->decimal('income_gen_pct', 5, 2)->default(0);
            $table->decimal('employment_pct', 5, 2)->default(0);
            $table->decimal('nc2_cert_pct', 5, 2)->default(0);
            $table->timestamp('recorded_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skills_utilization');
    }
};
