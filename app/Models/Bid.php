<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Shared\Enums\BidStatus;

class Bid extends Model
{
    protected $fillable = [
        'load_id',
        'driver_business_id',
        'vehicle_id',
        'amount',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => BidStatus::class,
            'amount' => 'decimal:2',
        ];
    }

    public function shipment()
    {
        return $this->belongsTo(Load::class, 'load_id');
    }

    public function driverBusiness()
    {
        return $this->belongsTo(Business::class, 'driver_business_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
