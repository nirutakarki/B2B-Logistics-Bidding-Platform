<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Admin\BusinessController as AdminBusinessController;

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
    });
});


