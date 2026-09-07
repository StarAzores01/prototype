<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * EC-editable text/media content for the public-facing pages (landing,
 * about, contact, trainings-public, choose-role, privacy, terms) plus a
 * 'global' pseudo-page for content repeated across every page's footer
 * (site logo, tagline, contact details) — see App\Models\PageContent and
 * Ec\PageContentController.
 *
 * One row per (page_key, section_key) — a page section only gets a row
 * once EC actually edits it; until then PageContent::get()'s fallback
 * argument covers it, so every public page renders correctly out of the
 * box with zero rows in this table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_contents', function (Blueprint $table) {
            $table->id();
            $table->string('page_key', 60);
            $table->string('section_key', 100);
            $table->enum('content_type', ['text', 'image', 'video_link'])->default('text');
            $table->text('content_value')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['page_key', 'section_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_contents');
    }
};
