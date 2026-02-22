<?php

namespace App\Services;

use App\Models\Bid;
use App\Models\Load;
use App\Models\Business;
use App\Models\Vehicle;
use App\Shared\Enums\BidStatus;
use App\Shared\Enums\LoadStatus;
use App\Shared\Enums\BusinessType;
use App\Shared\Enums\VehicleStatus;
use App\Shared\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;

class BidService
{
    public function __construct(
        private ActivityLogger $activityLogger
    ) {}

    public function createBid(Business $business, Load $load, array $data): Bid
    {
        if ($business->type !== BusinessType::Driver) {
            throw new \Exception('Only driver businesses can place bids');
        }

        if ($load->status !== LoadStatus::Open) {
            throw new \Exception('This load is not accepting bids');
        }

        $existingBid = Bid::where('load_id', $load->id)
            ->where('driver_business_id', $business->id)
            ->where('status', BidStatus::Pending)
            ->first();

        if ($existingBid) {
            throw new \Exception('You already have a pending bid on this load');
        }

        if (isset($data['vehicle_id'])) {
            $vehicle = Vehicle::find($data['vehicle_id']);
            
            if (!$vehicle || $vehicle->business_id !== $business->id) {
                throw new \Exception('Invalid vehicle selected');
            }

            if ($vehicle->status !== VehicleStatus::Available) {
                throw new \Exception('Selected vehicle is not available');
            }

            if ($vehicle->type->value !== $load->vehicle_type_required) {
                throw new \Exception('Vehicle type does not match load requirements');
            }
        }

        return DB::transaction(function () use ($business, $load, $data) {
            $bid = Bid::create([
                'load_id' => $load->id,
                'driver_business_id' => $business->id,
                'vehicle_id' => $data['vehicle_id'] ?? null,
                'amount' => $data['amount'],
                'notes' => $data['notes'] ?? null,
                'status' => BidStatus::Pending,
            ]);

            $this->activityLogger->log(
                'bid_created',
                "Bid placed on load #{$load->id} for $" . number_format($bid->amount, 2),
                $bid
            );

            return $bid;
        });
    }


    public function updateBid(Bid $bid, array $data): Bid
    {
        if ($bid->status !== BidStatus::Pending) {
            throw new \Exception('Can only update pending bids');
        }

        DB::transaction(function () use ($bid, $data) {
            $oldAmount = $bid->amount;
            
            if (isset($data['amount'])) {
                $bid->amount = $data['amount'];
            }
            
            if (isset($data['notes'])) {
                $bid->notes = $data['notes'];
            }

            if (isset($data['vehicle_id'])) {
                $vehicle = Vehicle::find($data['vehicle_id']);
                
                if (!$vehicle || $vehicle->business_id !== $bid->driver_business_id) {
                    throw new \Exception('Invalid vehicle selected');
                }

                $bid->vehicle_id = $data['vehicle_id'];
            }

            $bid->save();

            $this->activityLogger->log(
                'bid_updated',
                "Bid updated from $" . number_format($oldAmount, 2) . " to $" . number_format($bid->amount, 2),
                $bid
            );
        });

        return $bid->fresh();
    }


    public function acceptBid(Bid $bid, Business $shipperBusiness): Bid
    {
        $load = $bid->shipment;

        if ($load->business_id !== $shipperBusiness->id) {
            throw new \Exception('You can only accept bids on your own loads');
        }

        if ($bid->status !== BidStatus::Pending) {
            throw new \Exception('Can only accept pending bids');
        }

        if ($load->status !== LoadStatus::Open) {
            throw new \Exception('Load is no longer available for bidding');
        }

        return DB::transaction(function () use ($bid, $load) {
            $bid->status = BidStatus::Accepted;
            $bid->save();

            $load->status = LoadStatus::Assigned;
            $load->assigned_driver_id = $bid->driver_business_id;
            $load->save();

            Bid::where('load_id', $load->id)
                ->where('id', '!=', $bid->id)
                ->where('status', BidStatus::Pending)
                ->update(['status' => BidStatus::Rejected]);

            if ($bid->vehicle_id) {
                $vehicle = Vehicle::find($bid->vehicle_id);
                if ($vehicle) {
                    $vehicle->status = VehicleStatus::InTransit;
                    $vehicle->save();
                }
            }

            $this->activityLogger->log(
                'bid_accepted',
                "Bid accepted for load #{$load->id}. Driver: {$bid->driverBusiness->name}",
                $bid
            );

            return $bid;
        });
    }


    public function rejectBid(Bid $bid, Business $shipperBusiness): Bid
    {
        $load = $bid->shipment;

        if ($load->business_id !== $shipperBusiness->id) {
            throw new \Exception('You can only reject bids on your own loads');
        }

        if ($bid->status !== BidStatus::Pending) {
            throw new \Exception('Can only reject pending bids');
        }

        $bid->status = BidStatus::Rejected;
        $bid->save();

        $this->activityLogger->log(
            'bid_rejected',
            "Bid rejected for load #{$load->id}",
            $bid
        );

        return $bid;
    }


    public function withdrawBid(Bid $bid, Business $driverBusiness): Bid
    {
        if ($bid->driver_business_id !== $driverBusiness->id) {
            throw new \Exception('You can only withdraw your own bids');
        }

        if ($bid->status !== BidStatus::Pending) {
            throw new \Exception('Can only withdraw pending bids');
        }

        $bid->status = BidStatus::Withdrawn;
        $bid->save();

        $this->activityLogger->log(
            'bid_withdrawn',
            "Bid withdrawn for load #{$bid->load_id}",
            $bid
        );

        return $bid;
    }


    public function deleteBid(Bid $bid): void
    {
        if (!in_array($bid->status, [BidStatus::Withdrawn, BidStatus::Rejected])) {
            throw new \Exception('Can only delete withdrawn or rejected bids');
        }

        $this->activityLogger->log(
            'bid_deleted',
            "Bid deleted for load #{$bid->load_id}",
            null
        );

        $bid->delete();
    }
}
