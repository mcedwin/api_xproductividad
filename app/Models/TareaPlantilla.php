<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TareaPlantilla extends Model
{
    protected $table = 'app_template_tasks';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $fillable = [
        'template_id',
        'titulo',
        'periodicidad',
        'dias_semana',
        'dia_mes',
        'prioridad',
        'orden',
    ];

    protected $casts = [
        'dias_semana' => 'array',
    ];

    public function plantillaPerfil()
    {
        return $this->belongsTo(PlantillaPerfil::class, 'template_id', 'id');
    }
}
