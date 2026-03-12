<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'weight',
        'length',
        'width',
        'height',
        'fragile',
        'dangerous_goods',
        'pickup_address',
        'pickup_city',
        'pickup_postal_code',
        'pickup_country',
        'pickup_latitude',
        'pickup_longitude',
        'pickup_date_from',
        'pickup_date_to',
        'delivery_address',
        'delivery_city',
        'delivery_postal_code',
        'delivery_country',
        'delivery_latitude',
        'delivery_longitude',
        'delivery_date_limit',
        'budget_min',
        'budget_max',
        'currency',
        'status',
        'priority',
        'special_instructions',
        'published_at',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'fragile' => 'boolean',
        'dangerous_goods' => 'boolean',
        'pickup_latitude' => 'decimal:8',
        'pickup_longitude' => 'decimal:8',
        'pickup_date_from' => 'datetime',
        'pickup_date_to' => 'datetime',
        'delivery_latitude' => 'decimal:8',
        'delivery_longitude' => 'decimal:8',
        'delivery_date_limit' => 'datetime',
        'budget_min' => 'decimal:2',
        'budget_max' => 'decimal:2',
        'published_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function photos()
    {
        return $this->hasMany(ShipmentPhoto::class)->orderBy('sort_order');
    }

    public function primaryPhoto()
    {
        return $this->hasOne(ShipmentPhoto::class)->where('is_primary', true);
    }

    public function tracking()
    {
        return $this->hasMany(ShipmentTracking::class);
    }

    public function latestTracking()
    {
        return $this->hasOne(ShipmentTracking::class)->latestOfMany();
    }

    public function proofs()
    {
        return $this->hasMany(DeliveryProof::class);
    }

    public function statusHistory()
    {
        return $this->hasMany(ShipmentStatusHistory::class);
    }

    public function matches()
    {
        return $this->hasMany(\App\Models\MatchModel::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['published', 'matched']);
    }
}
