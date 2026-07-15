<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE tareas MODIFY COLUMN created_at VARCHAR(30) NULL DEFAULT NULL');
        DB::statement('ALTER TABLE tareas MODIFY COLUMN updated_at VARCHAR(30) NULL DEFAULT NULL');

        if (! Schema::hasColumn('tareas', 'deleted_at')) {
            Schema::table('tareas', function ($table) {
                $table->string('deleted_at', 30)->nullable()->default(null)->after('activo');
                $table->string('sync_status', 20)->default('synced')->after('deleted_at');
                $table->string('device_id', 36)->nullable()->default(null)->after('sync_status');
            });
        }

        if (! $this->hasIndex('tareas', 'tareas_updated_at_index')) {
            Schema::table('tareas', function ($table) {
                $table->index('updated_at');
            });
        }
    }

    public function down(): void
    {
        if ($this->hasIndex('tareas', 'tareas_updated_at_index')) {
            Schema::table('tareas', function ($table) {
                $table->dropIndex(['updated_at']);
            });
        }

        if (Schema::hasColumn('tareas', 'deleted_at')) {
            Schema::table('tareas', function ($table) {
                $table->dropColumn(['deleted_at', 'sync_status', 'device_id']);
            });
        }

        DB::statement('ALTER TABLE tareas MODIFY COLUMN created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');
        DB::statement('ALTER TABLE tareas MODIFY COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');
    }

    private function hasIndex(string $table, string $index): bool
    {
        $indexes = DB::select("SHOW INDEX FROM `{$table}`");
        foreach ($indexes as $indexRow) {
            if ($indexRow->Key_name === $index) {
                return true;
            }
        }

        return false;
    }
};
