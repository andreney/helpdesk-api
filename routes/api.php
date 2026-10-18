<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\TicketCommentController;
use App\Http\Controllers\Api\TicketController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::get('/categories', [CategoryController::class, 'index']);

        Route::apiResource('tickets', TicketController::class)
            ->only(['index', 'show', 'store']);

        Route::patch('/tickets/{ticket}/status', [TicketController::class, 'updateStatus']);
        Route::post('/tickets/{ticket}/comments', [TicketCommentController::class, 'store']);
    });
});
