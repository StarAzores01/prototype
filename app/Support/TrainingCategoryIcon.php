<?php

namespace App\Support;

/**
 * Single source of truth for the "training area -> icon" lookup that used
 * to be a duplicated $catEmoji array in ec/trainings.blade.php,
 * trainer/trainings.blade.php, beneficiary/trainings.blade.php,
 * beneficiary/home.blade.php, and public/trainings-public.blade.php.
 * Font Awesome equivalents of the original emoji, matched by intent.
 */
class TrainingCategoryIcon
{
    protected static array $map = [
        'Mechanical Technology'          => 'fa-gear',
        'Automotive Technology'          => 'fa-car',
        'Computer Technology'            => 'fa-laptop',
        'Electronics Technology'         => 'fa-plug',
        'Culinary Technology'            => 'fa-utensils',
        'Apparel and Fashion Technology' => 'fa-scissors',
        'Print Media Technology'         => 'fa-print',
        'Information Technology'         => 'fa-desktop',
    ];

    /** Fallback for any area not in the map above (e.g. "General Programs"). */
    protected static string $default = 'fa-book';

    public static function icon(?string $area): string
    {
        return self::$map[$area] ?? self::$default;
    }
}
