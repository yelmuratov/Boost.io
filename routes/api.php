<?php

use App\Http\Controllers\API\Auth\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Admin\SmmProviderController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/refresh', [AuthController::class, 'refresh']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'user']);
});


//Admin routes
Route::middleware(['auth:sanctum'])->prefix('admin/providers')->group(function () {
    // CRUD operations
    Route::get('/', [SmmProviderController::class, 'index']);
    Route::post('/', [SmmProviderController::class, 'store']);
    Route::get('/{id}', [SmmProviderController::class, 'show']);
    Route::put('/{id}', [SmmProviderController::class, 'update']);
    Route::delete('/{id}', [SmmProviderController::class, 'destroy']);

    // Actions
    Route::post('/{id}/sync-services', [SmmProviderController::class, 'syncServices']);
    Route::post('/{id}/sync-balance', [SmmProviderController::class, 'syncBalance']);
    Route::post('/{id}/test-connection', [SmmProviderController::class, 'testConnection']);
    Route::post('/{id}/toggle-active', [SmmProviderController::class, 'toggleActive']);
    Route::get('/{id}/stats', [SmmProviderController::class, 'stats']);
});

// Public services API (reads from database, filtered by active providers)
Route::middleware(['auth:sanctum'])->prefix('services')->group(function () {
    Route::get('/', [SmmProviderController::class, 'getAllServices']);
    Route::get('/categories', [SmmProviderController::class, 'getCategories']);
});

