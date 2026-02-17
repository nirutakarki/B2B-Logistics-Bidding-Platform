<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BusinessController;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/business', [BusinessController::class, 'store']);
});


