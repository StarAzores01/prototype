<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImpactAssessment extends Model
{
    protected $fillable = [
        'evaluator_id', 'training_id', 'title', 'description', 'file_name', 'original_name',
        'file_type', 'file_size', 'status', 'submitted_at', 'reviewed_at', 'ec_notes',
    ];

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime', 'reviewed_at' => 'datetime'];
    }

    public function evaluator()
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function training()
    {
        return $this->belongsTo(Training::class);
    }
}
