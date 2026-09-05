<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evaluation extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'training_id', 'participant_id', 'rating', 'feedback', 'submitted_at', 'status',
    ];

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime'];
    }

    public function training()
    {
        return $this->belongsTo(Training::class);
    }

    public function participant()
    {
        return $this->belongsTo(Participant::class);
    }
}
