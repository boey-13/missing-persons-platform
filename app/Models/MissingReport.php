<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MissingReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'full_name',
        'ic_number',
        'nickname',
        'age',
        'gender',
        'height_cm',
        'weight_kg',
        'physical_description',
        'last_seen_date',
        'last_seen_location',
        'last_seen_clothing',
        'photo_paths',
        'police_report_path',
        'reporter_relationship',
        'reporter_relationship_other',
        'reporter_name',
        'reporter_ic_number',
        'reporter_phone',
        'reporter_email',
        'additional_notes',
        'case_status',
        'rejection_reason',
    ];

    protected $casts = [
        'photo_paths' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function sightings()
    {
        return $this->hasMany(\App\Models\SightingReport::class);
    }

}


