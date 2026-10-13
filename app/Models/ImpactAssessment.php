<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImpactAssessment extends Model
{
    protected $fillable = [
        'evaluator_id', 'training_id', 'title', 'description', 'form_data', 'due_date', 'participant_form_id', 'file_name', 'original_name',
        'file_type', 'file_size', 'status', 'submitted_at', 'reviewed_at', 'ec_notes',
    ];

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime', 'reviewed_at' => 'datetime', 'form_data' => 'array', 'due_date' => 'date'];
    }

    public function evaluator()
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function participantForm()
    {
        return $this->belongsTo(ImpactAssessmentForm::class, 'participant_form_id');
    }

    public function training()
    {
        return $this->belongsTo(Training::class);
    }
}
