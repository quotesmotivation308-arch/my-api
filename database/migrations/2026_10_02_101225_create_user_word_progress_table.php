<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_word_progress', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('word_id')
                ->constrained('words')
                ->cascadeOnDelete();

            $table->string('status')->default('learning');

            $table->unsignedInteger('times_reviewed')->default(0);
            $table->unsignedInteger('current_streak')->default(0);

            $table->unsignedInteger('interval_days')->default(1);

            $table->timestamp('last_reviewed_at')->nullable();
            $table->timestamp('next_review_at')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'word_id']);

            $table->index(['user_id', 'next_review_at']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_word_progress');
    }
};