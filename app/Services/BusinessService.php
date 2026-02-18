<?php

namespace App\Services;

use App\Models\Business;
use App\Models\User;
use App\Shared\Enums\BusinessStatus;
use App\Shared\Enums\BusinessType;
use App\Shared\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Exception;

class BusinessService
{
    protected ActivityLogger $activityLogger;

    public function __construct(ActivityLogger $activityLogger)
    {
        $this->activityLogger = $activityLogger;
    }

    public function createBusiness(User $user, array $data): Business
    {
        if ($user->business) {
            throw new Exception('User already owns a business.');
        }

        return DB::transaction(function () use ($user, $data) {

            $business = Business::create([
                'name' => $data['name'],
                'type' => $data['type'],
                'status' => BusinessStatus::PendingReview,
                'address' => $data['address'] ?? null,
            ]);

            $user->business_id = $business->id;
            $user->save();

            if ($business->type === BusinessType::Shipper) {
                $user->assignRole('shipper_user');
            }

            if ($business->type === BusinessType::Driver) {
                $user->assignRole('driver_user');
            }

            $this->activityLogger->log(
                'business_created',
                "Business {$business->name} created and pending review.",
                $business,
                $user
            );

            return $business;
        });
    }
}
