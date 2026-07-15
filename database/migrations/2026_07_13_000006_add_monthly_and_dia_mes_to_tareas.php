<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Agregar 'monthly' al ENUM de periodicidad.
        $column = DB::selectOne("SHOW COLUMNS FROM tareas WHERE Field = 'periodicidad'");
        if ($column && str_contains($column->Type, 'enum')) {
            DB::statement("ALTER TABLE tareas MODIFY COLUMN periodicidad ENUM('daily','weekly','custom','fixed','monthly') NOT NULL DEFAULT 'daily'");
        }

        // Agregar dia_mes para tareas mensuales (opcional).
        if (! Schema::hasColumn('tareas', 'dia_mes')) {
            Schema::table('tareas', function ($table) {
                $table->unsignedTinyInteger('dia_mes')->nullable()->after('dias_semana')->comment('Día del mes para periodicidad monthly (1-31)');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tareas', 'dia_mes')) {
            Schema::table('tareas', function ($table) {
                $table->dropColumn('dia_mes');
            });
        }

        $column = DB::selectOne("SHOW COLUMNS FROM tareas WHERE Field = 'periodicidad'");
        if ($column && str_contains($column->Type, 'monthly')) {
            DB::statement("ALTER TABLE tareas MODIFY COLUMN periodicidad ENUM('daily','weekly','custom','fixed') NOT NULL DEFAULT 'daily'");
        }
    }
};
