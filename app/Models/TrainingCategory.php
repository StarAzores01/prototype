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
        'published_activity_name', 'published_project_name', 'published_description',
        'published_image', 'published_status',
    ];

    /**
     * activity_name/project_name/description/image/status are the
     * EC-editable "working draft" — always what the admin UI shows and
     * saves. published_* holds what's actually live on the public site
     * and only changes when Ec\PublishController@publishAll copies the
     * draft columns across. See the
     * add_published_snapshot_to_training_categories_table migration.
     */

    /** Only categories EC has PUBLISHED as Active are shown on the public site. */
    public function scopeActive($query)
    {
        return $query->where('published_status', 'active');
    }

    /** Whether this category's draft differs from what's currently live. */
    public function hasUnpublishedChanges(): bool
    {
        return (string) $this->activity_name !== (string) $this->published_activity_name
            || (string) $this->project_name !== (string) $this->published_project_name
            || (string) $this->description !== (string) $this->published_description
            || (string) $this->image !== (string) $this->published_image
            || (string) $this->status !== (string) $this->published_status;
    }

    /** Copies the working draft columns into their published_* counterparts. */
    public function publish(): void
    {
        $this->published_activity_name = $this->activity_name;
        $this->published_project_name  = $this->project_name;
        $this->published_description   = $this->description;
        $this->published_image         = $this->image;
        $this->published_status        = $this->status;
        $this->save();
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
