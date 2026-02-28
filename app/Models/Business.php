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

    // Loads (only for shipper businesses)
    public function loads()
    {
        return $this->hasMany(Load::class);
    }

    // Bids (only for driver businesses)
    public function bids()
    {
        return $this->hasMany(Bid::class, 'driver_business_id');
    }

    // Assigned Loads (loads assigned to this driver business)
    public function assignedLoads()
    {
        return $this->hasMany(Load::class, 'assigned_driver_business_id');
    }

    // Approval Logs
    public function approvalLogs()
    {
        return $this->morphMany(ApprovalLog::class, 'approvable');
    }

    // Ratings given by this business
    public function ratingsGiven()
    {
        return $this->hasMany(Rating::class, 'rated_by_business_id');
    }

    // Ratings received by this business
    public function ratingsReceived()
    {
        return $this->hasMany(Rating::class, 'rated_business_id');
    }

    // Get average rating for this business
    public function averageRating()
    {
        return $this->ratingsReceived()->avg('rating');
    }

    // Get total number of ratings
    public function totalRatings()
    {
        return $this->ratingsReceived()->count();
    }
}
