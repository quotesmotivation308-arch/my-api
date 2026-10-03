<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_deliveries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('word_id')
                ->constrained('words')
                ->cascadeOnDelete();

            $table->foreignId('progress_id')
                ->constrained('user_word_progress')
                ->cascadeOnDelete();

            $table->timestamp('scheduled_for');

            $table->timestamp('sent_at')->nullable();

            $table->string('status')->default('pending');

            $table->string('provider')->nullable();

            $table->string('provider_message_id')->nullable();

            $table->timestamps();

            $table->unique(
                ['user_id', 'progress_id', 'scheduled_for'],
                'notification_deliveries_unique'
            );

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'scheduled_for']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_deliveries');
    }
};