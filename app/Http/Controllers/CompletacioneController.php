<?php

namespace App\Http\Controllers;

use App\Models\Completacione;
use App\Models\Tarea;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompletacioneController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $since = $request->query('since');

        $query = Completacione::where('usuario_id', $user->id)
            ->whereNull('deleted_at');

        if ($since) {
            $query->where('updated_at', '>', $since);
        }

        if ($request->has('fecha')) {
            $query->whereDate('fecha_completada', $request->fecha);
        }

        if ($request->has('desde')) {
            $query->whereDate('fecha_completada', '>=', $request->desde);
        }

        if ($request->has('hasta')) {
            $query->whereDate('fecha_completada', '<=', $request->hasta);
        }

        $completaciones = $query->orderBy('updated_at', 'asc')
            ->with(['tarea:id,uuid'])
            ->get()
            ->map(function ($c) {
                if ($c->tarea) {
                    $c->tarea_id = $c->tarea->uuid;
                }
                unset($c->tarea);
                return $c;
            });

        return response()->json(['data' => $completaciones]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'required|string|max:36',
            'tarea_id' => 'required|string',
            'fecha_completada' => 'sometimes|date',
            'device_id' => 'sometimes|string|max:36|nullable',
        ]);

        $uuid = $validated['id'];
        $now = gmdate('Y-m-d H:i:s');
        $user = $request->user();

        $tarea = Tarea::where('usuario_id', $user->id)
            ->where('uuid', $validated['tarea_id'])
            ->firstOrFail();

        $fecha = $validated['fecha_completada'] ?? today()->toDateString();

        $existing = Completacione::withoutGlobalScopes()
            ->where('usuario_id', $user->id)
            ->where('tarea_id', $tarea->id)
            ->where('fecha_completada', $fecha)
            ->first();

        if ($existing) {
            $existing->update([
                'uuid' => $uuid,
                'deleted_at' => null,
                'updated_at' => $now,
                'sync_status' => 'synced',
                'device_id' => $validated['device_id'] ?? $existing->device_id,
            ]);

            return response()->json(['data' => $existing->fresh()]);
        }

        try {
            $completacion = Completacione::create([
                'uuid' => $uuid,
                'usuario_id' => $user->id,
                'tarea_id' => $tarea->id,
                'fecha_completada' => $fecha,
                'created_at' => $now,
                'updated_at' => $now,
                'sync_status' => 'synced',
                'device_id' => $validated['device_id'] ?? null,
            ]);

            return response()->json(['data' => $completacion], 201);
        } catch (\Throwable $e) {
            $existing = Completacione::withoutGlobalScopes()
                ->where('usuario_id', $user->id)
                ->where('tarea_id', $tarea->id)
                ->where('fecha_completada', $fecha)
                ->first();

            if ($existing) {
                $existing->update([
                    'uuid' => $uuid,
                    'deleted_at' => null,
                    'updated_at' => $now,
                    'sync_status' => 'synced',
                    'device_id' => $validated['device_id'] ?? $existing->device_id,
                ]);

                return response()->json(['data' => $existing->fresh()]);
            }

            throw $e;
        }
    }

    public function destroy(string $uuid): JsonResponse
    {
        $user = request()->user();

        $completacion = Completacione::where('uuid', $uuid)
            ->where('usuario_id', $user->id)
            ->first();

        if (! $completacion) {
            return response()->json(null, 204);
        }

        $now = gmdate('Y-m-d H:i:s');
        $completacion->update([
            'deleted_at' => $now,
            'updated_at' => $now,
            'sync_status' => 'synced',
        ]);

        return response()->json(null, 204);
    }

    public function toggle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tarea_id' => 'required|string',
            'fecha_completada' => 'sometimes|date',
            'updated_at' => 'sometimes|string|nullable',
        ]);

        $user = $request->user();
        $fecha = $validated['fecha_completada'] ?? today()->toDateString();
        $clientUpdatedAt = $validated['updated_at'] ?? null;
        $now = gmdate('Y-m-d H:i:s');

        $tarea = Tarea::where('usuario_id', $user->id)
            ->where('uuid', $validated['tarea_id'])
            ->firstOrFail();

        $completacion = Completacione::withoutGlobalScopes()
            ->where('usuario_id', $user->id)
            ->where('tarea_id', $tarea->id)
            ->where('fecha_completada', $fecha)
            ->first();

        if ($completacion) {
            // Last-write-wins: conservar el estado del servidor si es más reciente.
            if ($clientUpdatedAt !== null && $clientUpdatedAt < $completacion->updated_at) {
                return response()->json([
                    'completada' => $completacion->deleted_at === null,
                    'data' => $completacion,
                ], 200);
            }

            $newUpdatedAt = $clientUpdatedAt ?? $now;

            if ($completacion->deleted_at === null) {
                $completacion->update([
                    'deleted_at' => $now,
                    'updated_at' => $newUpdatedAt,
                    'sync_status' => 'synced',
                ]);

                return response()->json(['completada' => false], 200);
            }

            $completacion->update([
                'deleted_at' => null,
                'updated_at' => $newUpdatedAt,
                'sync_status' => 'synced',
            ]);

            return response()->json(['completada' => true], 200);
        }

        Completacione::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'usuario_id' => $user->id,
            'tarea_id' => $tarea->id,
            'fecha_completada' => $fecha,
            'created_at' => $now,
            'updated_at' => $clientUpdatedAt ?? $now,
            'sync_status' => 'synced',
        ]);

        return response()->json(['completada' => true], 200);
    }

    public function hoy(): JsonResponse
    {
        $user = request()->user();
        $fecha = today()->toDateString();

        $completadas = Completacione::where('usuario_id', $user->id)
            ->whereDate('fecha_completada', $fecha)
            ->whereNull('deleted_at')
            ->pluck('tarea_id');

        $tareas = Tarea::where('usuario_id', $user->id)
            ->where('activo', true)
            ->whereNull('deleted_at')
            ->get()
            ->map(function ($tarea) use ($completadas) {
                $tarea->completada_hoy = $completadas->contains($tarea->id);

                return $tarea;
            });

        return response()->json([
            'fecha' => $fecha,
            'tareas' => $tareas,
        ]);
    }
}
