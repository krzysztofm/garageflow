<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\LeaveRequestController;
use App\Http\Controllers\Api\BusinessTripController;
use App\Http\Controllers\Api\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'app' => 'GarageFlow',
        'status' => 'ok',
    ]);
});

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('employees', EmployeeController::class)
        ->only(['index', 'show', 'update']);

    Route::apiResource('business-trips', BusinessTripController::class)
        ->parameters(['business-trips' => 'businessTrip'])
        ->only(['index', 'store', 'show', 'update']);

    Route::get('/dashboard', DashboardController::class);

    Route::get('/leave-requests', [LeaveRequestController::class, 'index']);

    Route::post('/leave-requests', [LeaveRequestController::class, 'store']);

    Route::get('/leave-requests/{leaveRequest}', [
        LeaveRequestController::class,
        'show',
    ]);

    Route::patch('/leave-requests/{leaveRequest}/review', [
        LeaveRequestController::class,
        'review',
    ]);
});
