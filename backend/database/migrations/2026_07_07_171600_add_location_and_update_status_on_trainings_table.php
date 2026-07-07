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
        Schema::table('trainings', function (Blueprint $table) {
            $table->string('location')->nullable()->after('description');
        });

        Schema::table('trainings', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('trainings', function (Blueprint $table) {
            $table->enum('status', ['draft', 'scheduled', 'ongoing', 'completed', 'cancelled'])
                ->default('draft')
                ->after('end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trainings', function (Blueprint $table) {
            $table->dropColumn(['location', 'status']);
        });

        Schema::table('trainings', function (Blueprint $table) {
            $table->enum('status', ['proposed', 'ongoing', 'completed', 'cancelled'])
                ->default('proposed')
                ->after('end_date');
        });
    }
};
