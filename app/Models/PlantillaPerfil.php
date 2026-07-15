<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlantillaPerfil extends Model
{
    protected $table = 'plantillas_perfil';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'nombre',
        'descripcion',
        'codigo_punto_icono',
        'valor_color',
    ];

    public function tareasPlantilla()
    {
        return $this->hasMany(TareaPlantilla::class, 'plantilla_id', 'id');
    }
}
