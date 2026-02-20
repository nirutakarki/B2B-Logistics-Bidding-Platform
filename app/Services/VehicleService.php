<?php

namespace App\Services;

use App\Models\Business;
use App\Models\User;
use App\Models\Vehicle;
use App\Shared\Enums\BusinessStatus;
use App\Shared\Enums\VehicleStatus;
use App\Shared\Services\ActivityLogger;
use Exception;

class VehicleService
{
    protected ActivityLogger $activityLogger;

    public function __construct(ActivityLogger $activityLogger)
    {
        $this->activityLogger = $activityLogger;
    }

    public function createVehicle(User $user, array $data): Vehicle
    {
        $business = $user->business;

        if ($business->status !== BusinessStatus::Approved) {
            throw new Exception('Your business must be approved before registering vehicles.');
        }

        $vehicle = Vehicle::create([
            'business_id' => $business->id,
            'registration_number' => $data['registration_number'],
            'vehicle_type' => $data['vehicle_type'],
            'capacity' => $data['capacity'],
            'status' => VehicleStatus::Active,
        ]);

        $this->activityLogger->log(
            'vehicle_registered',
            "Vehicle {$vehicle->registration_number} registered by {$business->name}.",
            $vehicle,
            $user
        );

        return $vehicle;
    }

    public function updateVehicle(Vehicle $vehicle, array $data): Vehicle
    {
        $vehicle->update($data);

        return $vehicle->fresh();
    }

    public function changeVehicleStatus(Vehicle $vehicle, VehicleStatus $status, User $user): Vehicle
    {
        $oldStatus = $vehicle->status;
        $vehicle->status = $status;
        $vehicle->save();

        $this->activityLogger->log(
            'vehicle_status_changed',
            "Vehicle {$vehicle->registration_number} status changed from {$oldStatus->value} to {$status->value}.",
            $vehicle,
            $user
        );

        return $vehicle->fresh();
    }

    public function deleteVehicle(Vehicle $vehicle, User $user): bool
    {
        $activeBidsCount = $vehicle->bids()
            ->whereIn('status', ['pending', 'accepted'])
            ->count();

        if ($activeBidsCount > 0) {
            throw new Exception('Cannot delete vehicle with active or pending bids.');
        }

        $registrationNumber = $vehicle->registration_number;
        $deleted = $vehicle->delete();

        if ($deleted) {
            $this->activityLogger->log(
                'vehicle_deleted',
                "Vehicle {$registrationNumber} deleted.",
                null,
                $user
            );
        }

        return $deleted;
    }
}
