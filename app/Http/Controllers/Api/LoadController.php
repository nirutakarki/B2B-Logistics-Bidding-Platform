<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LoadController extends Controller
{
    public function index()
    {
        return Load::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'pickup_location' => 'required',
            'delivery_location' => 'required',
            'business_id' => 'required|exists:businesses,id'
        ]);

        $load = Load::create($validated);

        return response()->json($load, 201);
    }
}


