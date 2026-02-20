<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MatchModel extends Model
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
        'completed_at',
    ];

    protected $casts = [
        'proposed_price' => 'decimal:2',
        'final_price' => 'decimal:2',
        'pickup_datetime' => 'datetime',
        'delivery_datetime' => 'datetime',
        'matching_score' => 'decimal:2',
        'distance_km' => 'decimal:2',
        'estimated_duration_hours' => 'decimal:2',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

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
