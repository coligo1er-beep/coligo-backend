<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipmentTracking extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'latitude',
        'longitude',
        'speed'
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }
}
