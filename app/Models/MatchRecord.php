<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MatchRecord extends Model
{
    use HasFactory;

    protected $table = 'matches';

    protected $fillable = [
        'shipment_id',
        'route_id',
        'transporter_id',
        'sender_id',
        'status',
        'proposed_price',
        'final_price',
        'pickup_datetime',
        'delivery_datetime',
        'matching_score',
        'distance_km',
        'estimated_duration_hours',
        'transporter_message',
        'sender_response',
        'accepted_at',
        'rejected_at',
        'completed_at'
    ];

    protected $casts = [
        'proposed_price' => 'decimal:2',
        'final_price' => 'decimal:2',
        'matching_score' => 'decimal:2',
        'distance_km' => 'decimal:2',
        'estimated_duration_hours' => 'decimal:2',
        'pickup_datetime' => 'datetime',
        'delivery_datetime' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    protected $appends = ['status_label'];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    public function transporter()
    {
        return $this->belongsTo(User::class, 'transporter_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function messages()
    {
        return $this->hasMany(MatchMessage::class)->orderBy('created_at', 'asc');
    }

    public function getStatusLabelAttribute()
    {
        switch($this->status) {
            case 'pending':
                return 'En attente';
            case 'accepted':
                return 'Accepté';
            case 'rejected':
                return 'Refusé';
            case 'completed':
                return 'Terminé';
            case 'cancelled':
                return 'Annulé';
            default:
                return 'Inconnu';
        }
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where(function($q) use ($userId) {
            $q->where('transporter_id', $userId)
              ->orWhere('sender_id', $userId);
        });
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
