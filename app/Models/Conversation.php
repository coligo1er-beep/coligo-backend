<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'source_id',
        'participant_1_id',
        'participant_2_id',
        'last_message_at',
        'is_archived'
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'is_archived' => 'boolean',
    ];

    public function participant1()
    {
        return $this->belongsTo(User::class, 'participant_1_id');
    }

    public function participant2()
    {
        return $this->belongsTo(User::class, 'participant_2_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    /**
     * Get the other participant in the conversation.
     */
    public function getOtherParticipant($userId)
    {
        return $this->participant_1_id == $userId ? $this->participant2 : $this->participant1;
    }
}
