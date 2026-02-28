<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateRatingRequest;
use App\Models\Load;
use App\Models\Rating;
use App\Models\Business;
use App\Shared\Enums\LoadStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    /**
     * Create a rating for a completed load
     */
    public function store(CreateRatingRequest $request, Load $load): JsonResponse
    {
        $business = $request->user()->business;
        
        // Determine who is being rated
        $isShipper = $load->business_id === $business->id;
        $ratedBusinessId = $isShipper ? $load->assigned_driver_id : $load->business_id;
        
        // Check if rating already exists
        $existingRating = Rating::where([
            'rated_by_business_id' => $business->id,
            'rated_business_id' => $ratedBusinessId,
            'load_id' => $load->id,
        ])->first();
        
        if ($existingRating) {
            return response()->json([
                'message' => 'You have already rated this delivery',
            ], 400);
        }
        
        $rating = Rating::create([
            'rated_by_business_id' => $business->id,
            'rated_business_id' => $ratedBusinessId,
            'load_id' => $load->id,
            'rating' => $request->rating,
            'review_text' => $request->review_text,
        ]);
        
        $rating->load(['ratedBy', 'ratedBusiness', 'shipment']);
        
        return response()->json([
            'message' => 'Rating submitted successfully',
            'rating' => [
                'id' => $rating->id,
                'rating' => $rating->rating,
                'review_text' => $rating->review_text,
                'rated_by' => [
                    'id' => $rating->ratedBy->id,
                    'name' => $rating->ratedBy->name,
                ],
                'rated_business' => [
                    'id' => $rating->ratedBusiness->id,
                    'name' => $rating->ratedBusiness->name,
                ],
                'load' => [
                    'id' => $rating->shipment->id,
                    'route' => $rating->shipment->pickup_city . ', ' . $rating->shipment->pickup_state . 
                              ' → ' . $rating->shipment->delivery_city . ', ' . $rating->shipment->delivery_state,
                ],
                'created_at' => $rating->created_at,
            ],
        ], 201);
    }

    /**
     * Get ratings for a specific business (public profile)
     */
    public function businessRatings(Business $business): JsonResponse
    {
        $ratings = Rating::where('rated_business_id', $business->id)
            ->with(['ratedBy', 'shipment'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($rating) {
                return [
                    'id' => $rating->id,
                    'rating' => $rating->rating,
                    'review_text' => $rating->review_text,
                    'rated_by' => [
                        'id' => $rating->ratedBy->id,
                        'name' => $rating->ratedBy->name,
                    ],
                    'load' => [
                        'id' => $rating->shipment->id,
                        'route' => $rating->shipment->pickup_city . ', ' . $rating->shipment->pickup_state . 
                                  ' → ' . $rating->shipment->delivery_city . ', ' . $rating->shipment->delivery_state,
                    ],
                    'created_at' => $rating->created_at,
                ];
            });
        
        $averageRating = Rating::where('rated_business_id', $business->id)->avg('rating');
        $totalRatings = Rating::where('rated_business_id', $business->id)->count();
        
        // Rating distribution
        $distribution = [
            '5_stars' => Rating::where('rated_business_id', $business->id)->where('rating', 5)->count(),
            '4_stars' => Rating::where('rated_business_id', $business->id)->where('rating', 4)->count(),
            '3_stars' => Rating::where('rated_business_id', $business->id)->where('rating', 3)->count(),
            '2_stars' => Rating::where('rated_business_id', $business->id)->where('rating', 2)->count(),
            '1_star' => Rating::where('rated_business_id', $business->id)->where('rating', 1)->count(),
        ];
        
        return response()->json([
            'business' => [
                'id' => $business->id,
                'name' => $business->name,
                'type' => $business->type,
            ],
            'summary' => [
                'average_rating' => $averageRating ? round($averageRating, 2) : 0,
                'total_ratings' => $totalRatings,
                'distribution' => $distribution,
            ],
            'ratings' => $ratings,
        ]);
    }

    /**
     * Get ratings given by the authenticated user's business
     */
    public function myRatings(Request $request): JsonResponse
    {
        $business = $request->user()->business;
        
        $ratings = Rating::where('rated_by_business_id', $business->id)
            ->with(['ratedBusiness', 'shipment'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($rating) {
                return [
                    'id' => $rating->id,
                    'rating' => $rating->rating,
                    'review_text' => $rating->review_text,
                    'rated_business' => [
                        'id' => $rating->ratedBusiness->id,
                        'name' => $rating->ratedBusiness->name,
                        'type' => $rating->ratedBusiness->type,
                    ],
                    'load' => [
                        'id' => $rating->shipment->id,
                        'route' => $rating->shipment->pickup_city . ', ' . $rating->shipment->pickup_state . 
                                  ' → ' . $rating->shipment->delivery_city . ', ' . $rating->shipment->delivery_state,
                    ],
                    'created_at' => $rating->created_at,
                ];
            });
        
        return response()->json([
            'ratings' => $ratings,
            'total' => $ratings->count(),
        ]);
    }

    /**
     * Get rating for a specific load
     */
    public function loadRating(Request $request, Load $load): JsonResponse
    {
        $business = $request->user()->business;
        
        // User can view rating if they were involved in the load
        $canView = $load->business_id === $business->id || 
                   $load->assigned_driver_id === $business->id;
        
        if (!$canView) {
            return response()->json([
                'message' => 'You do not have permission to view ratings for this load',
            ], 403);
        }
        
        $ratings = Rating::where('load_id', $load->id)
            ->with(['ratedBy', 'ratedBusiness'])
            ->get()
            ->map(function ($rating) {
                return [
                    'id' => $rating->id,
                    'rating' => $rating->rating,
                    'review_text' => $rating->review_text,
                    'rated_by' => [
                        'id' => $rating->ratedBy->id,
                        'name' => $rating->ratedBy->name,
                        'type' => $rating->ratedBy->type,
                    ],
                    'rated_business' => [
                        'id' => $rating->ratedBusiness->id,
                        'name' => $rating->ratedBusiness->name,
                        'type' => $rating->ratedBusiness->type,
                    ],
                    'created_at' => $rating->created_at,
                ];
            });
        
        // Determine if current user can still rate this load
        $alreadyRated = Rating::where([
            'rated_by_business_id' => $business->id,
            'load_id' => $load->id,
        ])->exists();
        
        $canRate = $load->status === LoadStatus::Completed && !$alreadyRated;
        
        return response()->json([
            'load' => [
                'id' => $load->id,
                'route' => $load->pickup_city . ', ' . $load->pickup_state . 
                          ' → ' . $load->delivery_city . ', ' . $load->delivery_state,
                'status' => $load->status,
            ],
            'ratings' => $ratings,
            'can_rate' => $canRate,
        ]);
    }

    /**
     * Update a rating (edit your own rating)
     */
    public function update(Request $request, Rating $rating): JsonResponse
    {
        $business = $request->user()->business;
        
        if ($rating->rated_by_business_id !== $business->id) {
            return response()->json([
                'message' => 'You can only update your own ratings',
            ], 403);
        }
        
        $request->validate([
            'rating' => ['sometimes', 'integer', 'min:1', 'max:5'],
            'review_text' => ['nullable', 'string', 'max:1000'],
        ]);
        
        $rating->update($request->only(['rating', 'review_text']));
        
        return response()->json([
            'message' => 'Rating updated successfully',
            'rating' => [
                'id' => $rating->id,
                'rating' => $rating->rating,
                'review_text' => $rating->review_text,
                'updated_at' => $rating->updated_at,
            ],
        ]);
    }

    /**
     * Delete a rating (remove your own rating)
     */
    public function destroy(Request $request, Rating $rating): JsonResponse
    {
        $business = $request->user()->business;
        
        if ($rating->rated_by_business_id !== $business->id) {
            return response()->json([
                'message' => 'You can only delete your own ratings',
            ], 403);
        }
        
        $rating->delete();
        
        return response()->json([
            'message' => 'Rating deleted successfully',
        ]);
    }
}
