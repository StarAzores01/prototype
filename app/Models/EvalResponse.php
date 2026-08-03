<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvalResponse extends Model
{
    protected $table = 'eval_responses';

    const CREATED_AT = 'submitted_at';

    protected $fillable = ['form_id', 'training_id', 'beneficiary_id', 'responses'];

    protected function casts(): array
    {
        return ['responses' => 'array', 'submitted_at' => 'datetime'];
    }

    public function form()
    {
        return $this->belongsTo(EvalForm::class, 'form_id');
    }

    public function training()
    {
        return $this->belongsTo(Training::class);
    }

    public function beneficiary()
    {
        return $this->belongsTo(Beneficiary::class);
    }
}
