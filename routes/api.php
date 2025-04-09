<?php
use App\Http\Controllers\Api\AuthController;


Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'apiRegister']);
    Route::post('/login', [AuthController::class, 'apiLogin']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'apiLogout']);
        Route::get('/user', [AuthController::class, 'apiUser']);
    });
});
