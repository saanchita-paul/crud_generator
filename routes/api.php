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

use App\Http\Controllers\Api\ProjectController;
Route::middleware('auth:sanctum')->apiResource('projects', ProjectController::class);

use App\Http\Controllers\Api\TaskController;
Route::middleware('auth:sanctum')->apiResource('projects/{project}/tasks', TaskController::class);

use App\Http\Controllers\Api\DeveloperController;
Route::middleware('auth:sanctum')->apiResource('developers', DeveloperController::class);

Route::middleware('auth:sanctum')->apiResource('developers/{developer}/projects', ProjectController::class);
