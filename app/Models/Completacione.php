<?php

namespace App\Models;

use App\Traits\Syncable;
use Illuminate\Database\Eloquent\Model;

class Completacione extends Model
{
    use Syncable;

    protected $table = 'app_completions';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'user_id',
        'task_id',
        'fecha_completada',
        'created_at',
        'updated_at',
        'deleted_at',
        'sync_status',
        'device_id',
    ];

    protected $casts = [
        'fecha_completada' => 'date',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'user_id', 'id');
    }

    public function tarea()
    {
        return $this->belongsTo(Tarea::class, 'task_id', 'id');
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
