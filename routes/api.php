<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public. Login throttling lives in App\Http\Requests\Api\V1\LoginRequest (email|ip, 5 attempts).
    Route::post('login', [AuthController::class, 'login'])->name('api.v1.login');
    Route::post('register', [AuthController::class, 'register'])->name('api.v1.register');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me'])->name('api.v1.me');
        Route::post('logout', [AuthController::class, 'logout'])->name('api.v1.logout');
        Route::post('logout-all', [AuthController::class, 'logoutAll'])->name('api.v1.logout-all');

        Route::get('users/available', [UserController::class, 'available'])->name('api.v1.users.available');
    });
});
