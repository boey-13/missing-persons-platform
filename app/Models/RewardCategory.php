<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RewardCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'icon',
    ];

    public function rewards()
    {
        return $this->hasMany(Reward::class, 'category_id');
    }

    public function getActiveRewardsAttribute()
    {
        return $this->rewards()->where('status', 'active')->get();
    }
}
