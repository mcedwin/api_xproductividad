<?php

namespace App\Models;

use App\Traits\Syncable;
use Illuminate\Database\Eloquent\Model;

class Objetive extends Model
{
    use Syncable;

    protected $table = 'objectives';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'user_id',
        'title',
        'description',
        'start_date',
        'end_date',
        'status',
        'created_at',
        'updated_at',
        'deleted_at',
        'sync_status',
        'device_id',
    ];

    protected function getChildRelationships(): array
    {
        return ['tasks'];
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'objective_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
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
