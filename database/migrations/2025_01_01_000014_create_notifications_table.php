<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            // Nullable, unconstrained: can target a specific user across the
            // users/beneficiaries tables, so no single FK is used here.
            $table->unsignedBigInteger('user_id')->nullable();
            $table->enum('role', ['trainer', 'beneficiary', 'evaluator', 'all'])->default('all');
            $table->foreignId('training_id')->nullable()->constrained('trainings')->nullOnDelete();
            $table->text('message');
            $table->string('link', 255)->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
