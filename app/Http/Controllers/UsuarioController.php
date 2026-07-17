<?php

namespace App\Http\Controllers;

use App\Models\Tarea;
use App\Models\TareaPlantilla;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UsuarioController extends Controller
{
    public function aplicarPlantilla(Request $request): JsonResponse
    {
        $request->validate([
            'template_id' => 'required|string|exists:app_profile_templates,id',
        ]);

        $user = $request->user();
        $tareasPlantilla = TareaPlantilla::where('template_id', $request->template_id)
            ->orderBy('orden')
            ->get();

        $now = gmdate('Y-m-d H:i:s');

        $tareasPlantilla->each(function ($tp) use ($user, $now) {
            Tarea::create([
                'uuid' => (string) Str::uuid(),
                'user_id' => $user->id,
                'titulo' => $tp->titulo,
                'periodicidad' => $tp->periodicidad,
                'dias_semana' => $tp->dias_semana,
                'prioridad' => $tp->prioridad,
                'created_at' => $now,
                'updated_at' => $now,
                'sync_status' => 'synced',
            ]);
        });

        if ($user->profile_template_id !== $request->template_id) {
            $user->update(['profile_template_id' => $request->template_id]);
        }

        return response()->json(['message' => 'Plantilla aplicada correctamente']);
    }

    public function sync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'required|string|max:36',
            'nombre' => 'sometimes|string|max:255',
            'email' => 'sometimes|email',
            'avatar' => 'sometimes|string|nullable',
            'profile_template_id' => 'sometimes|string|nullable',
            'device_id' => 'sometimes|string|max:36|nullable',
        ]);

        $uuid = $validated['id'];
        $now = gmdate('Y-m-d H:i:s');
        $user = $request->user();

        $existing = \App\Models\Usuario::where('uuid', $uuid)->first();

        if ($existing) {
            $existing->update(array_filter([
                'nombre' => $validated['nombre'] ?? null,
                'email' => $validated['email'] ?? null,
                'avatar' => $validated['avatar'] ?? null,
                'profile_template_id' => $validated['profile_template_id'] ?? null,
                'updated_at' => $now,
                'sync_status' => 'synced',
                'device_id' => $validated['device_id'] ?? null,
            ], fn ($v) => $v !== null));

            return response()->json(['data' => $existing->fresh()]);
        }

        $usuario = \App\Models\Usuario::create([
            'uuid' => $uuid,
            'nombre' => $validated['nombre'] ?? $user->nombre,
            'email' => $validated['email'] ?? $user->email,
            'avatar' => $validated['avatar'] ?? null,
            'profile_template_id' => $validated['profile_template_id'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
            'sync_status' => 'synced',
            'device_id' => $validated['device_id'] ?? null,
        ]);

        return response()->json(['data' => $usuario], 201);
    }
}
