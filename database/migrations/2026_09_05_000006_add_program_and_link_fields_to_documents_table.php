<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A document can now be a link (Google Drive/YouTube/external) instead of an
 * uploaded file, and can belong to a program and/or an activity (trainings
 * row — reusing that table rather than a separate "activities" table).
 * file_name/file_type/file_size become nullable since a link-only document
 * has none of them; the "at least one of file_name or link_url" rule is
 * enforced in the Document model (see Document::boot()), not a DB check
 * constraint, per the same reasoning as the pivot tables' business rules.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('program_id')->nullable()->after('id')
                ->constrained('programs')->nullOnDelete();
            $table->foreignId('activity_id')->nullable()->after('program_id')
                ->constrained('trainings')->nullOnDelete();
            $table->string('link_url')->nullable()->after('file_size');
            $table->enum('link_type', ['gdrive', 'youtube', 'external'])->nullable()->after('link_url');

            $table->string('file_name', 255)->nullable()->change();
            $table->string('file_type', 20)->nullable()->change();
            $table->integer('file_size')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('program_id');
            $table->dropConstrainedForeignId('activity_id');
            $table->dropColumn(['link_url', 'link_type']);

            $table->string('file_name', 255)->nullable(false)->change();
            $table->string('file_type', 20)->nullable(false)->change();
            $table->integer('file_size')->default(0)->nullable(false)->change();
        });
    }
};
