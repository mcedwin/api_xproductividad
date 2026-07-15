<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureUuidColumn('usuarios');
        $this->ensureUuidColumn('tareas');
        $this->ensureUuidColumn('completaciones');

        $this->ensureUpdatedAtColumn('usuarios');
        $this->ensureUpdatedAtColumn('tareas');
        $this->ensureUpdatedAtColumn('completaciones');

        $this->ensureUpdatedAtIndex('usuarios', 'usuarios_updated_at_index');
        $this->ensureUpdatedAtIndex('tareas', 'tareas_updated_at_index');
        $this->ensureUpdatedAtIndex('completaciones', 'completaciones_updated_at_index');
    }

    public function down(): void
    {
        // Intencionalmente vacío: esta migración solo asegura el esquema.
    }

    private function ensureUuidColumn(string $table): void
    {
        if (! Schema::hasColumn($table, 'uuid')) {
            Schema::table($table, function ($table) {
                $table->string('uuid', 36)->nullable()->after('id');
            });
        }

        // Rellenar filas sin uuid.
        $rows = DB::table($table)->whereNull('uuid')->select('id')->get();
        foreach ($rows as $row) {
            DB::table($table)
                ->where('id', $row->id)
                ->update(['uuid' => (string) \Illuminate\Support\Str::uuid()]);
        }

        // Asegurar NOT NULL.
        $column = DB::selectOne(
            "SELECT is_nullable FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = 'uuid'",
            [$table]
        );
        $isNullable = $column?->is_nullable === 'YES';

        if ($isNullable) {
            Schema::table($table, function ($table) {
                $table->string('uuid', 36)->nullable(false)->change();
            });
        }

        // Asegurar UNIQUE.
        $indexes = DB::select("SHOW INDEX FROM `{$table}`");
        $hasUuidUnique = false;
        foreach ($indexes as $index) {
            if ($index->Key_name !== 'PRIMARY' && $index->Column_name === 'uuid' && $index->Non_unique == 0) {
                $hasUuidUnique = true;
                break;
            }
        }

        if (! $hasUuidUnique) {
            Schema::table($table, function ($table) {
                $table->unique('uuid');
            });
        }
    }

    private function ensureUpdatedAtColumn(string $table): void
    {
        if (! Schema::hasColumn($table, 'updated_at')) {
            Schema::table($table, function ($table) {
                $table->string('updated_at', 30)->nullable()->after('created_at');
            });
        }

        // Rellenar filas sin updated_at.
        DB::table($table)
            ->whereNull('updated_at')
            ->update(['updated_at' => '1970-01-01 00:00:00']);
    }

    private function ensureUpdatedAtIndex(string $table, string $indexName): void
    {
        $indexes = DB::select("SHOW INDEX FROM `{$table}`");
        foreach ($indexes as $index) {
            if ($index->Key_name === $indexName) {
                return;
            }
        }

        Schema::table($table, function ($table) {
            $table->index('updated_at');
        });
    }
};
