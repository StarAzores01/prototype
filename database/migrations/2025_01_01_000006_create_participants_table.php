<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->string('full_name', 160);
            $table->string('id_number', 40)->nullable();
            $table->foreignId('training_id')->constrained('trainings')->cascadeOnDelete();
            $table->foreignId('beneficiary_id')->nullable()->constrained('beneficiaries')->nullOnDelete();
            $table->string('phone', 30)->nullable();
            $table->string('address', 255)->nullable();
            $table->unsignedTinyInteger('age')->nullable();
            $table->enum('sex', ['Male', 'Female', 'Other'])->nullable();
            $table->string('barangay', 120)->nullable();
            $table->string('municipality', 120)->nullable();
            $table->enum('beneficiary_type', [
                'Out-of-School Youth', 'Displaced Worker', 'Solo Parent', 'Farmer', 'Retiree', 'Other',
            ])->default('Other');
            $table->string('attendance', 20)->nullable()->default('0/0');
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participants');
    }
};
