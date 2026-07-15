<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE users MODIFY COLUMN created_at VARCHAR(30) NULL DEFAULT NULL');
        DB::statement('ALTER TABLE users MODIFY COLUMN updated_at VARCHAR(30) NULL DEFAULT NULL');

        $hasUuid = Schema::hasColumn('users', 'uuid');

        if (! $hasUuid) {
            Schema::table('users', function ($table) {
                $table->string('uuid', 36)->nullable()->after('id');
            });

            $rows = DB::table('users')->whereNull('uuid')->select('id')->get();
            foreach ($rows as $row) {
                DB::table('users')
                    ->where('id', $row->id)
                    ->update(['uuid' => (string) \Illuminate\Support\Str::uuid()]);
            }

            Schema::table('users', function ($table) {
                $table->unique('uuid');
                $table->string('uuid', 36)->nullable(false)->change();
            });
        }

        if (! Schema::hasColumn('users', 'deleted_at')) {
            Schema::table('users', function ($table) {
                $table->string('deleted_at', 30)->nullable()->default(null)->after('timezone');
                $table->string('sync_status', 20)->default('synced')->after('deleted_at');
                $table->string('device_id', 36)->nullable()->default(null)->after('sync_status');
            });
        }

        if (! $this->hasIndex('users', 'users_updated_at_index')) {
            Schema::table('users', function ($table) {
                $table->index('updated_at');
            });
        }
    }

    public function down(): void
    {
        if ($this->hasIndex('users', 'users_updated_at_index')) {
            Schema::table('users', function ($table) {
                $table->dropIndex(['updated_at']);
            });
        }

        if (Schema::hasColumn('users', 'deleted_at')) {
            Schema::table('users', function ($table) {
                $table->dropColumn(['uuid', 'deleted_at', 'sync_status', 'device_id']);
            });
        }

        DB::statement('ALTER TABLE users MODIFY COLUMN created_at TIMESTAMP NULL DEFAULT NULL');
        DB::statement('ALTER TABLE users MODIFY COLUMN updated_at TIMESTAMP NULL DEFAULT NULL');
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
