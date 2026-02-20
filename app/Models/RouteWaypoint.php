<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RouteWaypoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'route_id',
        'address',
        'city',
        'country',
        'latitude',
        'longitude',
        'stop_order',
        'estimated_arrival',
        'is_flexible',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'stop_order' => 'integer',
        'estimated_arrival' => 'datetime',
        'is_flexible' => 'boolean',
    ];

    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('stop_order');
    }
}
