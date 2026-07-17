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
                'name' => $p->nombre,
                'description' => $p->descripcion,
                'iconCodePoint' => $p->codigo_punto_icono,
                'colorValue' => $p->valor_color,
                'tasks' => $p->tareasPlantilla->map(function ($t) {
                    return [
                        'title' => $t->titulo,
                        'periodicity' => $t->periodicidad,
                        'days_of_week' => $t->dias_semana,
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
            'name' => $p->nombre,
            'description' => $p->descripcion,
            'iconCodePoint' => $p->codigo_punto_icono,
            'colorValue' => $p->valor_color,
            'tasks' => $p->tareasPlantilla->map(function ($t) {
                return [
                    'title' => $t->titulo,
                    'periodicity' => $t->periodicidad,
                    'days_of_week' => $t->dias_semana,
                ];
            }),
        ];

        return response()->json($plantilla);
    }

    public function tareas(string $id)
    {
        $plantilla = PlantillaPerfil::findOrFail($id);

        $tareas = TareaPlantilla::where('template_id', $plantilla->id)
            ->orderBy('orden')
            ->get()
            ->map(function ($t) {
                return [
                    'title' => $t->titulo,
                    'periodicity' => $t->periodicidad,
                    'days_of_week' => $t->dias_semana,
                ];
            });

        return response()->json($tareas);
    }
}
