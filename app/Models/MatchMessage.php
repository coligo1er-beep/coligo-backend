<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MatchMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'match_id',
        'sender_id',
        'message',
        'message_type',
        'read_at'
    ];

    protected $casts = [
        'read_at' => 'datetime'
    ];

    protected $appends = ['is_read'];

    public function match()
    {
        return $this->belongsTo(MatchRecord::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function getIsReadAttribute()
    {
        return !is_null($this->read_at);
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->whereHas('match', function($q) use ($userId) {
            $q->where('transporter_id', $userId)
              ->orWhere('sender_id', $userId);
        });
    }
}
