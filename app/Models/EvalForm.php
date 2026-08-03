<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvalForm extends Model
{
    protected $table = 'eval_forms';
    public $timestamps = false;

    protected $fillable = ['training_id', 'title', 'fields', 'created_by', 'sent_at'];

    protected function casts(): array
    {
        return ['fields' => 'array', 'sent_at' => 'datetime', 'created_at' => 'datetime'];
    }

    public function training()
    {
        return $this->belongsTo(Training::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function responses()
    {
        return $this->hasMany(EvalResponse::class, 'form_id');
    }
}
