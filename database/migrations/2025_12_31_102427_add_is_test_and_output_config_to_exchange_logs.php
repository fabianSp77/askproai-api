<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds fields for state-of-the-art webhook monitoring:
     * - is_test: Distinguish test webhooks from real deliveries
     * - output_configuration_id: Direct link to ServiceOutputConfiguration
     */
    public function up(): void
    {
        Schema::table('service_gateway_exchange_logs', function (Blueprint $table) {
            // Flag to distinguish test webhooks from real deliveries
            $table->boolean('is_test')->default(false)->after('parent_event_id');

            // Direct relationship to output configuration for filtering
            $table->unsignedBigInteger('output_configuration_id')->nullable()->after('company_id');

            // Foreign key constraint
            $table->foreign('output_configuration_id')
                ->references('id')
                ->on('service_output_configurations')
                ->onDelete('set null');

            // Indexes for efficient querying
            $table->index(['output_configuration_id', 'created_at'], 'idx_exchange_logs_config_created');
            $table->index(['is_test', 'created_at'], 'idx_exchange_logs_test_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_gateway_exchange_logs', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['output_configuration_id']);

            // Drop indexes
            $table->dropIndex('idx_exchange_logs_config_created');
            $table->dropIndex('idx_exchange_logs_test_created');

            // Drop columns
            $table->dropColumn(['is_test', 'output_configuration_id']);
        });
    }
};
