<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_deliveries', function (Blueprint $table) {
            $table->uuid('action_token')->unique()->after('provider_message_id');
            $table->timestamp('actioned_at')->nullable()->after('sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('notification_deliveries', function (Blueprint $table) {
            $table->dropUnique(['action_token']);
            $table->dropColumn([
                'action_token',
                'actioned_at',
            ]);
        });
    }
};