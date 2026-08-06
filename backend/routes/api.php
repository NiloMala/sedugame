<?php

use App\Http\Controllers\Api\Admin\StudentPasswordController;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

// Autenticação — ver docs/03-api-contract.md
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:6,1');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:6,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Administração (Secretaria/Escola) — CRUDs completos entram no próximo sprint.
    Route::middleware('role:school_admin,department_admin')->prefix('admin')->group(function () {
        Route::post('/students/{student}/reset-password', StudentPasswordController::class);
    });
});
