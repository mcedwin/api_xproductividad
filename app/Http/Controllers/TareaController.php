<?php

namespace App\Http\Controllers;

use App\Models\Completacione;
use App\Models\Tarea;
use App\Models\TareaPlantilla;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TareaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $since = $request->query('since');

        $query = Tarea::where('usuario_id', $user->id)
            ->whereNull('deleted_at');

        if ($since) {
            $query->where('updated_at', '>', $since);
        }

        if ($request->has('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

        if ($request->has('periodicidad')) {
            $query->where('periodicidad', $request->periodicidad);
        }

        if ($request->boolean('with_today')) {
            $query->with('completadaHoy');
        }

        $tareas = $query->orderBy('updated_at', 'asc')->get();

        return response()->json(['data' => $tareas]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'required|string|max:36',
            'titulo' => 'required|string|max:255',
            'periodicidad' => 'sometimes|in:daily,weekly,custom,fixed,monthly',
            'dias_semana' => 'sometimes|array|nullable',
            'dias_semana.*' => 'integer|between:1,7',
            'dia_mes' => 'sometimes|integer|between:1,31|nullable',
            'fecha_fija' => 'sometimes|date|nullable',
            'hora' => 'sometimes|string|nullable|date_format:H:i',
            'prioridad' => 'sometimes|in:none,medium,high',
            'activo' => 'sometimes|boolean',
            'device_id' => 'sometimes|string|max:36|nullable',
            'updated_at' => 'sometimes|string|nullable',
        ]);

        $uuid = $validated['id'];
        $clientUpdatedAt = $validated['updated_at'] ?? null;
        $now = gmdate('Y-m-d H:i:s');
        $user = $request->user();

        $existing = Tarea::where('uuid', $uuid)
            ->where('usuario_id', $user->id)
            ->first();

        if ($existing) {
            // Last-write-wins: si el servidor tiene una versión más reciente, la conserva.
            if ($clientUpdatedAt !== null && $clientUpdatedAt < $existing->updated_at) {
                return response()->json(['data' => $existing]);
            }

            $existing->update([
                'titulo' => $validated['titulo'] ?? $existing->titulo,
                'periodicidad' => $validated['periodicidad'] ?? $existing->periodicidad,
                'dias_semana' => $validated['dias_semana'] ?? $existing->dias_semana,
                'dia_mes' => array_key_exists('dia_mes', $validated) ? $validated['dia_mes'] : $existing->dia_mes,
                'fecha_fija' => $validated['fecha_fija'] ?? $existing->fecha_fija,
                'hora' => $validated['hora'] ?? $existing->hora,
                'prioridad' => $validated['prioridad'] ?? $existing->prioridad,
                'activo' => $validated['activo'] ?? $existing->activo,
                'updated_at' => $clientUpdatedAt ?? $now,
                'sync_status' => 'synced',
                'device_id' => $validated['device_id'] ?? $existing->device_id,
            ]);

            return response()->json(['data' => $existing->fresh()]);
        }

        $tarea = Tarea::create([
            'uuid' => $uuid,
            'usuario_id' => $user->id,
            'titulo' => $validated['titulo'],
            'periodicidad' => $validated['periodicidad'] ?? 'daily',
            'dias_semana' => $validated['dias_semana'] ?? null,
            'dia_mes' => $validated['dia_mes'] ?? null,
            'fecha_fija' => $validated['fecha_fija'] ?? null,
            'hora' => $validated['hora'] ?? null,
            'prioridad' => $validated['prioridad'] ?? 'none',
            'created_at' => $now,
            'updated_at' => $clientUpdatedAt ?? $now,
            'sync_status' => 'synced',
            'device_id' => $validated['device_id'] ?? null,
        ]);

        return response()->json(['data' => $tarea], 201);
    }

    public function destroy(string $uuid): JsonResponse
    {
        $user = request()->user();
        $tarea = Tarea::where('uuid', $uuid)
            ->where('usuario_id', $user->id)
            ->first();

        if (! $tarea) {
            return response()->json(null, 204);
        }

        $now = gmdate('Y-m-d H:i:s');

        Completacione::where('tarea_id', $tarea->id)
            ->whereNull('deleted_at')
            ->update([
                'deleted_at' => $now,
                'updated_at' => $now,
                'sync_status' => 'synced',
            ]);

        $tarea->update([
            'deleted_at' => $now,
            'updated_at' => $now,
            'sync_status' => 'synced',
        ]);

        return response()->json(null, 204);
    }

    public function copiarDePlantilla(Request $request): JsonResponse
    {
        $request->validate([
            'plantilla_id' => 'required|string|exists:plantillas_perfil,id',
        ]);

        $user = $request->user();
        $tareasPlantilla = TareaPlantilla::where('plantilla_id', $request->plantilla_id)
            ->orderBy('orden')
            ->get();

        $now = gmdate('Y-m-d H:i:s');
        $tareas = $tareasPlantilla->map(function ($tp) use ($user, $now) {
            return Tarea::create([
                'uuid' => (string) Str::uuid(),
                'usuario_id' => $user->id,
                'titulo' => $tp->titulo,
                'periodicidad' => $tp->periodicidad,
                'dias_semana' => $tp->dias_semana,
                'dia_mes' => $tp->dia_mes ?? null,
                'prioridad' => $tp->prioridad,
                'created_at' => $now,
                'updated_at' => $now,
                'sync_status' => 'synced',
            ]);
        });

        if ($user->tipo_perfil !== $request->plantilla_id) {
            $user->update(['tipo_perfil' => $request->plantilla_id]);
        }

        return response()->json(['data' => $tareas], 201);
    }
}
