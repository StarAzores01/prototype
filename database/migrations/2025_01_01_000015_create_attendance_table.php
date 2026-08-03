<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_id')->constrained('trainings')->cascadeOnDelete();
            $table->foreignId('participant_id')->constrained('participants')->cascadeOnDelete();
            $table->date('session_date');
            $table->enum('status', ['Present', 'Absent', 'Late'])->default('Absent');
            $table->time('time_in')->nullable();
            $table->time('time_out')->nullable();
            $table->foreignId('recorded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['training_id', 'participant_id', 'session_date'], 'uq_attendance');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance');
    }
};
