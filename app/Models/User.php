<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'avatar_url',
        'is_locked',
        'locked_until',
        'failed_login_attempts',
        'last_failed_login',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

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
            'locked_until' => 'datetime',
            'last_failed_login' => 'datetime',
        ];
    }

    public function missingReports()
    {
        return $this->hasMany(\App\Models\MissingReport::class);
    }

    public function userPoint()
    {
        return $this->hasOne(UserPoint::class);
    }

    public function pointTransactions()
    {
        return $this->hasMany(PointTransaction::class);
    }

    public function userRewards()
    {
        return $this->hasMany(UserReward::class);
    }

    public function socialShares()
    {
        return $this->hasMany(SocialShare::class);
    }

    public function getCurrentPointsAttribute()
    {
        return $this->userPoint?->current_points ?? 0;
    }

    public function getTotalEarnedPointsAttribute()
    {
        return $this->userPoint?->total_earned_points ?? 0;
    }

    public function getTotalSpentPointsAttribute()
    {
        return $this->userPoint?->total_spent_points ?? 0;
    }

    /**
     * Check if the user account is currently locked
     */
    public function isAccountLocked(): bool
    {
        if (!$this->is_locked) {
            return false;
        }

        // If locked_until is null or in the past, unlock the account
        if (!$this->locked_until || $this->locked_until->isPast()) {
            $this->unlockAccount();
            return false;
        }

        return true;
    }

    /**
     * Lock the user account for 5 minutes
     */
    public function lockAccount(): void
    {
        $this->update([
            'is_locked' => true,
            'locked_until' => now()->addMinutes(5),
            'failed_login_attempts' => 0, // Reset counter when locked
        ]);
    }

    /**
     * Unlock the user account
     */
    public function unlockAccount(): void
    {
        $this->update([
            'is_locked' => false,
            'locked_until' => null,
            'failed_login_attempts' => 0,
        ]);
    }

    /**
     * Increment failed login attempts
     */
    public function incrementFailedLoginAttempts(): void
    {
        $this->increment('failed_login_attempts');
        $this->update(['last_failed_login' => now()]);

        // Lock account after 3 failed attempts
        if ($this->failed_login_attempts >= 3) {
            $this->lockAccount();
        }
    }

    /**
     * Reset failed login attempts on successful login
     */
    public function resetFailedLoginAttempts(): void
    {
        $this->update([
            'failed_login_attempts' => 0,
            'last_failed_login' => null,
        ]);
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is approved volunteer
     */
    public function isApprovedVolunteer(): bool
    {
        return $this->volunteerApplication && $this->volunteerApplication->status === 'Approved';
    }

    /**
     * Check if user has volunteer application
     */
    public function hasVolunteerApplication(): bool
    {
        return $this->volunteerApplication !== null;
    }

    /**
     * Check if user can access volunteer projects
     */
    public function canAccessVolunteerProjects(): bool
    {
        return $this->isAdmin() || $this->isApprovedVolunteer();
    }

    /**
     * Check if user can access admin dashboard
     */
    public function canAccessAdminDashboard(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Get volunteer application relationship
     */
    public function volunteerApplication()
    {
        return $this->hasOne(VolunteerApplication::class)->latest()->limit(1);
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new \App\Notifications\CustomResetPasswordNotification($token));
    }
}
