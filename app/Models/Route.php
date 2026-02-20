<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Route extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'departure_address',
        'departure_city',
        'departure_country',
        'departure_latitude',
        'departure_longitude',
        'departure_date_from',
        'departure_date_to',
        'arrival_address',
        'arrival_city',
        'arrival_country',
        'arrival_latitude',
        'arrival_longitude',
        'arrival_date_from',
        'arrival_date_to',
        'total_capacity_kg',
        'available_capacity_kg',
        'vehicle_type',
        'vehicle_description',
        'price_per_kg',
        'min_shipment_price',
        'status',
        'recurring',
        'recurring_pattern',
        'special_conditions',
        'published_at',
    ];

    protected $casts = [
        'departure_latitude' => 'decimal:8',
        'departure_longitude' => 'decimal:8',
        'departure_date_from' => 'datetime',
        'departure_date_to' => 'datetime',
        'arrival_latitude' => 'decimal:8',
        'arrival_longitude' => 'decimal:8',
        'arrival_date_from' => 'datetime',
        'arrival_date_to' => 'datetime',
        'total_capacity_kg' => 'decimal:2',
        'available_capacity_kg' => 'decimal:2',
        'price_per_kg' => 'decimal:2',
        'min_shipment_price' => 'decimal:2',
        'recurring' => 'boolean',
        'recurring_pattern' => 'array',
        'published_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function waypoints()
    {
        return $this->hasMany(RouteWaypoint::class)->orderBy('stop_order');
    }

    public function matches()
    {
        return $this->hasMany(\App\Models\MatchRecord::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['published', 'in_progress']);
    }

    public function scopeWithCapacity($query)
    {
        return $query->where('available_capacity_kg', '>', 0);
    }
}
