<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TravelOrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Login público
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

// Rotas protegidas
Route::middleware('auth:sanctum')->group(function () {
    
    Route::post('/auth/profile', [AuthController::class, 'updateProfile']);
    
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/notifications', function (Request $request) {
        return $request->user()->notifications()->orderByDesc('created_at')->get();
    });
    
    Route::post('/notifications/{id}/read', function (Request $request, $id) {
        $notification = $request->user()->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        return response()->json(['status' => 'ok']);
    });


    // Travel orders
    Route::get('/travel-orders', [TravelOrderController::class, 'index']);
    Route::post('/travel-orders', [TravelOrderController::class, 'store']);
    Route::patch('/travel-orders/{order}/status', [TravelOrderController::class, 'updateStatus']);

});

