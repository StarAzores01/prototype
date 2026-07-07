<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('participants', function (Blueprint $table) {
            $table->enum('status', ['enrolled', 'completed', 'dropped'])
                ->default('enrolled')
                ->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('participants', function (Blueprint $table) {
            $table->enum('status', ['registered', 'active', 'completed', 'dropped'])
                ->default('registered')
                ->after('user_id');
        });
    }
};
