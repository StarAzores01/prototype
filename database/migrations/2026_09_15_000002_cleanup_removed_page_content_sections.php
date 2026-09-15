<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Drops now-orphaned rows for sections removed from
 * config/page_content_sections.php in this same change:
 *   - about.system_badge/system_title/system_body — the "What is
 *     PAThrive?" section, removed from the About page entirely.
 *   - landing.hero_heading — replaced by the split
 *     hero_heading_text/hero_heading_highlight fields.
 * Harmless no-ops if EC never customized these (the common case — see
 * PageContent::get()'s $default fallback), but keeps page_contents free of
 * rows no view or edit form will ever read again.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('page_contents')
            ->where('page_key', 'about')
            ->whereIn('section_key', ['system_badge', 'system_title', 'system_body'])
            ->delete();

        DB::table('page_contents')
            ->where('page_key', 'landing')
            ->where('section_key', 'hero_heading')
            ->delete();
    }

    public function down(): void
    {
        // Deliberately irreversible — the deleted rows carried no
        // meaningful data to restore (see class docblock), and the
        // sections themselves no longer exist in config to restore into.
    }
};
