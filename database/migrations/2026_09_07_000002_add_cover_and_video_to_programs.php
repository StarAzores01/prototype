<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->string('cover_image', 120)->nullable()->after('created_by');
            $table->text('video_url')->nullable()->after('cover_image');
            $table->string('video_title', 255)->nullable()->after('video_url');
        });
    }

    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->dropColumn(['cover_image', 'video_url', 'video_title']);
        });
    }
};
