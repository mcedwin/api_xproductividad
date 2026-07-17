<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompletacioneController;
use App\Http\Controllers\PlantillaPerfilController;
use App\Http\Controllers\TareaController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

// Auth
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login/google', [AuthController::class, 'google']);

// Profile templates (public)
Route::get('/profile-templates', [PlantillaPerfilController::class, 'index']);
Route::get('/profile-templates/{id}', [PlantillaPerfilController::class, 'show']);
Route::get('/profile-templates/{id}/tasks', [PlantillaPerfilController::class, 'tareas']);

Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', fn ($request) => $request->user()->load('plantillaPerfil'));

    // User sync
    Route::post('/users/sync', [UsuarioController::class, 'sync']);

    // Apply template to user
    Route::post('/users/apply-template', [UsuarioController::class, 'aplicarPlantilla']);

    // Tasks - incremental sync + upsert by UUID
    Route::get('/tasks', [TareaController::class, 'index']);
    Route::post('/tasks', [TareaController::class, 'store']);
    Route::delete('/tasks/{uuid}', [TareaController::class, 'destroy']);

    // Completions - incremental sync + upsert by UUID
    Route::get('/completions', [CompletacioneController::class, 'index']);
    Route::post('/completions', [CompletacioneController::class, 'store']);
    Route::delete('/completions/{uuid}', [CompletacioneController::class, 'destroy']);
    Route::post('/completions/toggle', [CompletacioneController::class, 'toggle']);
});
