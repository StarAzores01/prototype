<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds "featured on public site" draft/publish staging directly to
 * `trainings` (Activities). Under Manage Public Site Content → Trainings,
 * the EC picks a real Training (existing or newly created) to feature and
 * uploads a photo for it — is_featured/featured_image are the EC-editable
 * working draft, always shown in the admin UI; published_* is what's
 * actually live on the public site (landing page's Courses Offered grid
 * and the public Trainings page's Training Programs grid), copied across
 * only by Ec\PublishController@publishAll via Training::publishFeature().
 * See App\Models\Training::scopeFeaturedOnPublicSite().
 *
 * Nothing is backfilled: all four columns start false/null for every
 * existing row. Nothing was ever explicitly "featured" before this —
 * the old public pages sourced their photos from the separate, hand-typed
 * TrainingCategory table instead of real Training rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trainings', function (Blueprint $table) {
            $table->boolean('is_featured')->default(false)->after('cover_image');
            $table->string('featured_image')->nullable()->after('is_featured');
            $table->boolean('published_is_featured')->default(false)->after('featured_image');
            $table->string('published_featured_image')->nullable()->after('published_is_featured');
        });
    }

    public function down(): void
    {
        Schema::table('trainings', function (Blueprint $table) {
            $table->dropColumn([
                'is_featured',
                'featured_image',
                'published_is_featured',
                'published_featured_image',
            ]);
        });
    }
};
