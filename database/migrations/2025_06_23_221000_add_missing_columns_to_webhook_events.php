<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('webhook_events', 'idempotency_key')) {
            Schema::table('webhook_events', function (Blueprint $table) {
                $table->string('idempotency_key')->nullable()->after('correlation_id');
                $table->string('event_type')->nullable()->after('event');
                $table->string('event_id')->nullable()->after('event_type');
                
                // Add indexes
                $table->index('idempotency_key');
                $table->index(['idempotency_key', 'status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('webhook_events', function (Blueprint $table) {
            $table->dropIndex(['idempotency_key', 'status']);
            $table->dropIndex(['idempotency_key']);
            
            $table->dropColumn(['idempotency_key', 'event_type', 'event_id']);
        });
    }
};