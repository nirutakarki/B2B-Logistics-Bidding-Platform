<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Shared\Enums\VehicleStatus;
use App\Shared\Enums\VehicleType;

class Vehicle extends Model
{
    protected $fillable = [
        'business_id',
        'registration_number',
        'vehicle_type',
        'capacity',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'vehicle_type' => VehicleType::class,
            'status' => VehicleStatus::class,
            'capacity' => 'decimal:2',
        ];
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function bids()
    {
        return $this->hasMany(Bid::class);
    }
}
