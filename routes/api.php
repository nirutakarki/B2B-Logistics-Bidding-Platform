<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Admin\BusinessController as AdminBusinessController;
use App\Http\Controllers\Api\VehicleController;
use App\Http\Controllers\Api\LoadController;
use App\Http\Controllers\Api\BidController;
use App\Http\Controllers\Api\ShipmentController;
use App\Http\Controllers\Api\SupportTicketController;
use App\Http\Controllers\Api\RatingController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::prefix('admin')->middleware('admin')->group(function () {
        Route::get('/businesses', [AdminBusinessController::class, 'index']);
        Route::get('/businesses/{business}', [AdminBusinessController::class, 'show']);
        Route::patch('/businesses/{business}/approve', [AdminBusinessController::class, 'approve']);
        Route::patch('/businesses/{business}/reject', [AdminBusinessController::class, 'reject']);
        Route::patch('/businesses/{business}/suspend', [AdminBusinessController::class, 'suspend']);
        
        Route::get('/support-tickets/statistics', [SupportTicketController::class, 'statistics']);
        Route::patch('/support-tickets/{ticket}/assign', [SupportTicketController::class, 'assign']);
        Route::patch('/support-tickets/{ticket}/resolve', [SupportTicketController::class, 'resolve']);
    });

    Route::middleware('business.approved')->group(function () {
        Route::get('/vehicles', [VehicleController::class, 'index']);
        Route::post('/vehicles', [VehicleController::class, 'store']);
        Route::get('/vehicles/{vehicle}', [VehicleController::class, 'show']);
        Route::patch('/vehicles/{vehicle}', [VehicleController::class, 'update']);
        Route::patch('/vehicles/{vehicle}/status', [VehicleController::class, 'updateStatus']);
        Route::delete('/vehicles/{vehicle}', [VehicleController::class, 'destroy']);
        
        Route::get('/loads', [LoadController::class, 'index']);
        Route::post('/loads', [LoadController::class, 'store']);
        Route::get('/loads/{load}', [LoadController::class, 'show']);
        Route::patch('/loads/{load}', [LoadController::class, 'update']);
        Route::patch('/loads/{load}/cancel', [LoadController::class, 'cancel']);
        Route::delete('/loads/{load}', [LoadController::class, 'destroy']);
        
        Route::get('/loads/{load}/bids', [BidController::class, 'loadBids']);
        
        Route::get('/marketplace/loads', [LoadController::class, 'marketplace']);
        
        Route::get('/bids', [BidController::class, 'index']);
        Route::post('/loads/{load}/bids', [BidController::class, 'store']);
        Route::get('/bids/{bid}', [BidController::class, 'show']);
        Route::patch('/bids/{bid}', [BidController::class, 'update']);
        Route::patch('/bids/{bid}/withdraw', [BidController::class, 'withdraw']);
        Route::delete('/bids/{bid}', [BidController::class, 'destroy']);
        
        Route::patch('/bids/{bid}/accept', [BidController::class, 'accept']);
        Route::patch('/bids/{bid}/reject', [BidController::class, 'reject']);
        
        Route::get('/shipments', [ShipmentController::class, 'index']);
        Route::post('/loads/{load}/shipment/start', [ShipmentController::class, 'start']);
        Route::patch('/loads/{load}/shipment/pickup', [ShipmentController::class, 'markPickupCompleted']);
        Route::post('/loads/{load}/shipment/updates', [ShipmentController::class, 'addTrackingUpdate']);
        Route::patch('/loads/{load}/shipment/complete', [ShipmentController::class, 'completeDelivery']);
        Route::get('/loads/{load}/shipment', [ShipmentController::class, 'show']);
        Route::get('/loads/{load}/shipment/timeline', [ShipmentController::class, 'timeline']);
        Route::patch('/loads/{load}/shipment/cancel', [ShipmentController::class, 'cancel']);
        
        Route::get('/support-tickets', [SupportTicketController::class, 'index']);
        Route::post('/support-tickets', [SupportTicketController::class, 'store']);
        Route::get('/support-tickets/{ticket}', [SupportTicketController::class, 'show']);
        Route::patch('/support-tickets/{ticket}', [SupportTicketController::class, 'update']);
        Route::patch('/support-tickets/{ticket}/close', [SupportTicketController::class, 'close']);
        Route::patch('/support-tickets/{ticket}/reopen', [SupportTicketController::class, 'reopen']);
        
        Route::post('/loads/{load}/ratings', [RatingController::class, 'store']);
        Route::get('/loads/{load}/ratings', [RatingController::class, 'loadRating']);
        Route::get('/businesses/{business}/ratings', [RatingController::class, 'businessRatings']);
        Route::get('/my-ratings', [RatingController::class, 'myRatings']);
        Route::patch('/ratings/{rating}', [RatingController::class, 'update']);
        Route::delete('/ratings/{rating}', [RatingController::class, 'destroy']);
    });
});


