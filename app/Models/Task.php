<?php

namespace App\Models;

use App\Traits\Syncable;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use Syncable;

    protected $table = 'tasks';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'objective_id',
        'title',
        'expected_minutes',
        'scheduled_at',
        'completed_at',
        'status',
        'created_at',
        'updated_at',
        'deleted_at',
        'sync_status',
        'device_id',
    ];

    public function objetive()
    {
        return $this->belongsTo(Objetive::class, 'objective_id', 'id');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
