<?php

namespace App\Models;

use App\Traits\Syncable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens, Syncable;

    protected $table = 'usuarios';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'google_id',
        'nombre',
        'email',
        'avatar',
        'tipo_perfil',
        'created_at',
        'updated_at',
        'deleted_at',
        'sync_status',
        'device_id',
    ];

    protected $hidden = [
        'deleted_at',
        'sync_status',
        'device_id',
        'remember_token',
    ];

    protected function getChildRelationships(): array
    {
        return ['tareas', 'completaciones'];
    }

    public function tareas()
    {
        return $this->hasMany(Tarea::class, 'usuario_id', 'id');
    }

    public function completaciones()
    {
        return $this->hasMany(Completacione::class, 'usuario_id', 'id');
    }

    public function plantillaPerfil()
    {
        return $this->belongsTo(PlantillaPerfil::class, 'tipo_perfil', 'id');
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
