<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Shared\Enums\BusinessStatus;
use App\Shared\Enums\BusinessType;

class Business extends Model
{
    protected $fillable = [
        'name',
        'type',
        'status',
        'logo_path',
        'address'
    ];

    protected function casts(): array
    {
        return [
            'type' => BusinessType::class,
            'status' => BusinessStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }

    public function loads()
    {
        return $this->hasMany(Load::class);
    }

    public function bids()
    {
        return $this->hasMany(Bid::class, 'driver_business_id');
    }

    public function assignedLoads()
    {
        return $this->hasMany(Load::class, 'assigned_driver_business_id');
    }

    public function approvalLogs()
    {
        return $this->morphMany(ApprovalLog::class, 'approvable');
    }

    public function ratingsGiven()
    {
        return $this->hasMany(Rating::class, 'rated_by_business_id');
    }

    public function ratingsReceived()
    {
        return $this->hasMany(Rating::class, 'rated_business_id');
    }

    public function averageRating()
    {
        return $this->ratingsReceived()->avg('rating');
    }

    public function totalRatings()
    {
        return $this->ratingsReceived()->count();
    }
}
