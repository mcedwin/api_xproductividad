<?php

namespace App\Models;

use App\Traits\Syncable;
use Illuminate\Database\Eloquent\Model;

class Tarea extends Model
{
    use Syncable;

    protected $table = 'tareas';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'usuario_id',
        'titulo',
        'periodicidad',
        'dias_semana',
        'dia_mes',
        'fecha_fija',
        'hora',
        'prioridad',
        'activo',
        'created_at',
        'updated_at',
        'deleted_at',
        'sync_status',
        'device_id',
    ];

    protected $casts = [
        'dias_semana' => 'array',
        'activo' => 'boolean',
        'fecha_fija' => 'date',
    ];

    protected function getChildRelationships(): array
    {
        return ['completaciones'];
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id', 'id');
    }

    public function completaciones()
    {
        return $this->hasMany(Completacione::class, 'tarea_id', 'id');
    }

    public function completadaHoy()
    {
        return $this->hasOne(Completacione::class, 'tarea_id', 'id')
            ->whereDate('fecha_completada', today());
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
