<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * The named route for this user's role-specific dashboard.
     */
    public function dashboardRouteName(): string
    {
        return match ($this->role) {
            'extension_coordinator' => 'extension-coordinator.dashboard',
            'project_leader' => 'project-leader.dashboard',
            'evaluator' => 'evaluator.dashboard',
            default => 'beneficiary.dashboard',
        };
    }

    public function trainingsCreated(): HasMany
    {
        return $this->hasMany(Training::class, 'created_by');
    }

    public function trainingsLed(): HasMany
    {
        return $this->hasMany(Training::class, 'project_leader_id');
    }

    public function participations(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    public function attendanceRecorded(): HasMany
    {
        return $this->hasMany(Attendance::class, 'recorded_by');
    }

    public function documentsUploaded(): HasMany
    {
        return $this->hasMany(Document::class, 'uploaded_by');
    }

    public function evaluationFormsCreated(): HasMany
    {
        return $this->hasMany(EvaluationForm::class, 'created_by');
    }

    public function evaluationResponses(): HasMany
    {
        return $this->hasMany(EvaluationResponse::class);
    }

    public function impactAssessments(): HasMany
    {
        return $this->hasMany(ImpactAssessment::class);
    }

    /**
     * Named appNotifications (not notifications) because Notifiable already
     * defines notifications() for Laravel's own notification system, which
     * uses a different table schema than App\Models\Notification.
     */
    public function appNotifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }
}
