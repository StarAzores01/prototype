<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cover/display picture per Activity (Training) — separate from the
 * document repository. Nullable: falls back to the existing gradient+icon
 * placeholder already used for activity cards when no cover is set.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trainings', function (Blueprint $table) {
            $table->string('cover_image')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('trainings', function (Blueprint $table) {
            $table->dropColumn('cover_image');
        });
    }
};
