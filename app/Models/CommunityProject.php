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
        'photo_paths',
        'latest_news',
        'news_files'
    ];

    protected $casts = [
        'date' => 'date',
        'time' => 'datetime:H:i',
        'photo_paths' => 'array',
        'news_files' => 'array',
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

    public function news()
    {
        return $this->hasMany(ProjectNews::class)->orderBy('created_at', 'desc');
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

    public function isFull()
    {
        return $this->volunteers_joined >= $this->volunteers_needed;
    }

    public function getAvailableSpotsAttribute()
    {
        return max(0, $this->volunteers_needed - $this->volunteers_joined);
    }

    /**
     * Check if this project has a schedule conflict with another project
     */
    public function hasScheduleConflictWith($otherProject)
    {
        // Different dates = no conflict
        // Extract date from datetime if needed
        $thisDate = $this->date;
        if (strpos($thisDate, ' ') !== false) {
            $thisDate = date('Y-m-d', strtotime($thisDate));
        }
        
        $otherDate = $otherProject->date;
        if (strpos($otherDate, ' ') !== false) {
            $otherDate = date('Y-m-d', strtotime($otherDate));
        }
        
        if ($thisDate != $otherDate) {
            return false;
        }

        // Parse durations
        $thisDuration = $this->parseDuration($this->duration);
        $otherDuration = $this->parseDuration($otherProject->duration);

        // Calculate time ranges
        // Extract time from datetime if needed
        $thisTime = $this->time;
        if (strpos($thisTime, ' ') !== false) {
            $thisTime = date('H:i:s', strtotime($thisTime));
        }
        
        $otherTime = $otherProject->time;
        if (strpos($otherTime, ' ') !== false) {
            $otherTime = date('H:i:s', strtotime($otherTime));
        }
        
        $thisStart = strtotime($thisTime);
        $thisEnd = $thisStart + $thisDuration;

        $otherStart = strtotime($otherTime);
        $otherEnd = $otherStart + $otherDuration;


        // Check for overlap: projects overlap if one starts before the other ends
        return !($thisEnd <= $otherStart || $otherEnd <= $thisStart);
    }

    /**
     * Parse duration string to seconds
     */
    private function parseDuration($duration)
    {
        $duration = strtolower(trim($duration));
        
        // Handle hours
        if (preg_match('/(\d+)\s*h(?:our)?s?/', $duration, $matches)) {
            return (int)$matches[1] * 3600;
        }
        
        // Handle days
        if (preg_match('/(\d+)\s*d(?:ay)?s?/', $duration, $matches)) {
            return (int)$matches[1] * 86400;
        }
        
        // Handle minutes
        if (preg_match('/(\d+)\s*m(?:inute)?s?/', $duration, $matches)) {
            return (int)$matches[1] * 60;
        }
        
        // Default: assume hours if just a number
        if (is_numeric($duration)) {
            return (int)$duration * 3600;
        }
        
        // Default fallback: 2 hours
        return 7200;
    }
}
