<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('auth_accounts')) {
            Schema::create('auth_accounts', function ($table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('provider', 50);
                $table->string('provider_id');
                $table->string('password_hash')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->unique(['provider', 'provider_id']);
                $table->index('user_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_accounts');
    }
};
