<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE completaciones MODIFY COLUMN created_at VARCHAR(30) NULL DEFAULT NULL');
        DB::statement('ALTER TABLE completaciones MODIFY COLUMN updated_at VARCHAR(30) NULL DEFAULT NULL');

        $hasUuid = Schema::hasColumn('completaciones', 'uuid');

        if (! $hasUuid) {
            Schema::table('completaciones', function ($table) {
                $table->string('uuid', 36)->nullable()->after('id');
            });

            $rows = DB::table('completaciones')->whereNull('uuid')->select('id')->get();
            foreach ($rows as $row) {
                DB::table('completaciones')
                    ->where('id', $row->id)
                    ->update(['uuid' => (string) \Illuminate\Support\Str::uuid()]);
            }

            Schema::table('completaciones', function ($table) {
                $table->unique('uuid');
                $table->string('uuid', 36)->nullable(false)->change();
            });
        }

        if (! Schema::hasColumn('completaciones', 'deleted_at')) {
            Schema::table('completaciones', function ($table) {
                $table->string('deleted_at', 30)->nullable()->default(null)->after('fecha_completada');
                $table->string('sync_status', 20)->default('synced')->after('deleted_at');
                $table->string('device_id', 36)->nullable()->default(null)->after('sync_status');
            });
        }

        if (! $this->hasIndex('completaciones', 'completaciones_updated_at_index')) {
            Schema::table('completaciones', function ($table) {
                $table->index('updated_at');
            });
        }
    }

    public function down(): void
    {
        if ($this->hasIndex('completaciones', 'completaciones_updated_at_index')) {
            Schema::table('completaciones', function ($table) {
                $table->dropIndex(['updated_at']);
            });
        }

        if (Schema::hasColumn('completaciones', 'deleted_at')) {
            Schema::table('completaciones', function ($table) {
                $table->dropColumn(['uuid', 'deleted_at', 'sync_status', 'device_id']);
            });
        }

        DB::statement('ALTER TABLE completaciones MODIFY COLUMN created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');
        DB::statement('ALTER TABLE completaciones MODIFY COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');
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
