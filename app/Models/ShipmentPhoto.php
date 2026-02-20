<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipmentPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'is_primary',
        'sort_order',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'is_primary' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }
}