<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_logs', function (Blueprint $table) {
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

            $table->string('source');

            $table->timestamp('reviewed_at');

            $table->unsignedInteger('previous_interval_days')->nullable();
            $table->unsignedInteger('new_interval_days')->nullable();

            $table->timestamp('next_review_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'reviewed_at']);
            $table->index(['user_id', 'word_id']);
            $table->index(['progress_id', 'reviewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_logs');
    }
};