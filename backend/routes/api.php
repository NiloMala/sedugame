<?php

use App\Http\Controllers\Api\Admin\GradeController;
use App\Http\Controllers\Api\Admin\SchoolClassController;
use App\Http\Controllers\Api\Admin\SchoolController;
use App\Http\Controllers\Api\Admin\SchoolYearController;
use App\Http\Controllers\Api\Admin\SettingsController;
use App\Http\Controllers\Api\Admin\SkillController;
use App\Http\Controllers\Api\Admin\StudentPasswordController;
use App\Http\Controllers\Api\Admin\SubjectController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StudentActivityController;
use Illuminate\Support\Facades\Route;

// Autenticação — ver docs/03-api-contract.md
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:6,1');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:6,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Aluno
    Route::middleware('role:student')->group(function () {
        Route::get('/activities', [StudentActivityController::class, 'index']);
    });

    // Administração (Secretaria/Escola). Escopo por escola x rede é resolvido
    // dentro de cada controller (ver Api\Admin\Concerns\ScopesToSchool) — o
    // middleware aqui só garante que é algum tipo de admin.
    Route::middleware('role:school_admin,department_admin,super_admin')->prefix('admin')->group(function () {
        Route::post('/students/{student}/reset-password', StudentPasswordController::class);

        Route::apiResource('schools', SchoolController::class);
        Route::apiResource('classes', SchoolClassController::class)->parameters(['classes' => 'class']);
        Route::apiResource('grades', GradeController::class);
        Route::apiResource('school-years', SchoolYearController::class);
        Route::apiResource('subjects', SubjectController::class);
        Route::apiResource('skills', SkillController::class);
        Route::apiResource('users', UserController::class);

        Route::get('/settings', [SettingsController::class, 'show']);
        Route::put('/settings', [SettingsController::class, 'update']);
    });
});
