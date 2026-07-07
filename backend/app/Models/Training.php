<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Training extends Model
{
    protected $fillable = [
        'title',
        'description',
        'location',
        'start_date',
        'end_date',
        'status',
        'created_by',
        'project_leader_id',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function projectLeader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'project_leader_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function evaluationForms(): HasMany
    {
        return $this->hasMany(EvaluationForm::class);
    }

    public function impactAssessments(): HasMany
    {
        return $this->hasMany(ImpactAssessment::class);
    }
}
