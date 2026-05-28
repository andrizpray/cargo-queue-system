<?php

use App\Http\Controllers\QueueController;
use App\Http\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;

Route::post('/queues', [QueueController::class, 'store']);
Route::get('/queues/location/{location_id}', [QueueController::class, 'index']);
Route::get('/queues/{id}', [QueueController::class, 'show']);
Route::put('/queues/{id}/status', [QueueController::class, 'updateStatus']);

Route::post('/vehicles/scan', [VehicleController::class, 'scan']);
Route::get('/vehicles/{barcode}', [VehicleController::class, 'show']);
