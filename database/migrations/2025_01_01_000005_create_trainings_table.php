<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainings', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->string('area', 120);
            $table->text('description')->nullable();
            $table->date('date_start')->nullable();
            $table->date('date_end')->nullable();
            $table->enum('status', ['Proposed', 'Approved', 'Ongoing', 'Completed'])->default('Proposed');
            $table->foreignId('trainer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('target_participants')->default(0);
            $table->decimal('budget_allocated', 12, 2)->nullable();
            $table->decimal('budget_used', 12, 2)->default(0);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainings');
    }
};
