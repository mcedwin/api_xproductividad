<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tasks')) {
            Schema::create('tasks', function ($table) {
                $table->id();
                $table->string('uuid', 36)->unique();
                $table->unsignedBigInteger('objective_id');
                $table->string('title');
                $table->integer('expected_minutes')->nullable();
                $table->datetime('scheduled_at')->nullable();
                $table->datetime('completed_at')->nullable();
                $table->string('status', 20)->default('pending');
                $table->string('created_at', 30)->nullable();
                $table->string('updated_at', 30)->nullable();
                $table->string('deleted_at', 30)->nullable()->default(null);
                $table->string('sync_status', 20)->default('synced');
                $table->string('device_id', 36)->nullable()->default(null);
                $table->index('objective_id');
                $table->index('updated_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
