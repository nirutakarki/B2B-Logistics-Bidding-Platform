<?php

namespace App\Services;

use App\Models\Load;
use App\Models\Business;
use App\Shared\Enums\LoadStatus;
use App\Shared\Enums\BusinessType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LoadService
{
    /**
     * Create a new load for a shipper business
     */
    public function createLoad(Business $business, array $data): Load
    {
        // Ensure only shippers can create loads
        if ($business->type !== BusinessType::Shipper) {
            throw new \Exception('Only shipper businesses can post loads');
        }

        return DB::transaction(function () use ($business, $data) {
            $load = new Load();
            $load->business_id = $business->id;
            
            // Pickup details
            $load->pickup_address = $data['pickup_address'];
            $load->pickup_city = $data['pickup_city'];
            $load->pickup_state = $data['pickup_state'];
            $load->pickup_zip = $data['pickup_zip'];
            $load->pickup_country = $data['pickup_country'] ?? 'USA';
            
            // Delivery details
            $load->delivery_address = $data['delivery_address'];
            $load->delivery_city = $data['delivery_city'];
            $load->delivery_state = $data['delivery_state'];
            $load->delivery_zip = $data['delivery_zip'];
            $load->delivery_country = $data['delivery_country'] ?? 'USA';
            
            // Dates
            $load->pickup_date = $data['pickup_date'];
            $load->delivery_date = $data['delivery_date'];
            
            // Cargo details
            $load->cargo_type = $data['cargo_type'];
            $load->cargo_weight_kg = $data['cargo_weight_kg'];
            $load->cargo_description = $data['cargo_description'] ?? null;
            
            // Requirements
            $load->vehicle_type_required = $data['vehicle_type_required'];
            $load->price = $data['price'];
            
            // Optional fields
            $load->special_requirements = $data['special_requirements'] ?? null;
            $load->distance_km = $data['distance_km'] ?? null;
            
            // Set status (draft or open)
            $load->status = $data['status'] ?? LoadStatus::Open;
            
            $load->save();
            
            // Log activity
            ActivityLogger::log(
                'load_created',
                'Load created: ' . $load->pickup_city . ' to ' . $load->delivery_city,
                $load,
                $business
            );
            
            return $load;
        });
    }

    /**
     * Update an existing load
     */
    public function updateLoad(Load $load, array $data): Load
    {
        // Only allow updates if load is not assigned or in transit
        if (in_array($load->status, [LoadStatus::Assigned, LoadStatus::InTransit, LoadStatus::Delivered])) {
            throw new \Exception('Cannot update load in current status: ' . $load->status->value);
        }

        DB::transaction(function () use ($load, $data) {
            // Update only provided fields
            if (isset($data['pickup_address'])) $load->pickup_address = $data['pickup_address'];
            if (isset($data['pickup_city'])) $load->pickup_city = $data['pickup_city'];
            if (isset($data['pickup_state'])) $load->pickup_state = $data['pickup_state'];
            if (isset($data['pickup_zip'])) $load->pickup_zip = $data['pickup_zip'];
            if (isset($data['pickup_country'])) $load->pickup_country = $data['pickup_country'];
            
            if (isset($data['delivery_address'])) $load->delivery_address = $data['delivery_address'];
            if (isset($data['delivery_city'])) $load->delivery_city = $data['delivery_city'];
            if (isset($data['delivery_state'])) $load->delivery_state = $data['delivery_state'];
            if (isset($data['delivery_zip'])) $load->delivery_zip = $data['delivery_zip'];
            if (isset($data['delivery_country'])) $load->delivery_country = $data['delivery_country'];
            
            if (isset($data['pickup_date'])) $load->pickup_date = $data['pickup_date'];
            if (isset($data['delivery_date'])) $load->delivery_date = $data['delivery_date'];
            
            if (isset($data['cargo_type'])) $load->cargo_type = $data['cargo_type'];
            if (isset($data['cargo_weight_kg'])) $load->cargo_weight_kg = $data['cargo_weight_kg'];
            if (isset($data['cargo_description'])) $load->cargo_description = $data['cargo_description'];
            
            if (isset($data['vehicle_type_required'])) $load->vehicle_type_required = $data['vehicle_type_required'];
            if (isset($data['price'])) $load->price = $data['price'];
            if (isset($data['special_requirements'])) $load->special_requirements = $data['special_requirements'];
            if (isset($data['distance_km'])) $load->distance_km = $data['distance_km'];
            
            if (isset($data['status'])) $load->status = $data['status'];
            
            $load->save();
            
            ActivityLogger::log(
                'load_updated',
                'Load updated',
                $load,
                $load->business
            );
        });

        return $load->fresh();
    }

    /**
     * Change load status
     */
    public function changeLoadStatus(Load $load, LoadStatus $newStatus): Load
    {
        $oldStatus = $load->status;
        
        // Validate status transitions
        $this->validateStatusTransition($oldStatus, $newStatus);
        
        $load->status = $newStatus;
        $load->save();
        
        ActivityLogger::log(
            'load_status_changed',
            "Load status changed from {$oldStatus->value} to {$newStatus->value}",
            $load,
            $load->business
        );
        
        return $load;
    }

    /**
     * Cancel a load
     */
    public function cancelLoad(Load $load, ?string $reason = null): Load
    {
        // Cannot cancel if already delivered or in transit
        if (in_array($load->status, [LoadStatus::InTransit, LoadStatus::Delivered])) {
            throw new \Exception('Cannot cancel load in current status');
        }

        $load->status = LoadStatus::Cancelled;
        if ($reason) {
            $load->special_requirements = ($load->special_requirements ? $load->special_requirements . "\n\n" : '') 
                . "Cancellation reason: {$reason}";
        }
        $load->save();
        
        ActivityLogger::log(
            'load_cancelled',
            'Load cancelled' . ($reason ? ": {$reason}" : ''),
            $load,
            $load->business
        );
        
        return $load;
    }

    /**
     * Delete a load
     */
    public function deleteLoad(Load $load): void
    {
        // Can only delete draft or cancelled loads
        if (!in_array($load->status, [LoadStatus::Draft, LoadStatus::Cancelled])) {
            throw new \Exception('Can only delete draft or cancelled loads');
        }

        // Check if load has bids
        if ($load->bids()->count() > 0) {
            throw new \Exception('Cannot delete load with existing bids');
        }

        ActivityLogger::log(
            'load_deleted',
            'Load deleted: ' . $load->pickup_city . ' to ' . $load->delivery_city,
            null,
            $load->business
        );

        $load->delete();
    }

    /**
     * Validate status transitions
     */
    private function validateStatusTransition(LoadStatus $from, LoadStatus $to): void
    {
        $validTransitions = [
            LoadStatus::Draft->value => [LoadStatus::Open->value, LoadStatus::Cancelled->value],
            LoadStatus::Open->value => [LoadStatus::Assigned->value, LoadStatus::Cancelled->value],
            LoadStatus::Assigned->value => [LoadStatus::InTransit->value, LoadStatus::Cancelled->value],
            LoadStatus::InTransit->value => [LoadStatus::Delivered->value],
            LoadStatus::Delivered->value => [],
            LoadStatus::Cancelled->value => [],
        ];

        if (!isset($validTransitions[$from->value])) {
            throw new \Exception("Invalid current status: {$from->value}");
        }

        if (!in_array($to->value, $validTransitions[$from->value])) {
            throw new \Exception("Cannot transition from {$from->value} to {$to->value}");
        }
    }
}
