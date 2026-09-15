<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkillProgressEntry extends Model
{
    const OUTCOME_TYPES = [
        'Income Generating',
        'Employment / Work',
        'Community Service',
        'Personal Development',
        'Training Application',
        'Other',
    ];

    protected $fillable = [
        'beneficiary_id',
        'training_id',
        'activity_name',
        'description',
        'activity_date',
        'outcome_type',
        'service_fee',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'activity_date' => 'date',
            'service_fee'   => 'decimal:2',
        ];
    }

    public function beneficiary()
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function training()
    {
        return $this->belongsTo(Training::class);
    }
}
