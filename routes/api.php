<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TicketCommentController;
use App\Http\Controllers\Api\TicketController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/tickets', [TicketController::class, 'index']);
    Route::get('/tickets/{id}', [TicketController::class, 'show']);
    Route::post('/tickets', [TicketController::class, 'store']);

    Route::post(
        '/tickets/{ticket}/comments',
        [TicketCommentController::class, 'store']
    );

    Route::patch(
        '/tickets/{ticket}/status',
        [TicketController::class, 'updateStatus']
    );
});
