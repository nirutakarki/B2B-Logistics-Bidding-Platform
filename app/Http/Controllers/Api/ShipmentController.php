<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Shipment;

class ShipmentController extends Controller
{
    // Create Shipment (Shipper only)
    public function store(Request $request)
    {
        if ($request->user()->role !== 'shipper') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'pickup_location' => 'required|string',
            'delivery_location' => 'required|string',
            'weight' => 'required|numeric',
            'pickup_date' => 'required|date'
        ]);

        $shipment = Shipment::create([
            'company_id' => $request->user()->company_id,
            'pickup_location' => $validated['pickup_location'],
            'delivery_location' => $validated['delivery_location'],
            'weight' => $validated['weight'],
            'pickup_date' => $validated['pickup_date'],
            'status' => 'open'
        ]);

        return response()->json($shipment, 201);
    }

    // View All Open Shipments
    public function index()
    {
        return Shipment::where('status', 'open')->get();
    }

    // View Single Shipment
    public function show($id)
    {
        return Shipment::with('bids')->findOrFail($id);
    }
}
