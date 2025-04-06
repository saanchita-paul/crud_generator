<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');


use App\Http\Controllers\ProjectController;
Route::resource('projects', ProjectController::class);

use App\Http\Controllers\TaskController;
Route::resource('projects/{project}/tasks', TaskController::class);



use App\Http\Controllers\DeveloperController;
Route::resource('developers', DeveloperController::class);

//Route::resource('developers/{developer}/projects', ProjectController::class);
