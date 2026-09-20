<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds real draft/publish staging to `page_contents` (Pages + Site
 * Settings tabs under Manage Public Site Content). `content_value`
 * becomes the EC-editable "working draft" — what the admin edit forms
 * always show/save — while the new `published_value` column holds what
 * the public site actually renders, via App\Models\PageContent::get().
 * Nothing goes live until Ec\PublishController@publishAll copies
 * content_value into published_value for every row where they differ.
 *
 * Backfilled from the current content_value so every value already saved
 * by EC stays live immediately after this migration runs — no existing
 * public content disappears or reverts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('page_contents', function (Blueprint $table) {
            $table->text('published_value')->nullable()->after('content_value');
        });

        DB::table('page_contents')->update([
            'published_value' => DB::raw('content_value'),
        ]);
    }

    public function down(): void
    {
        Schema::table('page_contents', function (Blueprint $table) {
            $table->dropColumn('published_value');
        });
    }
};
