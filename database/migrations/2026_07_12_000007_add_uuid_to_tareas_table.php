<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tareas', 'uuid')) {
            Schema::table('tareas', function ($table) {
                $table->string('uuid', 36)->unique()->after('id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tareas', 'uuid')) {
            Schema::table('tareas', function ($table) {
                $table->dropColumn('uuid');
            });
        }
    }
};
