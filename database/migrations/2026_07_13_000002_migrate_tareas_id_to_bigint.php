<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tareas', 'uuid')) {
            Schema::table('tareas', function ($table) {
                $table->string('uuid', 36)->nullable()->after('id');
            });

            $rows = DB::table('tareas')->select('id')->get();
            foreach ($rows as $row) {
                DB::table('tareas')
                    ->where('id', $row->id)
                    ->update(['uuid' => $row->id]);
            }

            Schema::table('tareas', function ($table) {
                $table->unique('uuid');
            });
        }

        $fkName = $this->getForeignKeyName('completaciones', 'fk_completaciones_tarea');

        if ($fkName) {
            DB::statement("ALTER TABLE completaciones DROP FOREIGN KEY `{$fkName}`");
        }

        DB::statement('ALTER TABLE completaciones MODIFY COLUMN tarea_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE tareas MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');

        Schema::table('completaciones', function ($table) {
            $table->foreign('tarea_id')->references('id')->on('tareas');
        });
    }

    public function down(): void
    {
        $fkName = $this->getForeignKeyName('completaciones', 'fk_completaciones_tarea');

        if ($fkName) {
            DB::statement("ALTER TABLE completaciones DROP FOREIGN KEY `{$fkName}`");
        }

        DB::statement('ALTER TABLE tareas MODIFY COLUMN id CHAR(36) NOT NULL');
        DB::statement('ALTER TABLE completaciones MODIFY COLUMN tarea_id CHAR(36) NOT NULL');

        Schema::table('tareas', function ($table) {
            $table->dropIndex(['uuid']);
            $table->dropColumn('uuid');
        });
    }

    private function getForeignKeyName(string $table, string $fallback): ?string
    {
        $rows = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL AND COLUMN_NAME = 'tarea_id'", [$table]);

        return $rows ? $rows[0]->CONSTRAINT_NAME : $fallback;
    }
};
