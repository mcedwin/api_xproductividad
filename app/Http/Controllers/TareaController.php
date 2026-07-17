<?php

namespace App\Http\Controllers;

use App\Models\Completacione;
use App\Models\Tarea;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TareaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $since = $request->query('since');

        $query = Tarea::where('user_id', $user->id)
            ->whereNull('deleted_at');

        if ($since) {
            $query->where('updated_at', '>', $since);
        }

        if ($request->has('active')) {
            $query->where('activo', $request->boolean('active'));
        }

        if ($request->has('periodicity')) {
            $query->where('periodicidad', $request->periodicity);
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
            'title' => 'required|string|max:255',
            'periodicity' => 'sometimes|in:daily,weekly,custom,fixed,monthly',
            'days_of_week' => 'sometimes|array|nullable',
            'days_of_week.*' => 'integer|between:1,7',
            'day_of_month' => 'sometimes|integer|between:1,31|nullable',
            'fixed_date' => 'sometimes|date|nullable',
            'time' => 'sometimes|string|nullable|date_format:H:i',
            'priority' => 'sometimes|in:none,medium,high',
            'active' => 'sometimes|boolean',
            'device_id' => 'sometimes|string|max:36|nullable',
            'updated_at' => 'sometimes|string|nullable',
        ]);

        $uuid = $validated['id'];
        $clientUpdatedAt = $validated['updated_at'] ?? null;
        $now = gmdate('Y-m-d H:i:s');
        $user = $request->user();

        $existing = Tarea::where('uuid', $uuid)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            if ($clientUpdatedAt !== null && $clientUpdatedAt < $existing->updated_at) {
                return response()->json(['data' => $existing]);
            }

            $existing->update([
                'titulo' => $validated['title'] ?? $existing->titulo,
                'periodicidad' => $validated['periodicity'] ?? $existing->periodicidad,
                'dias_semana' => $validated['days_of_week'] ?? $existing->dias_semana,
                'dia_mes' => array_key_exists('day_of_month', $validated) ? $validated['day_of_month'] : $existing->dia_mes,
                'fecha_fija' => $validated['fixed_date'] ?? $existing->fecha_fija,
                'hora' => $validated['time'] ?? $existing->hora,
                'prioridad' => $validated['priority'] ?? $existing->prioridad,
                'activo' => $validated['active'] ?? $existing->activo,
                'updated_at' => $clientUpdatedAt ?? $now,
                'sync_status' => 'synced',
                'device_id' => $validated['device_id'] ?? $existing->device_id,
            ]);

            return response()->json(['data' => $existing->fresh()]);
        }

        $tarea = Tarea::create([
            'uuid' => $uuid,
            'user_id' => $user->id,
            'titulo' => $validated['title'],
            'periodicidad' => $validated['periodicity'] ?? 'daily',
            'dias_semana' => $validated['days_of_week'] ?? null,
            'dia_mes' => $validated['day_of_month'] ?? null,
            'fecha_fija' => $validated['fixed_date'] ?? null,
            'hora' => $validated['time'] ?? null,
            'prioridad' => $validated['priority'] ?? 'none',
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
            ->where('user_id', $user->id)
            ->first();

        if (! $tarea) {
            return response()->json(null, 204);
        }

        $now = gmdate('Y-m-d H:i:s');

        Completacione::where('task_id', $tarea->id)
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
}
