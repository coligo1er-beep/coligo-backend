<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryProof extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'type',
        'file_path',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array'
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }
}
