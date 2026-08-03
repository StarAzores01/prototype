<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkillsUtilization extends Model
{
    protected $table = 'skills_utilization';
    public $timestamps = false;

    protected $fillable = [
        'training_id', 'personal_use_pct', 'income_gen_pct', 'employment_pct', 'nc2_cert_pct',
    ];

    protected function casts(): array
    {
        return ['recorded_at' => 'datetime'];
    }

    public function training()
    {
        return $this->belongsTo(Training::class);
    }
}
