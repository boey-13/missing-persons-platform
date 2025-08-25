<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectNews extends Model
{
    use HasFactory;

    protected $fillable = [
        'community_project_id',
        'content',
        'files',
        'created_by'
    ];

    protected $casts = [
        'files' => 'array'
    ];

    public function project()
    {
        return $this->belongsTo(CommunityProject::class, 'community_project_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
