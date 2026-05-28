<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;

// Authentication routes (public)
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected authentication routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
});

// Public routes
Route::get('/locations', function () {
    return response()->json(\App\Models\Location::all());
});

Route::get('/vehicles', function () {
    return response()->json(['data' => \App\Models\Vehicle::all()]);
});

// Protected routes - require authentication
Route::middleware('auth:sanctum')->group(function () {
    // Vehicle routes
    Route::post('/vehicles/scan', [VehicleController::class, 'scan']);
    Route::get('/vehicles/{barcode}', [VehicleController::class, 'show']);

    // Queue routes - with role-based access control
    Route::post('/queues', [QueueController::class, 'store'])->middleware('role:driver,admin');
    Route::get('/queues', function () {
        $user = auth()->user();
        
        // Admin and security can view all queues
        if ($user->hasAnyRole(['admin', 'security'])) {
            return response()->json(['data' => \App\Models\Queue::all()]);
        }
        
        // Drivers can only view their own queues
        if ($user->hasRole('driver')) {
            return response()->json(['data' => \App\Models\Queue::where('user_id', $user->id)->get()]);
        }
        
        return response()->json(['data' => []], 403);
    });
    
    Route::get('/queues/location/{location_id}', [QueueController::class, 'index']);
    Route::get('/queues/{id}', [QueueController::class, 'show']);
    Route::put('/queues/{id}/status', [QueueController::class, 'updateStatus'])->middleware('role:security,admin');
});

