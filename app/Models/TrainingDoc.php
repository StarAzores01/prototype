<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingDoc extends Model
{
    protected $table = 'training_docs';
    public $timestamps = false;

    protected $fillable = ['training_id', 'caption', 'file_name', 'file_type', 'uploaded_by'];

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
