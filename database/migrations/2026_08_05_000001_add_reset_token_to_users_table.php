<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Supports ecrecovery.php's forgot-password flow (EC accounts only, matching
 * the original). Added as a new migration per project convention rather than
 * editing the already-run users-table migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('reset_token', 64)->nullable()->after('password_hash');
            $table->timestamp('reset_expires')->nullable()->after('reset_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['reset_token', 'reset_expires']);
        });
    }
};
