<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create company_gateway_configurations table for per-company gateway settings.
 *
 * This table enables multi-tenancy for gateway configuration, allowing each
 * company to have their own settings instead of relying on global config.
 *
 * Configuration Hierarchy (highest to lowest priority):
 * 1. company_gateway_configurations (this table) - explicit per-company settings
 * 2. PolicyConfiguration (legacy) - backward compatible
 * 3. config/gateway.php - global defaults
 *
 * @see App\Models\CompanyGatewayConfiguration
 * @see App\Services\Gateway\Config\GatewayConfigService
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('company_gateway_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')
                ->unique()
                ->constrained()
                ->onDelete('cascade');

            // Gateway Mode Settings
            $table->boolean('gateway_enabled')
                ->default(false)
                ->comment('Enable Service Gateway for this company');

            $table->enum('gateway_mode', ['appointment', 'service_desk', 'hybrid'])
                ->default('appointment')
                ->comment('Primary gateway mode');

            $table->enum('hybrid_fallback_mode', ['appointment', 'service_desk'])
                ->default('appointment')
                ->comment('Fallback mode when intent unclear in hybrid mode');

            // Feature Flags
            $table->boolean('enrichment_enabled')
                ->default(false)
                ->comment('Enable 2-phase delivery (wait for enrichment)');

            $table->boolean('audio_in_webhook')
                ->default(false)
                ->comment('Include presigned audio URL in webhook payloads');

            // Delivery Configuration
            $table->unsignedInteger('delivery_initial_delay_seconds')
                ->default(90)
                ->comment('Delay before first delivery attempt when wait_for_enrichment=true');

            $table->unsignedInteger('enrichment_timeout_seconds')
                ->default(180)
                ->comment('Max wait time for enrichment before partial delivery');

            $table->unsignedInteger('audio_url_ttl_minutes')
                ->default(60)
                ->comment('TTL for presigned audio URLs in minutes');

            // Admin Alerts (company-specific)
            $table->string('admin_email', 500)
                ->nullable()
                ->comment('Email address(es) for failure alerts (comma-separated)');

            $table->boolean('alerts_enabled')
                ->default(true)
                ->comment('Enable admin alerts for this company');

            $table->string('slack_webhook', 500)
                ->nullable()
                ->comment('Slack webhook URL for critical alerts');

            // Intent Detection Settings (for hybrid mode)
            $table->decimal('intent_confidence_threshold', 3, 2)
                ->default(0.75)
                ->comment('Minimum confidence for intent classification (0.00-1.00)');

            // Service Desk Settings
            $table->unsignedInteger('default_priority')
                ->nullable()
                ->comment('Default priority for new service cases (1-4)');

            $table->string('default_case_type', 50)
                ->default('incident')
                ->comment('Default case type: incident, request, problem');

            $table->timestamps();
        });

        // Add index for quick lookups
        Schema::table('company_gateway_configurations', function (Blueprint $table) {
            $table->index(['gateway_enabled', 'gateway_mode'], 'idx_gateway_enabled_mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_gateway_configurations');
    }
};
