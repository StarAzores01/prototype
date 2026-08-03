<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Training extends Model
{
    protected $fillable = [
        'title', 'area', 'description', 'date_start', 'date_end', 'status',
        'trainer_id', 'target_participants', 'budget_allocated', 'budget_used', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date_start'          => 'date',
            'date_end'            => 'date',
            'budget_allocated'    => 'decimal:2',
            'budget_used'         => 'decimal:2',
            'target_participants' => 'integer',
        ];
    }

    public function trainer()
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants()
    {
        return $this->hasMany(Participant::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function trainingDocs()
    {
        return $this->hasMany(TrainingDoc::class);
    }

    public function evaluations()
    {
        return $this->hasMany(Evaluation::class);
    }

    public function attendance()
    {
        return $this->hasMany(Attendance::class);
    }

    public function skillsUtilization()
    {
        return $this->hasMany(SkillsUtilization::class);
    }

    public function evalForms()
    {
        return $this->hasMany(EvalForm::class);
    }

    public function skillsForms()
    {
        return $this->hasMany(SkillsForm::class);
    }

    public function impactAssessmentForms()
    {
        return $this->hasMany(ImpactAssessmentForm::class);
    }
}
