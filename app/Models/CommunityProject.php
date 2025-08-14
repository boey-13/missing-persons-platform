<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommunityProject extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'location',
        'date',
        'time',
        'duration',
        'volunteers_needed',
        'volunteers_joined',
        'points_reward',
        'category',
        'status',
        'photo_paths'
    ];

    protected $casts = [
        'date' => 'date',
        'time' => 'datetime:H:i',
        'photo_paths' => 'array',
        'volunteers_needed' => 'integer',
        'volunteers_joined' => 'integer',
        'points_reward' => 'integer'
    ];

    public function applications()
    {
        return $this->hasMany(ProjectApplication::class);
    }

    public function pendingApplications()
    {
        return $this->applications()->where('status', 'pending');
    }

    public function approvedApplications()
    {
        return $this->applications()->where('status', 'approved');
    }

    public function rejectedApplications()
    {
        return $this->applications()->where('status', 'rejected');
    }

    public function getPhotoUrlAttribute()
    {
        if ($this->photo_paths && count($this->photo_paths) > 0) {
            return asset('storage/' . $this->photo_paths[0]);
        }
        return null;
    }

    public function getProgressPercentageAttribute()
    {
        if ($this->volunteers_needed > 0) {
            return round(($this->volunteers_joined / $this->volunteers_needed) * 100);
        }
        return 0;
    }
}
