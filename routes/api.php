<?php


use App\Http\Controllers\ProjectController;
Route::apiResource('projects', ProjectController::class);

use App\Http\Controllers\SubheaderController;
Route::apiResource('subheaders', SubheaderController::class);
