<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImpactAssessmentResponse extends Model
{
    protected $table = 'impact_assessment_responses';
    public $timestamps = false;

    protected $fillable = ['form_id', 'training_id', 'beneficiary_id', 'responses'];

    protected function casts(): array
    {
        return ['responses' => 'array', 'submitted_at' => 'datetime'];
    }

    public function form()
    {
        return $this->belongsTo(ImpactAssessmentForm::class, 'form_id');
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
