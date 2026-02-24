<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StartShipmentRequest;
use App\Http\Requests\CompleteDeliveryRequest;
use App\Http\Requests\AddTrackingUpdateRequest;
use App\Models\Load;
use App\Services\ShipmentService;
use App\Shared\Enums\LoadStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShipmentController extends Controller
{
    public function __construct(
        private ShipmentService $shipmentService
    ) {}

    public function start(StartShipmentRequest $request, Load $load): JsonResponse
    {
        try {
            $business = $request->user()->business;
            
            $shipment = $this->shipmentService->startShipment(
                $load, 
                $business,
                $request->notes
            );
            
            return response()->json([
                'message' => 'Shipment started successfully',
                'shipment' => [
                    'id' => $shipment->id,
                    'status' => $shipment->status,
                    'pickup_location' => $shipment->pickup_city . ', ' . $shipment->pickup_state,
                    'delivery_location' => $shipment->delivery_city . ', ' . $shipment->delivery_state,
                    'started_at' => now(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to start shipment',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function markPickupCompleted(Request $request, Load $load): JsonResponse
    {
        $business = $request->user()->business;
        
        if ($load->assigned_driver_id !== $business->id) {
            return response()->json([
                'message' => 'Only the assigned driver can update this shipment',
            ], 403);
        }

        $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $shipment = $this->shipmentService->markPickupCompleted(
                $load,
                $business,
                $request->notes
            );
            
            return response()->json([
                'message' => 'Pickup marked as completed',
                'shipment' => [
                    'id' => $shipment->id,
                    'status' => $shipment->status,
                    'pickup_completed_at' => now(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to mark pickup as completed',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function completeDelivery(CompleteDeliveryRequest $request, Load $load): JsonResponse
    {
        try {
            $business = $request->user()->business;
            
            $shipment = $this->shipmentService->completeDelivery(
                $load,
                $business,
                $request->validated()
            );
            
            return response()->json([
                'message' => 'Delivery completed successfully',
                'shipment' => [
                    'id' => $shipment->id,
                    'status' => $shipment->status,
                    'pickup_location' => $shipment->pickup_city . ', ' . $shipment->pickup_state,
                    'delivery_location' => $shipment->delivery_city . ', ' . $shipment->delivery_state,
                    'completed_at' => now(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to complete delivery',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function addTrackingUpdate(AddTrackingUpdateRequest $request, Load $load): JsonResponse
    {
        try {
            $business = $request->user()->business;
            
            $this->shipmentService->addTrackingUpdate(
                $load,
                $business,
                $request->validated()
            );
            
            return response()->json([
                'message' => 'Tracking update added successfully',
                'update' => [
                    'message' => $request->message,
                    'location' => $request->location,
                    'timestamp' => now(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to add tracking update',
                'error' => $e->getMessage(),
            ], 400);
        }
    }


    public function show(Request $request, Load $load): JsonResponse
    {
        $business = $request->user()->business;
        
        $canView = $load->business_id === $business->id || 
                   $load->assigned_driver_id === $business->id;
        
        if (!$canView) {
            return response()->json([
                'message' => 'You do not have permission to view this shipment',
            ], 403);
        }
        
        $load->load(['business', 'assignedDriver', 'bids' => function ($query) {
            $query->where('status', 'accepted')->with('vehicle');
        }]);
        
        $acceptedBid = $load->bids->first();
        
        return response()->json([
            'shipment' => [
                'id' => $load->id,
                'status' => $load->status,
                
                'shipper' => [
                    'id' => $load->business->id,
                    'name' => $load->business->name,
                    'phone' => $load->business->phone,
                    'email' => $load->business->email,
                ],
                
                'driver' => $load->assignedDriver ? [
                    'id' => $load->assignedDriver->id,
                    'name' => $load->assignedDriver->name,
                    'phone' => $load->assignedDriver->phone,
                    'email' => $load->assignedDriver->email,
                ] : null,
                
                'vehicle' => $acceptedBid && $acceptedBid->vehicle ? [
                    'id' => $acceptedBid->vehicle->id,
                    'registration_number' => $acceptedBid->vehicle->registration_number,
                    'type' => $acceptedBid->vehicle->vehicle_type,
                ] : null,
                
                'pickup' => [
                    'address' => $load->pickup_address,
                    'city' => $load->pickup_city,
                    'state' => $load->pickup_state,
                    'zip' => $load->pickup_zip,
                    'date' => $load->pickup_date,
                ],
                
                'delivery' => [
                    'address' => $load->delivery_address,
                    'city' => $load->delivery_city,
                    'state' => $load->delivery_state,
                    'zip' => $load->delivery_zip,
                    'date' => $load->delivery_date,
                ],
                
                'cargo' => [
                    'type' => $load->cargo_type,
                    'weight_kg' => $load->cargo_weight_kg,
                    'description' => $load->cargo_description,
                ],
                
                'price' => $acceptedBid ? $acceptedBid->amount : $load->price,
                'distance_km' => $load->distance_km,
                'special_requirements' => $load->special_requirements,
                
                'created_at' => $load->created_at,
                'updated_at' => $load->updated_at,
            ],
        ]);
    }

    public function timeline(Request $request, Load $load): JsonResponse
    {
        $business = $request->user()->business;
        
        $canView = $load->business_id === $business->id || 
                   $load->assigned_driver_id === $business->id;
        
        if (!$canView) {
            return response()->json([
                'message' => 'You do not have permission to view this shipment timeline',
            ], 403);
        }
        
        $timeline = $this->shipmentService->getShipmentTimeline($load);
        
        return response()->json([
            'shipment_id' => $load->id,
            'route' => $load->pickup_city . ', ' . $load->pickup_state . ' → ' . 
                      $load->delivery_city . ', ' . $load->delivery_state,
            'current_status' => $load->status,
            'timeline' => $timeline,
        ]);
    }

    public function cancel(Request $request, Load $load): JsonResponse
    {
        $business = $request->user()->business;
        
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $cancelledShipment = $this->shipmentService->cancelShipment(
                $load,
                $business,
                $request->reason
            );
            
            return response()->json([
                'message' => 'Shipment cancelled successfully',
                'shipment' => [
                    'id' => $cancelledShipment->id,
                    'status' => $cancelledShipment->status,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to cancel shipment',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * List active shipments for the authenticated user's business
     */
    public function index(Request $request): JsonResponse
    {
        $business = $request->user()->business;
        $businessType = $business->type->value;
        
        // Determine query based on business type
        if ($businessType === 'shipper') {
            // Shippers see their loads that are assigned or in progress
            $query = Load::where('business_id', $business->id)
                ->whereIn('status', [LoadStatus::Assigned, LoadStatus::InProgress, LoadStatus::Completed]);
        } else {
            // Drivers see loads assigned to them
            $query = Load::where('assigned_driver_id', $business->id)
                ->whereIn('status', [LoadStatus::Assigned, LoadStatus::InProgress, LoadStatus::Completed]);
        }
        
        $shipments = $query->with(['business', 'assignedDriver'])
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($load) use ($businessType) {
                $data = [
                    'id' => $load->id,
                    'status' => $load->status,
                    'pickup_city' => $load->pickup_city,
                    'pickup_state' => $load->pickup_state,
                    'delivery_city' => $load->delivery_city,
                    'delivery_state' => $load->delivery_state,
                    'pickup_date' => $load->pickup_date,
                    'delivery_date' => $load->delivery_date,
                    'cargo_type' => $load->cargo_type,
                    'updated_at' => $load->updated_at,
                ];

                if ($businessType === 'shipper') {
                    // Shipper view - show driver details
                    $data['driver'] = $load->assignedDriver ? [
                        'id' => $load->assignedDriver->id,
                        'name' => $load->assignedDriver->name,
                    ] : null;
                } else {
                    // Driver view - show shipper details
                    $data['shipper'] = [
                        'id' => $load->business->id,
                        'name' => $load->business->name,
                    ];
                }

                return $data;
            });
        
        return response()->json([
            'shipments' => $shipments,
            'total' => $shipments->count(),
        ]);
    }
}
