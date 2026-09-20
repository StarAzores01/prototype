<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds real draft/publish staging to `training_categories` (Trainings tab
 * under Manage Public Site Content). The existing columns
 * (activity_name/project_name/description/image/status) become the
 * EC-editable "working draft" — always shown/edited in the admin UI —
 * while the new `published_*` columns hold what's actually live on the
 * public site (landing page's Courses Offered cards, public Trainings
 * page). Nothing goes live until Ec\PublishController@publishAll copies
 * the working columns into their published_* counterparts for every
 * category where they differ.
 *
 * Backfilled from the current live columns so every category already
 * visible on the public site stays visible immediately after this
 * migration runs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_categories', function (Blueprint $table) {
            $table->string('published_activity_name', 100)->nullable()->after('status');
            $table->string('published_project_name', 150)->nullable()->after('published_activity_name');
            $table->text('published_description')->nullable()->after('published_project_name');
            $table->string('published_image')->nullable()->after('published_description');
            $table->enum('published_status', ['active', 'draft'])->nullable()->after('published_image');
        });

        DB::table('training_categories')->update([
            'published_activity_name' => DB::raw('activity_name'),
            'published_project_name'  => DB::raw('project_name'),
            'published_description'   => DB::raw('description'),
            'published_image'         => DB::raw('image'),
            'published_status'        => DB::raw('status'),
        ]);
    }

    public function down(): void
    {
        Schema::table('training_categories', function (Blueprint $table) {
            $table->dropColumn([
                'published_activity_name',
                'published_project_name',
                'published_description',
                'published_image',
                'published_status',
            ]);
        });
    }
};
