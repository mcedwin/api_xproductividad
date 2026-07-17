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

        $query = Completacione::where('user_id', $user->id)
            ->whereNull('deleted_at');

        if ($since) {
            $query->where('updated_at', '>', $since);
        }

        if ($request->has('date')) {
            $query->whereDate('fecha_completada', $request->date);
        }

        if ($request->has('from')) {
            $query->whereDate('fecha_completada', '>=', $request->from);
        }

        if ($request->has('to')) {
            $query->whereDate('fecha_completada', '<=', $request->to);
        }

        $completaciones = $query->orderBy('updated_at', 'asc')
            ->with(['tarea:id,uuid'])
            ->get()
            ->map(function ($c) {
                if ($c->tarea) {
                    $c->task_id = $c->tarea->uuid;
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
            'task_id' => 'required|string',
            'completed_date' => 'sometimes|date',
            'device_id' => 'sometimes|string|max:36|nullable',
        ]);

        $uuid = $validated['id'];
        $now = gmdate('Y-m-d H:i:s');
        $user = $request->user();

        $tarea = Tarea::where('user_id', $user->id)
            ->where('uuid', $validated['task_id'])
            ->firstOrFail();

        $fecha = $validated['completed_date'] ?? today()->toDateString();

        $existing = Completacione::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->where('task_id', $tarea->id)
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
                'user_id' => $user->id,
                'task_id' => $tarea->id,
                'fecha_completada' => $fecha,
                'created_at' => $now,
                'updated_at' => $now,
                'sync_status' => 'synced',
                'device_id' => $validated['device_id'] ?? null,
            ]);

            return response()->json(['data' => $completacion], 201);
        } catch (\Throwable $e) {
            $existing = Completacione::withoutGlobalScopes()
                ->where('user_id', $user->id)
                ->where('task_id', $tarea->id)
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
            ->where('user_id', $user->id)
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
            'task_id' => 'required|string',
            'completed_date' => 'sometimes|date',
            'updated_at' => 'sometimes|string|nullable',
        ]);

        $user = $request->user();
        $fecha = $validated['completed_date'] ?? today()->toDateString();
        $clientUpdatedAt = $validated['updated_at'] ?? null;
        $now = gmdate('Y-m-d H:i:s');

        $tarea = Tarea::where('user_id', $user->id)
            ->where('uuid', $validated['task_id'])
            ->firstOrFail();

        $completacion = Completacione::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->where('task_id', $tarea->id)
            ->where('fecha_completada', $fecha)
            ->first();

        if ($completacion) {
            if ($clientUpdatedAt !== null && $clientUpdatedAt < $completacion->updated_at) {
                return response()->json([
                    'completed' => $completacion->deleted_at === null,
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

                return response()->json(['completed' => false], 200);
            }

            $completacion->update([
                'deleted_at' => null,
                'updated_at' => $newUpdatedAt,
                'sync_status' => 'synced',
            ]);

            return response()->json(['completed' => true], 200);
        }

        Completacione::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $user->id,
            'task_id' => $tarea->id,
            'fecha_completada' => $fecha,
            'created_at' => $now,
            'updated_at' => $clientUpdatedAt ?? $now,
            'sync_status' => 'synced',
        ]);

        return response()->json(['completed' => true], 200);
    }
}
