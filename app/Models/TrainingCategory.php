<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A public-site training category (e.g. "Culinary Technology"), managed by
 * the EC under Manage Public Site Content → Trainings. Feeds the landing
 * page's Courses Offered cards and the public Trainings page's category
 * photos — see resources/views/public/{landing,trainings-public}.blade.php.
 */
class TrainingCategory extends Model
{
    protected $fillable = [
        'activity_name', 'project_name', 'description', 'image', 'status',
    ];

    /** Only categories EC has marked Active are shown on the public site. */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * The real Program whose area matches this category, if any — always
     * looked up live against Program.area rather than stored, so the
     * "Program Name" column on the Trainings admin table automatically
     * reflects real Program data instead of a manually-entered value that
     * could drift out of sync with what Programs actually exist.
     */
    public function programName(): string
    {
        return Program::where('area', $this->activity_name)
            ->latest('created_at')
            ->value('title') ?? '—';
    }
}
