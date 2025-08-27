<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reward extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'description',
        'points_required',
        'stock_quantity',
        'redeemed_count',
        'image_path',
        'voucher_code_prefix',
        'validity_days',
        'status',
    ];

    protected $casts = [
        'points_required' => 'integer',
        'stock_quantity' => 'integer',
        'redeemed_count' => 'integer',
        'validity_days' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(RewardCategory::class, 'category_id');
    }

    public function userRewards()
    {
        return $this->hasMany(UserReward::class);
    }

    public function getIsAvailableAttribute()
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->stock_quantity === 0) {
            return true; // Unlimited
        }

        return $this->redeemed_count < $this->stock_quantity;
    }

    public function getRemainingStockAttribute()
    {
        if ($this->stock_quantity === 0) {
            return 'Unlimited';
        }

        return $this->stock_quantity - $this->redeemed_count;
    }

    public function getImageUrlAttribute()
    {
        if ($this->image_path) {
            return asset('storage/' . $this->image_path);
        }
        return asset('voucher.png');
    }
}
