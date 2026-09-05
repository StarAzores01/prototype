<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit trail for EC unlocking a locked program and changing its budget or
 * timeline. Immutable log — created_at only, no updated_at (matches the
 * documents table's pattern for the same reason).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_amendments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('programs')->cascadeOnDelete();
            $table->enum('field_changed', ['budget', 'timeline']);
            $table->string('old_value');
            $table->string('new_value');
            $table->text('remark');
            $table->foreignId('amended_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_amendments');
    }
};
