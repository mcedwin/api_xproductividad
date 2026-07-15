<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    protected string $modelClass;

    protected string $routeName;

    protected array $childRelations = [];

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $since = $request->query('since');

        $query = $this->modelClass::where($this->getUserColumn(), $user->id);

        if ($since) {
            $query->where('updated_at', '>', $since);
        }

        $models = $query->orderBy('updated_at', 'asc')->get();

        return response()->json(['data' => $models]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->all();
        $user = $request->user();

        $data[$this->getUserColumn()] = $user->id;
        $data['device_id'] = $request->header('X-Device-Id', $data['device_id'] ?? null);

        $uuid = $data['id'] ?? $data['uuid'] ?? null;

        if (! $uuid) {
            return response()->json(['error' => 'UUID requerido en campo id'], 422);
        }

        $now = gmdate('Y-m-d H:i:s');
        $existing = $this->modelClass::where('uuid', $uuid)->first();

        if ($existing) {
            $updateData = collect($data)
                ->except(['id', 'created_at', 'sync_status'])
                ->toArray();
            $updateData['updated_at'] = $now;
            $updateData['sync_status'] = 'synced';

            $existing->update($updateData);

            return response()->json(['data' => $existing->fresh()]);
        }

        $createData = collect($data)
            ->except(['id'])
            ->toArray();
        $createData['uuid'] = $uuid;
        $createData['created_at'] = $data['created_at'] ?? $now;
        $createData['updated_at'] = $now;
        $createData['sync_status'] = 'synced';

        $model = $this->modelClass::create($createData);

        return response()->json(['data' => $model], 201);
    }

    public function destroy(string $uuid): JsonResponse
    {
        $user = request()->user();
        $model = $this->modelClass::where('uuid', $uuid)
            ->where($this->getUserColumn(), $user->id)
            ->first();

        if (! $model) {
            return response()->json(null, 204);
        }

        $now = gmdate('Y-m-d H:i:s');
        $model->update([
            'deleted_at' => $now,
            'updated_at' => $now,
            'sync_status' => 'synced',
        ]);

        foreach ($this->childRelations as $relation) {
            $model->{$relation}->each(function ($child) {
                $now = gmdate('Y-m-d H:i:s');
                $child->update([
                    'deleted_at' => $now,
                    'updated_at' => $now,
                    'sync_status' => 'synced',
                ]);
            });
        }

        return response()->json(null, 204);
    }

    protected function getUserColumn(): string
    {
        return 'user_id';
    }

    protected function modelClass(): string
    {
        return $this->modelClass;
    }
}
