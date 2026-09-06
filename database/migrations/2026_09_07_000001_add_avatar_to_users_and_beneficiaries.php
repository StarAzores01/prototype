<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar', 120)->nullable()->after('department');
        });

        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->string('avatar', 120)->nullable()->after('municipality');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('avatar');
        });

        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->dropColumn('avatar');
        });
    }
};
