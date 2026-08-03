<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'file_name', 'original_name', 'file_type', 'file_size',
        'training_id', 'uploaded_by', 'visibility',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function training()
    {
        return $this->belongsTo(Training::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
