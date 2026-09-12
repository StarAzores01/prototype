<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * timeline_start/timeline_end are permanently fixed at creation (see
 * Program::booted()) — the only way a program's effective end date can ever
 * move afterward is an EC "Extend Timeline" action setting this column.
 * Program::effective_end_date (extended_end_date ?? timeline_end) is what
 * every display of "the program's end date" should read from now on; the
 * original timeline_end stays untouched for documentation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->date('extended_end_date')->nullable()->after('timeline_end');
        });
    }

    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->dropColumn('extended_end_date');
        });
    }
};
