<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainerWhitelist extends Model
{
    protected $table = 'trainer_whitelist';
    public $timestamps = false;

    protected $fillable = [
        'first_name', 'last_name', 'specialization', 'id_number', 'is_registered',
    ];

    protected function casts(): array
    {
        return ['is_registered' => 'boolean', 'created_at' => 'datetime'];
    }
}
