<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SightingReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'missing_report_id',
        'location',
        'description',
        'sighted_at',
        'photo_paths',
        'reporter_name',
        'reporter_phone',
        'reporter_email',
        'status',
    ];

    protected $casts = [
        'photo_paths' => 'array',
        'sighted_at' => 'datetime',
    ];

    public function missingReport()
    {
        return $this->belongsTo(MissingReport::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}


