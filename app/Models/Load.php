<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Shared\Enums\LoadStatus;

class Load extends Model
{
    protected $fillable = [
        'business_id',
        'pickup_location',
        'delivery_location',
        'pickup_date',
        'delivery_deadline',
        'weight',
        'cargo_description',
        'special_requirements',
        'budget_amount',
        'status',
        'assigned_driver_business_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => LoadStatus::class,
            'pickup_date' => 'date',
            'delivery_deadline' => 'date',
            'weight' => 'decimal:2',
            'budget_amount' => 'decimal:2',
        ];
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function assignedDriverBusiness()
    {
        return $this->belongsTo(Business::class, 'assigned_driver_business_id');
    }

    public function bids()
    {
        return $this->hasMany(Bid::class);
    }

    public function approvalLogs()
    {
        return $this->morphMany(ApprovalLog::class, 'approvable');
    }
}
