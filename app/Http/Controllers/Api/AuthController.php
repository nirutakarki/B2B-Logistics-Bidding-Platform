<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role' => 'required|in:admin,business,driver'
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role']
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }
}

Route::middleware('auth:sanctum')->group(function () {

    Route::post('businesses', [BusinessController::class, 'store']);
    Route::put('businesses/{id}/approve', [BusinessController::class, 'approve']);

    Route::post('drivers', [DriverController::class, 'store']);
    Route::put('drivers/{id}/approve', [DriverController::class, 'approve']);

    Route::post('loads', [LoadController::class, 'store']);
    Route::post('bids', [BidController::class, 'store']);
    Route::put('bids/{id}/accept', [BidController::class, 'accept']);

});

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

// app/Http/Middleware/RoleMiddleware.php

public function handle($request, Closure $next, $role)
{
    if (auth()->user()->role !== $role) {
        return response()->json([
            'message' => 'Unauthorized action.'
        ], 403);
    }

    return $next($request);
}

Route::middleware(['auth:sanctum', 'role:admin'])
    ->put('businesses/{id}/approve', [BusinessController::class, 'approve']);

Route::middleware(['auth:sanctum', 'role:business'])
    ->post('loads', [LoadController::class, 'store']);

Route::middleware(['auth:sanctum', 'role:driver'])
    ->post('bids', [BidController::class, 'store']);
