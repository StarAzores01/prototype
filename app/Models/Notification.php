<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'role', 'training_id', 'message', 'link', 'is_read'];

    protected function casts(): array
    {
        return ['is_read' => 'boolean', 'created_at' => 'datetime'];
    }

    public function training()
    {
        return $this->belongsTo(Training::class);
    }
}
