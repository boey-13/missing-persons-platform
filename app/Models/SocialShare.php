<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialShare extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'missing_report_id',
        'platform',
        'share_url',
        'points_awarded'
    ];

    protected $casts = [
        'points_awarded' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function missingReport()
    {
        return $this->belongsTo(MissingReport::class);
    }

    public function getPlatformNameAttribute()
    {
        return ucfirst($this->platform);
    }
}
