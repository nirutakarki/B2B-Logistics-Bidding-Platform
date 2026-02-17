<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBusinessRequest;
use App\Services\BusinessService;
use Illuminate\Http\JsonResponse;

class BusinessController extends Controller
{
    protected BusinessService $businessService;

    public function __construct(BusinessService $businessService)
    {
        $this->businessService = $businessService;
    }

    public function store(StoreBusinessRequest $request): JsonResponse
    {
        $business = $this->businessService->createBusiness(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'message' => 'Business submitted for review.',
            'data' => $business
        ], 201);
    }
}
