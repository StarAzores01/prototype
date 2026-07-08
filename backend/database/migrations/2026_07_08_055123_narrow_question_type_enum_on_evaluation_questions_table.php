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
        Schema::table('evaluation_questions', function (Blueprint $table) {
            $table->dropColumn('question_type');
        });

        Schema::table('evaluation_questions', function (Blueprint $table) {
            $table->enum('question_type', ['rating', 'text', 'yes_no'])
                ->default('text')
                ->after('question_text');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evaluation_questions', function (Blueprint $table) {
            $table->dropColumn('question_type');
        });

        Schema::table('evaluation_questions', function (Blueprint $table) {
            $table->enum('question_type', ['text', 'rating', 'multiple_choice', 'yes_no'])
                ->default('text')
                ->after('question_text');
        });
    }
};
