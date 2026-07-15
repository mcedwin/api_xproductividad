<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait Syncable
{
    public static function bootSyncable(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            $now = gmdate('Y-m-d H:i:s');
            if (empty($model->created_at)) {
                $model->created_at = $now;
            }
            if (empty($model->updated_at)) {
                $model->updated_at = $now;
            }
            $model->sync_status = 'synced';
        });

        static::updating(function ($model) {
            if (empty($model->updated_at)) {
                $model->updated_at = gmdate('Y-m-d H:i:s');
            }
            $model->sync_status = 'synced';
        });
    }

    public function getUuidAttribute(): string
    {
        return $this->attributes['uuid'] ?? '';
    }

    public function softDeleteWithCascade(): void
    {
        $now = gmdate('Y-m-d H:i:s');
        $this->update([
            'deleted_at' => $now,
            'updated_at' => $now,
            'sync_status' => 'synced',
        ]);

        foreach ($this->getChildRelationships() as $relation) {
            $this->{$relation}->each(function ($child) {
                if (method_exists($child, 'softDeleteWithCascade')) {
                    $child->softDeleteWithCascade();
                } else {
                    $now = gmdate('Y-m-d H:i:s');
                    $child->update([
                        'deleted_at' => $now,
                        'updated_at' => $now,
                        'sync_status' => 'synced',
                    ]);
                }
            });
        }
    }

    protected function getChildRelationships(): array
    {
        return [];
    }

    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    public function scopeSince($query, ?string $since)
    {
        if ($since) {
            return $query->where('updated_at', '>', $since);
        }

        return $query;
    }

    public function toArray(): array
    {
        $data = parent::toArray();
        if (isset($this->attributes['uuid'])) {
            $data['id'] = $this->uuid;
            unset($data['uuid']);
        }

        return $data;
    }

    public function toSyncArray(): array
    {
        $data = $this->toArray();
        $data['id'] = $this->uuid;
        unset($data[$this->getKeyName()]);

        return $data;
    }
}
