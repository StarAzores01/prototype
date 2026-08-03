<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('impact_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluator_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('training_id')->nullable()->constrained('trainings')->nullOnDelete();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('file_name', 255)->nullable();
            $table->string('original_name', 255)->nullable();
            $table->string('file_type', 20)->nullable();
            $table->integer('file_size')->nullable()->default(0);
            $table->enum('status', ['Draft', 'Submitted', 'Reviewed'])->default('Submitted');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('ec_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('impact_assessments');
    }
};
