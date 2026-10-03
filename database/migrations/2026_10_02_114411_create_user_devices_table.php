<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('device_token', 512);

            $table->string('platform', 20);

            $table->boolean('is_active')->default(true);

            $table->timestamp('last_seen_at')->nullable();

            $table->timestamps();

            $table->unique(
                ['user_id', 'device_token'],
                'user_devices_user_token_unique'
            );

            $table->index(['user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_devices');
    }
};