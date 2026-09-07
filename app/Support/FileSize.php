<?php

namespace App\Support;

/**
 * Human-readable byte formatting — first introduced for the Document
 * Analytics dashboard's "total storage used" stat (documents.file_size is
 * stored as a raw byte integer; nothing in the app formatted it for
 * display before this).
 */
class FileSize
{
    public static function human(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);
        $value = $bytes / (1024 ** $power);

        return ($power === 0 ? (string) $bytes : number_format($value, $value < 10 ? 2 : 1)) . ' ' . $units[$power];
    }
}
