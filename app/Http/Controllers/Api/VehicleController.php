<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use App\Models\Vehicle;
use App\Services\VehicleService;
use App\Shared\Enums\VehicleStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    protected VehicleService $vehicleService;

    public function __construct(VehicleService $vehicleService)
    {
        $this->vehicleService = $vehicleService;
    }

    public function index(Request $request): JsonResponse
    {
        $business = $request->user()->business;

        if (!$business) {
            return response()->json([
                'message' => 'You must have a business to view vehicles.'
            ], 403);
        }

        $query = Vehicle::where('business_id', $business->id);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $vehicles = $query->latest()->get();

        return response()->json([
            'data' => $vehicles
        ]);
    }

    public function store(StoreVehicleRequest $request): JsonResponse
    {
        try {
            $vehicle = $this->vehicleService->createVehicle(
                $request->user(),
                $request->validated()
            );

            return response()->json([
                'message' => 'Vehicle registered successfully.',
                'data' => $vehicle
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function show(Vehicle $vehicle): JsonResponse
    {
        return response()->json([
            'data' => $vehicle
        ]);
    }

    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): JsonResponse
    {
        try {
            $updated = $this->vehicleService->updateVehicle(
                $vehicle,
                $request->validated()
            );

            return response()->json([
                'message' => 'Vehicle updated successfully.',
                'data' => $updated
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function updateStatus(Request $request, Vehicle $vehicle): JsonResponse
    {
        if ($vehicle->business_id !== $request->user()->business_id) {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 403);
        }

        $request->validate([
            'status' => ['required', 'in:' . implode(',', array_column(VehicleStatus::cases(), 'value'))]
        ]);

        try {
            $updated = $this->vehicleService->changeVehicleStatus(
                $vehicle,
                VehicleStatus::from($request->status),
                $request->user()
            );

            return response()->json([
                'message' => 'Vehicle status updated successfully.',
                'data' => $updated
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function destroy(Request $request, Vehicle $vehicle): JsonResponse
    {
        if ($vehicle->business_id !== $request->user()->business_id) {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 403);
        }

        try {
            $this->vehicleService->deleteVehicle($vehicle, $request->user());

            return response()->json([
                'message' => 'Vehicle deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
