<?php

namespace App\Services;

use App\Models\Load;
use App\Models\Business;
use App\Shared\Enums\LoadStatus;
use App\Shared\Enums\BusinessType;
use App\Shared\Services\ActivityLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LoadService
{
    public function __construct(
        private ActivityLogger $activityLogger
    ) {}

    public function createLoad(Business $business, array $data): Load
    {
        if ($business->type !== BusinessType::Shipper) {
            throw new \Exception('Only shipper businesses can post loads');
        }

        return DB::transaction(function () use ($business, $data) {
            $load = new Load();
            $load->business_id = $business->id;
            
            $load->pickup_address = $data['pickup_address'];
            $load->pickup_city = $data['pickup_city'];
            $load->pickup_state = $data['pickup_state'];
            $load->pickup_zip = $data['pickup_zip'];
            $load->pickup_country = $data['pickup_country'] ?? 'USA';
            
            $load->delivery_address = $data['delivery_address'];
            $load->delivery_city = $data['delivery_city'];
            $load->delivery_state = $data['delivery_state'];
            $load->delivery_zip = $data['delivery_zip'];
            $load->delivery_country = $data['delivery_country'] ?? 'USA';
            
            $load->pickup_date = $data['pickup_date'];
            $load->delivery_date = $data['delivery_date'];
            
            $load->cargo_type = $data['cargo_type'];
            $load->cargo_weight_kg = $data['cargo_weight_kg'];
            $load->cargo_description = $data['cargo_description'] ?? null;
            
            $load->vehicle_type_required = $data['vehicle_type_required'];
            $load->price = $data['price'];
            
            $load->special_requirements = $data['special_requirements'] ?? null;
            $load->distance_km = $data['distance_km'] ?? null;
            
            $load->status = $data['status'] ?? LoadStatus::Open;
            
            $load->save();
            
            $this->activityLogger->log(
                'load_created',
                'Load created: ' . $load->pickup_city . ' to ' . $load->delivery_city,
                $load
            );
            
            return $load;
        });
    }

    public function updateLoad(Load $load, array $data): Load
    {
        if (in_array($load->status, [LoadStatus::Assigned, LoadStatus::InProgress, LoadStatus::Completed])) {
            throw new \Exception('Cannot update load in current status: ' . $load->status->value);
        }

        DB::transaction(function () use ($load, $data) {
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
            
            $this->activityLogger->log(
                'load_updated',
                'Load updated',
                $load
            );
        });

        return $load->fresh();
    }


    public function changeLoadStatus(Load $load, LoadStatus $newStatus): Load
    {
        $oldStatus = $load->status;
        
        $this->validateStatusTransition($oldStatus, $newStatus);
        
        $load->status = $newStatus;
        $load->save();
        
        $this->activityLogger->log(
            'load_status_changed',
            "Load status changed from {$oldStatus->value} to {$newStatus->value}",
            $load
        );
        
        return $load;
    }


    public function cancelLoad(Load $load, ?string $reason = null): Load
    {
        if (in_array($load->status, [LoadStatus::InProgress, LoadStatus::Completed])) {
            throw new \Exception('Cannot cancel load in current status');
        }

        $load->status = LoadStatus::Cancelled;
        if ($reason) {
            $load->special_requirements = ($load->special_requirements ? $load->special_requirements . "\n\n" : '') 
                . "Cancellation reason: {$reason}";
        }
        $load->save();
        
        $this->activityLogger->log(
            'load_cancelled',
            'Load cancelled' . ($reason ? ": {$reason}" : ''),
            $load
        );
        
        return $load;
    }

  
    public function deleteLoad(Load $load): void
    {
        if (!in_array($load->status, [LoadStatus::Draft, LoadStatus::Cancelled])) {
            throw new \Exception('Can only delete draft or cancelled loads');
        }

        if ($load->bids()->count() > 0) {
            throw new \Exception('Cannot delete load with existing bids');
        }

        $this->activityLogger->log(
            'load_deleted',
            'Load deleted: ' . $load->pickup_city . ' to ' . $load->delivery_city,
            null
        );

        $load->delete();
    }

    private function validateStatusTransition(LoadStatus $from, LoadStatus $to): void
    {
        $validTransitions = [
            LoadStatus::Draft->value => [LoadStatus::Open->value, LoadStatus::Cancelled->value],
            LoadStatus::Open->value => [LoadStatus::Assigned->value, LoadStatus::Cancelled->value],
            LoadStatus::Assigned->value => [LoadStatus::InProgress->value, LoadStatus::Cancelled->value],
            LoadStatus::InProgress->value => [LoadStatus::Completed->value],
            LoadStatus::Completed->value => [],
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
