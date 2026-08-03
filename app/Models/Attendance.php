<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'training_id', 'participant_id', 'session_date', 'status',
        'time_in', 'time_out', 'recorded_by',
    ];

    protected function casts(): array
    {
        return ['session_date' => 'date'];
    }

    public function training()
    {
        return $this->belongsTo(Training::class);
    }

    public function participant()
    {
        return $this->belongsTo(Participant::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
