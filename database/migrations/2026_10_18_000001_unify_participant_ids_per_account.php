<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * "Add Existing Account" used to give every extra enrollment a brand-new
 * BF user ID. A person keeps the User ID from their first registration, so
 * set every enrollment of an account to the ID of its oldest enrollment.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            UPDATE participants p
            SET id_number = f.id_number
            FROM (
                SELECT DISTINCT ON (beneficiary_id) beneficiary_id, id_number
                FROM participants
                WHERE beneficiary_id IS NOT NULL
                  AND id_number IS NOT NULL AND id_number <> ''
                ORDER BY beneficiary_id, id
            ) f
            WHERE p.beneficiary_id = f.beneficiary_id
              AND p.id_number IS DISTINCT FROM f.id_number
        SQL);
    }

    public function down(): void
    {
        // The previous per-enrollment IDs are not recoverable; nothing to undo.
    }
};
