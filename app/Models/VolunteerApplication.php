<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VolunteerApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'motivation',
        'skills',
        'languages',
        'availability',
        'preferred_roles',
        'areas',
        'transport_mode',
        'emergency_contact_name',
        'emergency_contact_phone',
        'prior_experience',
        'supporting_documents',
        'status',
        'status_reason',
    ];

    protected $casts = [
        'skills' => 'array',
        'languages' => 'array',
        'availability' => 'array',
        'preferred_roles' => 'array',
        'supporting_documents' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}


