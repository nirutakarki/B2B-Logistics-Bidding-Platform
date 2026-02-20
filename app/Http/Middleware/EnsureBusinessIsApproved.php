<?php

namespace App\Http\Middleware;

use App\Shared\Enums\BusinessStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBusinessIsApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->business) {
            return response()->json([
                'message' => 'You must have a registered business to access this feature.'
            ], 403);
        }

        if ($user->business->status !== BusinessStatus::Approved) {
            return response()->json([
                'message' => 'Your business must be approved to access this feature. Current status: ' . $user->business->status->value
            ], 403);
        }

        return $next($request);
    }
}
