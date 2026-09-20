<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Beneficiary extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'username', 'first_name', 'last_name', 'email', 'phone', 'address',
        'age', 'sex', 'barangay', 'municipality', 'password_hash', 'is_active', 'avatar',
    ];

    protected $hidden = ['password_hash', 'remember_token'];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'created_at' => 'datetime'];
    }

    /** Used by Laravel's rehash-on-login; the default ('password') does not exist here. */
    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function participants()
    {
        return $this->hasMany(Participant::class);
    }

    public function evalResponses()
    {
        return $this->hasMany(EvalResponse::class);
    }

    public function skillsResponses()
    {
        return $this->hasMany(SkillsResponse::class);
    }

    public function impactAssessmentResponses()
    {
        return $this->hasMany(ImpactAssessmentResponse::class);
    }
}
