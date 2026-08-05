<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Staff account: Extension Coordinator, Trainer, or Evaluator.
 * Distinguished from Beneficiary, which is a separate guard/table.
 */
class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'username', 'first_name', 'last_name', 'email', 'id_number',
        'position', 'department', 'role', 'password_hash', 'is_active', 'last_login',
        'reset_token', 'reset_expires',
    ];

    protected $hidden = ['password_hash', 'remember_token'];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'last_login' => 'datetime',
        ];
    }

    /** Legacy column name compatibility: password_hash instead of password. */
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function isExtensionCoordinator(): bool
    {
        return $this->role === 'extension_coordinator';
    }

    public function isTrainer(): bool
    {
        return $this->role === 'trainer';
    }

    public function isEvaluator(): bool
    {
        return $this->role === 'evaluator';
    }

    public function trainings()
    {
        return $this->hasMany(Training::class, 'trainer_id');
    }

    public function createdTrainings()
    {
        return $this->hasMany(Training::class, 'created_by');
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'uploaded_by');
    }

    public function impactAssessments()
    {
        return $this->hasMany(ImpactAssessment::class, 'evaluator_id');
    }
}
