<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLoadRequest;
use App\Http\Requests\UpdateLoadRequest;
use App\Models\Load;
use App\Services\LoadService;
use App\Shared\Enums\LoadStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoadController extends Controller
{
    public function __construct(
        private LoadService $loadService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $business = $request->user()->business;
        
        $query = Load::where('business_id', $business->id)
            ->with(['assignedDriver.business'])
            ->orderBy('created_at', 'desc');
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        $loads = $query->get()->map(function ($load) {
            return [
                'id' => $load->id,
                'pickup_city' => $load->pickup_city,
                'pickup_state' => $load->pickup_state,
                'delivery_city' => $load->delivery_city,
                'delivery_state' => $load->delivery_state,
                'pickup_date' => $load->pickup_date,
                'delivery_date' => $load->delivery_date,
                'cargo_type' => $load->cargo_type,
                'cargo_weight_kg' => $load->cargo_weight_kg,
                'vehicle_type_required' => $load->vehicle_type_required,
                'price' => $load->price,
                'status' => $load->status,
                'distance_km' => $load->distance_km,
                'assigned_driver' => $load->assignedDriver ? [
                    'id' => $load->assignedDriver->id,
                    'business_name' => $load->assignedDriver->business->name,
                ] : null,
                'created_at' => $load->created_at,
            ];
        });
        
        return response()->json([
            'loads' => $loads,
        ]);
    }

    public function store(StoreLoadRequest $request): JsonResponse
    {
        try {
            $business = $request->user()->business;
            
            $load = $this->loadService->createLoad($business, $request->validated());
            
            return response()->json([
                'message' => 'Load posted successfully',
                'load' => [
                    'id' => $load->id,
                    'pickup_location' => $load->pickup_city . ', ' . $load->pickup_state,
                    'delivery_location' => $load->delivery_city . ', ' . $load->delivery_state,
                    'pickup_date' => $load->pickup_date,
                    'delivery_date' => $load->delivery_date,
                    'cargo_type' => $load->cargo_type,
                    'cargo_weight_kg' => $load->cargo_weight_kg,
                    'vehicle_type_required' => $load->vehicle_type_required,
                    'price' => $load->price,
                    'status' => $load->status,
                    'created_at' => $load->created_at,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create load',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function show(Request $request, Load $load): JsonResponse
    {
        $business = $request->user()->business;
        
        if ($load->business_id !== $business->id) {
            return response()->json([
                'message' => 'You can only view your own loads',
            ], 403);
        }
        
        $load->load(['assignedDriver.business', 'bids.business']);
        
        return response()->json([
            'load' => [
                'id' => $load->id,
                'business_id' => $load->business_id,
                
                'pickup_address' => $load->pickup_address,
                'pickup_city' => $load->pickup_city,
                'pickup_state' => $load->pickup_state,
                'pickup_zip' => $load->pickup_zip,
                'pickup_country' => $load->pickup_country,
                'pickup_date' => $load->pickup_date,
                
                'delivery_address' => $load->delivery_address,
                'delivery_city' => $load->delivery_city,
                'delivery_state' => $load->delivery_state,
                'delivery_zip' => $load->delivery_zip,
                'delivery_country' => $load->delivery_country,
                'delivery_date' => $load->delivery_date,
                
                'cargo_type' => $load->cargo_type,
                'cargo_weight_kg' => $load->cargo_weight_kg,
                'cargo_description' => $load->cargo_description,
                
                'vehicle_type_required' => $load->vehicle_type_required,
                'price' => $load->price,
                'special_requirements' => $load->special_requirements,
                'distance_km' => $load->distance_km,
                
                'status' => $load->status,
                
                'assigned_driver' => $load->assignedDriver ? [
                    'id' => $load->assignedDriver->id,
                    'business_id' => $load->assignedDriver->business_id,
                    'business_name' => $load->assignedDriver->business->name,
                    'business_phone' => $load->assignedDriver->business->phone,
                    'business_email' => $load->assignedDriver->business->email,
                ] : null,
                
                'bids_count' => $load->bids->count(),
                
                'created_at' => $load->created_at,
                'updated_at' => $load->updated_at,
            ],
        ]);
    }

    public function update(UpdateLoadRequest $request, Load $load): JsonResponse
    {
        try {
            $updatedLoad = $this->loadService->updateLoad($load, $request->validated());
            
            return response()->json([
                'message' => 'Load updated successfully',
                'load' => [
                    'id' => $updatedLoad->id,
                    'pickup_location' => $updatedLoad->pickup_city . ', ' . $updatedLoad->pickup_state,
                    'delivery_location' => $updatedLoad->delivery_city . ', ' . $updatedLoad->delivery_state,
                    'pickup_date' => $updatedLoad->pickup_date,
                    'delivery_date' => $updatedLoad->delivery_date,
                    'price' => $updatedLoad->price,
                    'status' => $updatedLoad->status,
                    'updated_at' => $updatedLoad->updated_at,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update load',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function cancel(Request $request, Load $load): JsonResponse
    {
        $business = $request->user()->business;
        
        if ($load->business_id !== $business->id) {
            return response()->json([
                'message' => 'You can only cancel your own loads',
            ], 403);
        }
        
        $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);
        
        try {
            $cancelledLoad = $this->loadService->cancelLoad($load, $request->reason);
            
            return response()->json([
                'message' => 'Load cancelled successfully',
                'load' => [
                    'id' => $cancelledLoad->id,
                    'status' => $cancelledLoad->status,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to cancel load',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function destroy(Request $request, Load $load): JsonResponse
    {
        $business = $request->user()->business;
        
        if ($load->business_id !== $business->id) {
            return response()->json([
                'message' => 'You can only delete your own loads',
            ], 403);
        }
        
        try {
            $this->loadService->deleteLoad($load);
            
            return response()->json([
                'message' => 'Load deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete load',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function marketplace(Request $request): JsonResponse
    {
        $query = Load::where('status', LoadStatus::Open)
            ->with(['business'])
            ->orderBy('pickup_date', 'asc');
        
        if ($request->has('vehicle_type')) {
            $query->where('vehicle_type_required', $request->vehicle_type);
        }
        
        if ($request->has('pickup_state')) {
            $query->where('pickup_state', $request->pickup_state);
        }
        
        if ($request->has('delivery_state')) {
            $query->where('delivery_state', $request->delivery_state);
        }
        
        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        
        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }
        
        $loads = $query->get()->map(function ($load) {
            return [
                'id' => $load->id,
                'shipper_name' => $load->business->name,
                'pickup_city' => $load->pickup_city,
                'pickup_state' => $load->pickup_state,
                'pickup_date' => $load->pickup_date,
                'delivery_city' => $load->delivery_city,
                'delivery_state' => $load->delivery_state,
                'delivery_date' => $load->delivery_date,
                'cargo_type' => $load->cargo_type,
                'cargo_weight_kg' => $load->cargo_weight_kg,
                'vehicle_type_required' => $load->vehicle_type_required,
                'price' => $load->price,
                'distance_km' => $load->distance_km,
                'posted_at' => $load->created_at,
            ];
        });
        
        return response()->json([
            'loads' => $loads,
            'count' => $loads->count(),
        ]);
    }
}
