<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserReward extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reward_id',
        'voucher_code',
        'points_spent',
        'redeemed_at',
        'expires_at',
        'status',
        'used_at',
    ];

    protected $casts = [
        'points_spent' => 'integer',
        'redeemed_at' => 'datetime',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reward()
    {
        return $this->belongsTo(Reward::class);
    }

    public function getIsExpiredAttribute()
    {
        return $this->expires_at->isPast();
    }

    public function getIsActiveAttribute()
    {
        return $this->status === 'active' && !$this->is_expired;
    }

    public function getDaysUntilExpiryAttribute()
    {
        if ($this->is_expired) {
            return 0;
        }

        return now()->diffInDays($this->expires_at, false);
    }

    public function getQrCodeDataAttribute()
    {
        return json_encode([
            'voucher_code' => $this->voucher_code,
            'reward_name' => $this->reward->name,
            'redeemed_at' => $this->redeemed_at->toISOString(),
            'expires_at' => $this->expires_at->toISOString(),
        ]);
    }

    public function getQrCodeImageAttribute()
    {
        $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::size(200)
            ->format('png')
            ->generate($this->qr_code_data);
            
        return 'data:image/png;base64,' . base64_encode($qrCode);
    }
}
