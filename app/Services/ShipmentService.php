<?php

namespace App\Services;

use App\Models\Load;
use App\Models\Business;
use App\Shared\Enums\LoadStatus;
use App\Shared\Enums\VehicleStatus;
use App\Shared\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;

class ShipmentService
{
    public function __construct(
        private ActivityLogger $activityLogger
    ) {}

    /**
     * Start shipment - driver marks load as in progress
     */
    public function startShipment(Load $load, Business $driverBusiness, ?string $notes = null): Load
    {
        // Validate driver owns this load
        if ($load->assigned_driver_id !== $driverBusiness->id) {
            throw new \Exception('Only the assigned driver can start this shipment');
        }

        // Can only start from assigned status
        if ($load->status !== LoadStatus::Assigned) {
            throw new \Exception('Can only start shipment from assigned status. Current: ' . $load->status->value);
        }

        return DB::transaction(function () use ($load, $notes) {
            $load->status = LoadStatus::InProgress;
            $load->save();

            // Update vehicle status if assigned
            $acceptedBid = $load->bids()->where('status', 'accepted')->first();
            if ($acceptedBid && $acceptedBid->vehicle_id) {
                $vehicle = $acceptedBid->vehicle;
                $vehicle->status = VehicleStatus::InTransit;
                $vehicle->save();
            }

            $this->activityLogger->log(
                'shipment_started',
                "Shipment started for load #{$load->id}" . ($notes ? ": {$notes}" : ''),
                $load
            );

            return $load;
        });
    }

    /**
     * Mark pickup completed
     */
    public function markPickupCompleted(Load $load, Business $driverBusiness, ?string $notes = null): Load
    {
        // Validate driver owns this load
        if ($load->assigned_driver_id !== $driverBusiness->id) {
            throw new \Exception('Only the assigned driver can update this shipment');
        }

        // Must be in progress
        if ($load->status !== LoadStatus::InProgress) {
            throw new \Exception('Shipment must be in progress to mark pickup completed');
        }

        $this->activityLogger->log(
            'pickup_completed',
            "Pickup completed for load #{$load->id} at {$load->pickup_city}, {$load->pickup_state}" . ($notes ? ": {$notes}" : ''),
            $load
        );

        return $load->fresh();
    }

    /**
     * Complete delivery - mark shipment as delivered
     */
    public function completeDelivery(Load $load, Business $driverBusiness, array $data = []): Load
    {
        // Validate driver owns this load
        if ($load->assigned_driver_id !== $driverBusiness->id) {
            throw new \Exception('Only the assigned driver can complete this delivery');
        }

        // Must be in progress
        if ($load->status !== LoadStatus::InProgress) {
            throw new \Exception('Shipment must be in progress to complete delivery. Current: ' . $load->status->value);
        }

        return DB::transaction(function () use ($load, $data) {
            $load->status = LoadStatus::Completed;
            
            // Store delivery notes if provided
            if (isset($data['delivery_notes'])) {
                $load->special_requirements = ($load->special_requirements ? $load->special_requirements . "\n\n" : '') 
                    . "Delivery Notes: {$data['delivery_notes']}";
            }
            
            $load->save();

            // Update vehicle status back to available
            $acceptedBid = $load->bids()->where('status', 'accepted')->first();
            if ($acceptedBid && $acceptedBid->vehicle_id) {
                $vehicle = $acceptedBid->vehicle;
                $vehicle->status = VehicleStatus::Available;
                $vehicle->save();
            }

            $this->activityLogger->log(
                'delivery_completed',
                "Delivery completed for load #{$load->id} at {$load->delivery_city}, {$load->delivery_state}",
                $load
            );

            return $load;
        });
    }

    /**
     * Add tracking update - driver provides location/status update
     */
    public function addTrackingUpdate(Load $load, Business $driverBusiness, array $data): void
    {
        // Validate driver owns this load
        if ($load->assigned_driver_id !== $driverBusiness->id) {
            throw new \Exception('Only the assigned driver can add tracking updates');
        }

        // Must be in progress
        if ($load->status !== LoadStatus::InProgress) {
            throw new \Exception('Can only add tracking updates for shipments in progress');
        }

        $message = $data['message'] ?? 'Status update';
        $location = isset($data['location']) ? " at {$data['location']}" : '';

        $this->activityLogger->log(
            'tracking_update',
            "Tracking update for load #{$load->id}: {$message}{$location}",
            $load
        );
    }

    /**
     * Get shipment timeline (activity logs for a load)
     */
    public function getShipmentTimeline(Load $load): array
    {
        $activities = $load->activityLogs()
            ->whereIn('action', [
                'bid_accepted',
                'shipment_started',
                'pickup_completed',
                'tracking_update',
                'delivery_completed'
            ])
            ->orderBy('created_at', 'asc')
            ->get();

        return $activities->map(function ($activity) {
            return [
                'action' => $activity->action,
                'description' => $activity->description,
                'timestamp' => $activity->created_at,
                'user_id' => $activity->user_id,
            ];
        })->toArray();
    }

    /**
     * Cancel shipment (if issues arise)
     */
    public function cancelShipment(Load $load, Business $business, string $reason): Load
    {
        // Either shipper or assigned driver can cancel
        $canCancel = $load->business_id === $business->id || 
                     $load->assigned_driver_id === $business->id;

        if (!$canCancel) {
            throw new \Exception('Only the shipper or assigned driver can cancel this shipment');
        }

        // Can only cancel if assigned or in progress
        if (!in_array($load->status, [LoadStatus::Assigned, LoadStatus::InProgress])) {
            throw new \Exception('Cannot cancel shipment in current status: ' . $load->status->value);
        }

        return DB::transaction(function () use ($load, $reason) {
            $oldStatus = $load->status;
            $load->status = LoadStatus::Cancelled;
            $load->special_requirements = ($load->special_requirements ? $load->special_requirements . "\n\n" : '') 
                . "Cancellation reason: {$reason}";
            $load->save();

            // Free up vehicle if assigned
            $acceptedBid = $load->bids()->where('status', 'accepted')->first();
            if ($acceptedBid && $acceptedBid->vehicle_id) {
                $vehicle = $acceptedBid->vehicle;
                $vehicle->status = VehicleStatus::Available;
                $vehicle->save();
            }

            $this->activityLogger->log(
                'shipment_cancelled',
                "Shipment cancelled from {$oldStatus->value} status: {$reason}",
                $load
            );

            return $load;
        });
    }
}
