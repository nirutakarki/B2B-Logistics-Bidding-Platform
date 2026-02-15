<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Bid;
use App\Models\Shipment;

class BidController extends Controller
{
    // Place Bid (Transporter only)
    public function store(Request $request, $shipmentId)
    {
        if ($request->user()->role !== 'transporter') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $shipment = Shipment::findOrFail($shipmentId);

        if ($shipment->status !== 'open') {
            return response()->json(['message' => 'Shipment not open for bidding'], 400);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric',
            'message' => 'nullable|string'
        ]);

        $bid = Bid::create([
            'shipment_id' => $shipment->id,
            'company_id' => $request->user()->company_id,
            'amount' => $validated['amount'],
            'message' => $validated['message'] ?? null,
            'status' => 'pending'
        ]);

        return response()->json($bid, 201);
    }

    // Accept Bid (Shipper only)
    public function accept(Request $request, $bidId)
    {
        $bid = Bid::findOrFail($bidId);
        $shipment = $bid->shipment;

        if ($shipment->company_id !== $request->user()->company_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $bid->update(['status' => 'accepted']);
        $shipment->update(['status' => 'assigned']);

        return response()->json(['message' => 'Bid accepted successfully']);
    }
}
