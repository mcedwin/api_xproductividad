<?php

namespace App\Models;

use App\Traits\Syncable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Syncable;

    protected $table = 'users';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'name',
        'email',
        'timezone',
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
    ];

    protected function getChildRelationships(): array
    {
        return ['goals'];
    }

    public function goals()
    {
        return $this->hasMany(Objetive::class, 'user_id', 'id');
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
