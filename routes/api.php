<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompletacioneController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ObjetiveController;
use App\Http\Controllers\PlantillaPerfilController;
use App\Http\Controllers\TareaController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

// Auth
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login/google', [AuthController::class, 'google']);

// Plantillas de perfil (públicas)
Route::get('/plantillas-perfil', [PlantillaPerfilController::class, 'index']);
Route::get('/plantillas-perfil/{id}', [PlantillaPerfilController::class, 'show']);
Route::get('/plantillas-perfil/{id}/tareas', [PlantillaPerfilController::class, 'tareas']);

Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', fn ($request) => $request->user()->load('plantillaPerfil'));

    // Usuario sync
    Route::post('/usuarios/sync', [UsuarioController::class, 'sync']);

    // Aplicar plantilla al usuario
    Route::post('/usuarios/aplicar-plantilla', [UsuarioController::class, 'aplicarPlantilla']);

    // Tareas - sync incremental + upsert por UUID
    Route::get('/tareas', [TareaController::class, 'index']);
    Route::post('/tareas', [TareaController::class, 'store']);
    Route::delete('/tareas/{uuid}', [TareaController::class, 'destroy']);

    // Completaciones - sync incremental + upsert por UUID
    Route::get('/completaciones', [CompletacioneController::class, 'index']);
    Route::post('/completaciones', [CompletacioneController::class, 'store']);
    Route::delete('/completaciones/{uuid}', [CompletacioneController::class, 'destroy']);
    Route::post('/completaciones/toggle', [CompletacioneController::class, 'toggle']);

    // Objetivos - sync incremental + upsert por UUID
    Route::get('/objectives', [ObjetiveController::class, 'index']);
    Route::post('/objectives', [ObjetiveController::class, 'store']);
    Route::delete('/objectives/{uuid}', [ObjetiveController::class, 'destroy']);

    // Tasks (hijos de objectives) - upsert por UUID
    Route::post('/objectives/{uuid}/tasks', [ObjetiveController::class, 'syncTasks']);
    Route::delete('/objectives/{uuid}/tasks/{taskUuid}', [ObjetiveController::class, 'destroyTask']);

    // Notificaciones
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/sync', [NotificationController::class, 'sync']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/send-notification', [NotificationController::class, 'send']);
});
