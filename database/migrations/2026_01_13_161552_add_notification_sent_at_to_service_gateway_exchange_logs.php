<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds notification_sent_at field to track when failure alerts were sent.
     * This prevents duplicate notifications for the same failed webhook.
     */
    public function up(): void
    {
        Schema::table('service_gateway_exchange_logs', function (Blueprint $table) {
            $table->timestamp('notification_sent_at')->nullable()->after('is_test');

            // Index for efficient querying of unnotified failed logs
            $table->index(['direction', 'error_class', 'notification_sent_at'], 'idx_webhook_failure_notifications');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_gateway_exchange_logs', function (Blueprint $table) {
            $table->dropIndex('idx_webhook_failure_notifications');
            $table->dropColumn('notification_sent_at');
        });
    }
};
