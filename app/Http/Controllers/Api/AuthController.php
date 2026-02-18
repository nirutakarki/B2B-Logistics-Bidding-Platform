<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Services\BusinessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    protected BusinessService $businessService;

    public function __construct(BusinessService $businessService)
    {
        $this->businessService = $businessService;
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->businessService->registerUserWithBusiness($request->validated());
        
        $user = $result['user'];
        $business = $result['business'];

        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful. Your business is pending approval.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'business_id' => $user->business_id,
            ],
            'business' => [
                'id' => $business->id,
                'name' => $business->name,
                'type' => $business->type,
                'status' => $business->status,
                'address' => $business->address,
            ],
            'token' => $token,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials.'
            ], 401);
        }

        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'token' => $token
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully.'
        ]);
    }
}
