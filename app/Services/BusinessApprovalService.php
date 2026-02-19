<?php

namespace App\Services;

use App\Models\ApprovalLog;
use App\Models\Business;
use App\Models\User;
use App\Shared\Enums\BusinessStatus;
use App\Shared\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Exception;

class BusinessApprovalService
{
    protected ActivityLogger $activityLogger;

    public function __construct(ActivityLogger $activityLogger)
    {
        $this->activityLogger = $activityLogger;
    }

    /**
     * Approve a business
     */
    public function approveBusiness(Business $business, User $admin, ?string $reason = null): Business
    {
        if ($business->status === BusinessStatus::Approved) {
            throw new Exception('Business is already approved.');
        }

        return DB::transaction(function () use ($business, $admin, $reason) {
            $oldStatus = $business->status;

            // Update business
            $business->status = BusinessStatus::Approved;
            $business->approved_by = $admin->id;
            $business->approved_at = now();
            $business->save();

            // Log approval
            ApprovalLog::create([
                'approvable_type' => Business::class,
                'approvable_id' => $business->id,
                'old_status' => $oldStatus->value,
                'new_status' => BusinessStatus::Approved->value,
                'approved_by' => $admin->id,
                'reason' => $reason,
            ]);

            // Activity log
            $this->activityLogger->log(
                'business_approved',
                "Business {$business->name} approved by admin {$admin->name}.",
                $business,
                $admin
            );

            return $business->fresh();
        });
    }

    /**
     * Reject a business
     */
    public function rejectBusiness(Business $business, User $admin, string $reason): Business
    {
        if ($business->status === BusinessStatus::Rejected) {
            throw new Exception('Business is already rejected.');
        }

        return DB::transaction(function () use ($business, $admin, $reason) {
            $oldStatus = $business->status;

            // Update business
            $business->status = BusinessStatus::Rejected;
            $business->save();

            // Log rejection
            ApprovalLog::create([
                'approvable_type' => Business::class,
                'approvable_id' => $business->id,
                'old_status' => $oldStatus->value,
                'new_status' => BusinessStatus::Rejected->value,
                'approved_by' => $admin->id,
                'reason' => $reason,
            ]);

            // Activity log
            $this->activityLogger->log(
                'business_rejected',
                "Business {$business->name} rejected by admin {$admin->name}. Reason: {$reason}",
                $business,
                $admin
            );

            return $business->fresh();
        });
    }

    /**
     * Suspend a business
     */
    public function suspendBusiness(Business $business, User $admin, string $reason): Business
    {
        if ($business->status === BusinessStatus::Suspended) {
            throw new Exception('Business is already suspended.');
        }

        return DB::transaction(function () use ($business, $admin, $reason) {
            $oldStatus = $business->status;

            // Update business
            $business->status = BusinessStatus::Suspended;
            $business->save();

            // Log suspension
            ApprovalLog::create([
                'approvable_type' => Business::class,
                'approvable_id' => $business->id,
                'old_status' => $oldStatus->value,
                'new_status' => BusinessStatus::Suspended->value,
                'approved_by' => $admin->id,
                'reason' => $reason,
            ]);

            // Activity log
            $this->activityLogger->log(
                'business_suspended',
                "Business {$business->name} suspended by admin {$admin->name}. Reason: {$reason}",
                $business,
                $admin
            );

            return $business->fresh();
        });
    }
}
