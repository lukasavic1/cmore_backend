<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\TaskController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — v1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    /*
    |----------------------------------------------------------------------
    | Auth (public)
    |----------------------------------------------------------------------
    */
    Route::prefix('auth')->group(function () {
        Route::post('google',  [AuthController::class, 'googleSignIn']);
        Route::get('me',       [AuthController::class, 'me'])->middleware('auth:sanctum');
        Route::post('logout',  [AuthController::class, 'logout'])->middleware('auth:sanctum');
    });

    /*
    |----------------------------------------------------------------------
    | Protected routes — require a valid Sanctum token
    |----------------------------------------------------------------------
    */
    Route::middleware('auth:sanctum')->group(function () {
        // Stats (before resource routes to avoid {task} collision)
        Route::get('stats', [TaskController::class, 'stats']);

        // Must be registered before apiResource so "assignees" is not captured as {task}
        Route::get('tasks/assignees', [TaskController::class, 'assignees'])->name('tasks.assignees');

        // Task resource
        Route::apiResource('tasks', TaskController::class);

        // Status toggle
        Route::patch('tasks/{task}/toggle', [TaskController::class, 'toggle'])
             ->name('tasks.toggle');
    });
});
