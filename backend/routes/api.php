<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TravelOrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Login público
Route::post('/auth/login', [AuthController::class, 'login']);

// Rotas protegidas
Route::middleware('auth:sanctum')->group(function () {
    
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/notifications', function (Request $request) {
        return $request->user()->notifications()->orderByDesc('created_at')->get();
    });

    // Travel orders
    Route::get('/travel-orders', [TravelOrderController::class, 'index']);
    Route::post('/travel-orders', [TravelOrderController::class, 'store']);
    Route::patch('/travel-orders/{order}/status', [TravelOrderController::class, 'updateStatus']);

});

