<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvaluatorWhitelist extends Model
{
    protected $table = 'evaluator_whitelist';
    public $timestamps = false;

    protected $fillable = [
        'first_name', 'last_name', 'department', 'id_number', 'is_registered',
    ];

    protected function casts(): array
    {
        return ['is_registered' => 'boolean', 'created_at' => 'datetime'];
    }
}
