<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBidRequest;
use App\Http\Requests\UpdateBidRequest;
use App\Models\Bid;
use App\Models\Load;
use App\Services\BidService;
use App\Shared\Enums\BusinessType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BidController extends Controller
{
    public function __construct(
        private BidService $bidService
    ) {}


    public function index(Request $request): JsonResponse
    {
        $business = $request->user()->business;
        
        if ($business->type === BusinessType::Driver) {
            $query = Bid::where('driver_business_id', $business->id)
                ->with(['shipment.business', 'vehicle'])
                ->orderBy('created_at', 'desc');
        } else {
            $loadIds = $business->loads()->pluck('id');
            $query = Bid::whereIn('load_id', $loadIds)
                ->with(['driverBusiness', 'vehicle', 'shipment'])
                ->orderBy('created_at', 'desc');
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        $bids = $query->get()->map(function ($bid) use ($business) {
            $data = [
                'id' => $bid->id,
                'load_id' => $bid->load_id,
                'amount' => $bid->amount,
                'status' => $bid->status,
                'notes' => $bid->notes,
                'created_at' => $bid->created_at,
            ];

            if ($business->type === BusinessType::Driver) {
                $data['load'] = [
                    'id' => $bid->shipment->id,
                    'pickup_city' => $bid->shipment->pickup_city,
                    'pickup_state' => $bid->shipment->pickup_state,
                    'delivery_city' => $bid->shipment->delivery_city,
                    'delivery_state' => $bid->shipment->delivery_state,
                    'cargo_type' => $bid->shipment->cargo_type,
                    'price' => $bid->shipment->price,
                ];
            } else {
                $data['driver'] = [
                    'id' => $bid->driverBusiness->id,
                    'name' => $bid->driverBusiness->name,
                    'phone' => $bid->driverBusiness->phone,
                    'email' => $bid->driverBusiness->email,
                ];
                $data['load_route'] = $bid->shipment->pickup_city . ' → ' . $bid->shipment->delivery_city;
            }

            if ($bid->vehicle) {
                $data['vehicle'] = [
                    'id' => $bid->vehicle->id,
                    'make' => $bid->vehicle->make,
                    'model' => $bid->vehicle->model,
                    'type' => $bid->vehicle->type,
                ];
            }

            return $data;
        });
        
        return response()->json([
            'bids' => $bids,
        ]);
    }

    public function store(StoreBidRequest $request, Load $load): JsonResponse
    {
        try {
            $business = $request->user()->business;
            
            $bid = $this->bidService->createBid($business, $load, $request->validated());
            
            return response()->json([
                'message' => 'Bid placed successfully',
                'bid' => [
                    'id' => $bid->id,
                    'load_id' => $bid->load_id,
                    'amount' => $bid->amount,
                    'status' => $bid->status,
                    'notes' => $bid->notes,
                    'created_at' => $bid->created_at,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to place bid',
                'error' => $e->getMessage(),
            ], 400);
        }
    }


    public function show(Request $request, Bid $bid): JsonResponse
    {
        $business = $request->user()->business;
        
        $canView = $bid->driver_business_id === $business->id || 
                   $bid->shipment->business_id === $business->id;
        
        if (!$canView) {
            return response()->json([
                'message' => 'You do not have permission to view this bid',
            ], 403);
        }
        
        $bid->load(['shipment', 'driverBusiness', 'vehicle']);
        
        return response()->json([
            'bid' => [
                'id' => $bid->id,
                'load_id' => $bid->load_id,
                'amount' => $bid->amount,
                'status' => $bid->status,
                'notes' => $bid->notes,
                'driver_business' => [
                    'id' => $bid->driverBusiness->id,
                    'name' => $bid->driverBusiness->name,
                    'phone' => $bid->driverBusiness->phone,
                    'email' => $bid->driverBusiness->email,
                ],
                'load' => [
                    'id' => $bid->shipment->id,
                    'pickup_city' => $bid->shipment->pickup_city,
                    'pickup_state' => $bid->shipment->pickup_state,
                    'delivery_city' => $bid->shipment->delivery_city,
                    'delivery_state' => $bid->shipment->delivery_state,
                    'pickup_date' => $bid->shipment->pickup_date,
                    'delivery_date' => $bid->shipment->delivery_date,
                    'cargo_type' => $bid->shipment->cargo_type,
                    'price' => $bid->shipment->price,
                ],
                'vehicle' => $bid->vehicle ? [
                    'id' => $bid->vehicle->id,
                    'make' => $bid->vehicle->make,
                    'model' => $bid->vehicle->model,
                    'type' => $bid->vehicle->type,
                    'license_plate' => $bid->vehicle->license_plate,
                ] : null,
                'created_at' => $bid->created_at,
                'updated_at' => $bid->updated_at,
            ],
        ]);
    }


    public function update(UpdateBidRequest $request, Bid $bid): JsonResponse
    {
        try {
            $updatedBid = $this->bidService->updateBid($bid, $request->validated());
            
            return response()->json([
                'message' => 'Bid updated successfully',
                'bid' => [
                    'id' => $updatedBid->id,
                    'amount' => $updatedBid->amount,
                    'notes' => $updatedBid->notes,
                    'updated_at' => $updatedBid->updated_at,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update bid',
                'error' => $e->getMessage(),
            ], 400);
        }
    }


    public function withdraw(Request $request, Bid $bid): JsonResponse
    {
        $business = $request->user()->business;
        
        try {
            $withdrawnBid = $this->bidService->withdrawBid($bid, $business);
            
            return response()->json([
                'message' => 'Bid withdrawn successfully',
                'bid' => [
                    'id' => $withdrawnBid->id,
                    'status' => $withdrawnBid->status,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to withdraw bid',
                'error' => $e->getMessage(),
            ], 400);
        }
    }


    public function destroy(Request $request, Bid $bid): JsonResponse
    {
        $business = $request->user()->business;
        
        if ($bid->driver_business_id !== $business->id) {
            return response()->json([
                'message' => 'You can only delete your own bids',
            ], 403);
        }
        
        try {
            $this->bidService->deleteBid($bid);
            
            return response()->json([
                'message' => 'Bid deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete bid',
                'error' => $e->getMessage(),
            ], 400);
        }
    }


    public function loadBids(Request $request, Load $load): JsonResponse
    {
        $business = $request->user()->business;
        
        if ($load->business_id !== $business->id) {
            return response()->json([
                'message' => 'You can only view bids on your own loads',
            ], 403);
        }
        
        $bids = Bid::where('load_id', $load->id)
            ->with(['driverBusiness', 'vehicle'])
            ->orderBy('amount', 'asc') 
            ->get()
            ->map(function ($bid) {
                return [
                    'id' => $bid->id,
                    'amount' => $bid->amount,
                    'status' => $bid->status,
                    'notes' => $bid->notes,
                    'driver' => [
                        'id' => $bid->driverBusiness->id,
                        'name' => $bid->driverBusiness->name,
                        'phone' => $bid->driverBusiness->phone,
                        'email' => $bid->driverBusiness->email,
                    ],
                    'vehicle' => $bid->vehicle ? [
                        'id' => $bid->vehicle->id,
                        'make' => $bid->vehicle->make,
                        'model' => $bid->vehicle->model,
                        'type' => $bid->vehicle->type,
                        'license_plate' => $bid->vehicle->license_plate,
                    ] : null,
                    'created_at' => $bid->created_at,
                ];
            });
        
        return response()->json([
            'load_id' => $load->id,
            'load_route' => $load->pickup_city . ' → ' . $load->delivery_city,
            'bids' => $bids,
            'total_bids' => $bids->count(),
        ]);
    }


    public function accept(Request $request, Bid $bid): JsonResponse
    {
        $business = $request->user()->business;
        
        try {
            $acceptedBid = $this->bidService->acceptBid($bid, $business);
            
            return response()->json([
                'message' => 'Bid accepted successfully. Load has been assigned to driver.',
                'bid' => [
                    'id' => $acceptedBid->id,
                    'status' => $acceptedBid->status,
                    'driver_business' => [
                        'id' => $acceptedBid->driverBusiness->id,
                        'name' => $acceptedBid->driverBusiness->name,
                        'phone' => $acceptedBid->driverBusiness->phone,
                    ],
                ],
                'load' => [
                    'id' => $acceptedBid->shipment->id,
                    'status' => $acceptedBid->shipment->status,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to accept bid',
                'error' => $e->getMessage(),
            ], 400);
        }
    }


    public function reject(Request $request, Bid $bid): JsonResponse
    {
        $business = $request->user()->business;
        
        try {
            $rejectedBid = $this->bidService->rejectBid($bid, $business);
            
            return response()->json([
                'message' => 'Bid rejected',
                'bid' => [
                    'id' => $rejectedBid->id,
                    'status' => $rejectedBid->status,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to reject bid',
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
