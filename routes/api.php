<?php

use App\Http\Controllers\Api\ApiAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (Mobile App)
|--------------------------------------------------------------------------
*/

// Public auth routes (no token required)
Route::prefix('auth')->group(function () {
    Route::post('/validate-email', [ApiAuthController::class, 'validateEmail']);
    Route::post('/login', [ApiAuthController::class, 'login']);
    Route::post('/register', [ApiAuthController::class, 'register']);
    Route::post('/forgot-password', [ApiAuthController::class, 'forgotPassword']);
    Route::post('/validate-business-code', [ApiAuthController::class, 'validateBusinessCode']);
});

// Protected routes (token required)
Route::middleware('auth:sanctum')->prefix('auth')->group(function () {
    Route::post('/logout', [ApiAuthController::class, 'logout']);
    Route::post('/unlock', [ApiAuthController::class, 'unlock']);
    Route::get('/user', [ApiAuthController::class, 'user']);
});
