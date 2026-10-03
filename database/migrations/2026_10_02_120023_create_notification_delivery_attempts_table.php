<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_delivery_attempts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('notification_delivery_id')
                ->constrained('notification_deliveries')
                ->cascadeOnDelete();

            $table->foreignId('user_device_id')
                ->constrained('user_devices')
                ->cascadeOnDelete();

            $table->string('status', 20)->default('pending');

            $table->string('provider', 50)->nullable();

            $table->string('provider_message_id')->nullable();

            $table->timestamp('sent_at')->nullable();

            $table->timestamp('failed_at')->nullable();

            $table->text('error_message')->nullable();

            $table->timestamps();

            $table->unique(
                ['notification_delivery_id', 'user_device_id'],
                'notification_delivery_attempt_unique'
            );

            $table->index(
                ['notification_delivery_id', 'status'],
                'notification_delivery_attempt_delivery_status_index'
            );

            $table->index(
                ['user_device_id', 'status'],
                'notification_delivery_attempt_device_status_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_delivery_attempts');
    }
};