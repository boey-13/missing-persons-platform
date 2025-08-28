<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'subject',
        'message',
        'status',
        'ip_address',
        'user_agent',
        'admin_reply',
        'admin_reply_subject',
        'admin_replied_at',
        'admin_replied_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'admin_replied_at' => 'datetime',
    ];

    public function scopeUnread($query)
    {
        return $query->where('status', 'unread');
    }

    public function scopeRead($query)
    {
        return $query->where('status', 'read');
    }

    public function adminRepliedBy()
    {
        return $this->belongsTo(User::class, 'admin_replied_by');
    }
}
