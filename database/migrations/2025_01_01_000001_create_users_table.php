<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username', 60)->unique();
            $table->string('first_name', 80);
            $table->string('last_name', 80);
            $table->string('email', 120)->unique();
            $table->string('id_number', 40)->unique();
            $table->enum('position', ['Professor', 'Assistant Professor', 'Instructor']);
            $table->enum('role', ['extension_coordinator', 'trainer', 'evaluator'])->default('trainer');
            // Kept as password_hash (not Laravel's default "password") to match the
            // original schema. User model overrides getAuthPassword() accordingly.
            $table->string('password_hash', 255);
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamp('last_login')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
