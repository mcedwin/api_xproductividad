<?php

namespace App\Http\Controllers;

use App\Models\PlantillaPerfil;
use App\Models\TareaPlantilla;

class PlantillaPerfilController extends Controller
{
    public function index()
    {
        $plantillas = PlantillaPerfil::with('tareasPlantilla')->get()->map(function ($p) {
            return [
                'id' => $p->id,
                'nombre' => $p->nombre,
                'descripcion' => $p->descripcion,
                'codigo_punto_icono' => $p->codigo_punto_icono,
                'valor_color' => $p->valor_color,
                'tareas' => $p->tareasPlantilla->map(function ($t) {
                    return [
                        'titulo' => $t->titulo,
                        'periodicidad' => $t->periodicidad,
                        'dias_semana' => $t->dias_semana,
                    ];
                }),
            ];
        });

        return response()->json(['data' => $plantillas]);
    }

    public function show(string $id)
    {
        $p = PlantillaPerfil::with('tareasPlantilla')->findOrFail($id);

        $plantilla = [
            'id' => $p->id,
            'nombre' => $p->nombre,
            'descripcion' => $p->descripcion,
            'codigo_punto_icono' => $p->codigo_punto_icono,
            'valor_color' => $p->valor_color,
            'tareas' => $p->tareasPlantilla->map(function ($t) {
                return [
                    'titulo' => $t->titulo,
                    'periodicidad' => $t->periodicidad,
                    'dias_semana' => $t->dias_semana,
                ];
            }),
        ];

        return response()->json($plantilla);
    }

    public function tareas(string $id)
    {
        $plantilla = PlantillaPerfil::findOrFail($id);

        $tareas = TareaPlantilla::where('plantilla_id', $plantilla->id)
            ->orderBy('orden')
            ->get()
            ->map(function ($t) {
                return [
                    'titulo' => $t->titulo,
                    'periodicidad' => $t->periodicidad,
                    'dias_semana' => $t->dias_semana,
                ];
            });

        return response()->json($tareas);
    }
}
