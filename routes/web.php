<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});



use App\Http\Controllers\ProjectController;
Route::resource('projects', ProjectController::class);
