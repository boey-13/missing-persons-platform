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
}
