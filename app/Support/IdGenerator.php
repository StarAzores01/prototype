<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Generates sequential system IDs in the form PREFIX-YYYY-0001.
 * Replaces the original MySQL "LOCK TABLES" approach with a Postgres-safe
 * row lock (SELECT ... FOR UPDATE inside a transaction) so concurrent
 * requests never collide on the same sequence number.
 */
class IdGenerator
{
    public static function next(string $prefix, string $table, string $column = 'id_number'): string
    {
        $year = date('Y');
        $like = $prefix . '-' . $year . '-%';

        return DB::transaction(function () use ($prefix, $table, $column, $year, $like) {
            $last = DB::table($table)
                ->where($column, 'like', $like)
                ->orderByDesc($column)
                ->lockForUpdate()
                ->value($column);

            $seq = 1;
            if ($last) {
                $parts = explode('-', $last);
                $seq = ((int) end($parts)) + 1;
            }

            return $prefix . '-' . $year . '-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
        });
    }
}
