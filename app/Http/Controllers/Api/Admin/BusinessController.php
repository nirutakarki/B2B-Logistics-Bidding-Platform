<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Services\BusinessApprovalService;
use App\Shared\Enums\BusinessStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BusinessController extends Controller
{
    protected BusinessApprovalService $approvalService;

    public function __construct(BusinessApprovalService $approvalService)
    {
        $this->approvalService = $approvalService;
    }

    public function index(Request $request): JsonResponse
    {
        $query = Business::with(['users', 'approver']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $businesses = $query->latest()->paginate(15);

        return response()->json($businesses);
    }

    public function show(Business $business): JsonResponse
    {
        $business->load(['users', 'approver', 'approvalLogs.approver']);

        return response()->json([
            'data' => $business
        ]);
    }

    public function approve(Request $request, Business $business): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:1000'
        ]);

        try {
            $approvedBusiness = $this->approvalService->approveBusiness(
                $business,
                $request->user(),
                $request->reason
            );

            return response()->json([
                'message' => 'Business approved successfully.',
                'data' => $approvedBusiness
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function reject(Request $request, Business $business): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:1000'
        ]);

        try {
            $rejectedBusiness = $this->approvalService->rejectBusiness(
                $business,
                $request->user(),
                $request->reason
            );

            return response()->json([
                'message' => 'Business rejected successfully.',
                'data' => $rejectedBusiness
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function suspend(Request $request, Business $business): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:1000'
        ]);

        try {
            $suspendedBusiness = $this->approvalService->suspendBusiness(
                $business,
                $request->user(),
                $request->reason
            );

            return response()->json([
                'message' => 'Business suspended successfully.',
                'data' => $suspendedBusiness
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
