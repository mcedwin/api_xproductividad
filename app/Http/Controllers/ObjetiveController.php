<?php

namespace App\Http\Controllers;

use App\Models\Objetive;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ObjetiveController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $since = $request->query('since');

        $query = Objetive::where('user_id', $user->id)->with('tasks');

        if ($since) {
            $query->where('updated_at', '>', $since);
        }

        $objetivos = $query->orderBy('updated_at', 'asc')->get();

        return response()->json(['data' => $objetivos]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'required|string|max:36',
            'title' => 'required|string|max:255',
            'description' => 'sometimes|string',
            'end_date' => 'sometimes|date|nullable',
            'status' => 'sometimes|in:active,completed,paused',
            'device_id' => 'sometimes|string|max:36|nullable',
        ]);

        $uuid = $validated['id'];
        $now = gmdate('Y-m-d H:i:s');
        $user = $request->user();

        $existing = Objetive::where('uuid', $uuid)->first();

        if ($existing) {
            $existing->update([
                'title' => $validated['title'] ?? $existing->title,
                'description' => $validated['description'] ?? $existing->description,
                'end_date' => $validated['end_date'] ?? $existing->end_date,
                'status' => $validated['status'] ?? $existing->status,
                'updated_at' => $now,
                'sync_status' => 'synced',
                'device_id' => $validated['device_id'] ?? $existing->device_id,
            ]);

            return response()->json(['data' => $existing->fresh()->load('tasks')]);
        }

        $objetivo = Objetive::create([
            'uuid' => $uuid,
            'user_id' => $user->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'start_date' => now()->toDateString(),
            'end_date' => $validated['end_date'] ?? null,
            'status' => $validated['status'] ?? 'active',
            'created_at' => $now,
            'updated_at' => $now,
            'sync_status' => 'synced',
            'device_id' => $validated['device_id'] ?? null,
        ]);

        return response()->json(['data' => $objetivo->load('tasks')], 201);
    }

    public function destroy(string $uuid): JsonResponse
    {
        $user = request()->user();

        $objetivo = Objetive::where('uuid', $uuid)
            ->where('user_id', $user->id)
            ->first();

        if (! $objetivo) {
            return response()->json(null, 204);
        }

        $now = gmdate('Y-m-d H:i:s');

        Task::where('objective_id', $objetivo->id)
            ->whereNull('deleted_at')
            ->update([
                'deleted_at' => $now,
                'updated_at' => $now,
                'sync_status' => 'synced',
            ]);

        $objetivo->update([
            'deleted_at' => $now,
            'updated_at' => $now,
            'sync_status' => 'synced',
        ]);

        return response()->json(null, 204);
    }

    public function syncTasks(Request $request, string $objetiveUuid): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'required|string|max:36',
            'title' => 'required|string|max:255',
            'status' => 'sometimes|in:pending,in_progress,completed',
            'expected_minutes' => 'sometimes|integer|nullable',
            'scheduled_at' => 'sometimes|date|nullable',
            'completed_at' => 'sometimes|date|nullable',
            'device_id' => 'sometimes|string|max:36|nullable',
        ]);

        $user = $request->user();
        $objetivo = Objetive::where('uuid', $objetiveUuid)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $uuid = $validated['id'];
        $now = gmdate('Y-m-d H:i:s');

        $existing = Task::where('uuid', $uuid)->first();

        if ($existing) {
            $existing->update([
                'title' => $validated['title'] ?? $existing->title,
                'status' => $validated['status'] ?? $existing->status,
                'expected_minutes' => $validated['expected_minutes'] ?? $existing->expected_minutes,
                'scheduled_at' => $validated['scheduled_at'] ?? $existing->scheduled_at,
                'completed_at' => $validated['completed_at'] ?? $existing->completed_at,
                'updated_at' => $now,
                'sync_status' => 'synced',
                'device_id' => $validated['device_id'] ?? $existing->device_id,
            ]);

            $objetivo->update(['updated_at' => $now, 'sync_status' => 'synced']);

            return response()->json(['data' => $existing->fresh()]);
        }

        $task = Task::create([
            'uuid' => $uuid,
            'objective_id' => $objetivo->id,
            'title' => $validated['title'],
            'status' => $validated['status'] ?? 'pending',
            'expected_minutes' => $validated['expected_minutes'] ?? null,
            'scheduled_at' => $validated['scheduled_at'] ?? null,
            'completed_at' => $validated['completed_at'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
            'sync_status' => 'synced',
            'device_id' => $validated['device_id'] ?? null,
        ]);

        $objetivo->update(['updated_at' => $now, 'sync_status' => 'synced']);

        return response()->json(['data' => $task], 201);
    }

    public function destroyTask(string $objetiveUuid, string $taskUuid): JsonResponse
    {
        $user = request()->user();

        $objetivo = Objetive::where('uuid', $objetiveUuid)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $task = Task::where('uuid', $taskUuid)
            ->where('objective_id', $objetivo->id)
            ->first();

        if (! $task) {
            return response()->json(null, 204);
        }

        $now = gmdate('Y-m-d H:i:s');
        $task->update([
            'deleted_at' => $now,
            'updated_at' => $now,
            'sync_status' => 'synced',
        ]);

        $objetivo->update(['updated_at' => $now, 'sync_status' => 'synced']);

        return response()->json(null, 204);
    }
}
