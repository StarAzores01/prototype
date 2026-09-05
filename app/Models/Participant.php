<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Participant extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'full_name', 'id_number', 'training_id', 'beneficiary_id', 'phone', 'address',
        'age', 'sex', 'barangay', 'municipality', 'beneficiary_type', 'attendance', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'created_at' => 'datetime'];
    }

    public function training()
    {
        return $this->belongsTo(Training::class);
    }

    public function beneficiary()
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function evaluations()
    {
        return $this->hasMany(Evaluation::class);
    }

    public function attendanceRecords()
    {
        return $this->hasMany(Attendance::class);
    }
}
