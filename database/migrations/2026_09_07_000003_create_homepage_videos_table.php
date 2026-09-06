<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A single-row table holding the public homepage's video montage — an
 * external link (YouTube, Google Drive, Vimeo, etc.), same convention as
 * the per-program video montage (see 2026_09_07_000002). Deliberately not
 * scoped to any program: this is the one site-wide video shown on the
 * public landing page, managed by the Extension Coordinator from the
 * bottom of their Dashboard.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homepage_videos', function (Blueprint $table) {
            $table->id();
            $table->text('video_url')->nullable();
            $table->string('video_title', 255)->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homepage_videos');
    }
};
