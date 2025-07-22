<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MissingReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'full_name',
        'nickname',
        'age',
        'gender',
        'height_cm',
        'weight_kg',
        'physical_description',
        'last_seen_date',
        'last_seen_location',
        'last_seen_clothing',
        'photo_path',
        'police_report_path',
        'reporter_name',
        'reporter_relationship',
        'reporter_phone',
        'reporter_email',
        'additional_notes',
    ];
}
