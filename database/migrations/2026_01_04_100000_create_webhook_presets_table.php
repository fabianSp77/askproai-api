<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Webhook Preset Library - Database Schema
 *
 * Stores reusable webhook templates for integrating with external systems:
 * - Jira, ServiceNow, OTRS, Zendesk (ticketing)
 * - Slack, Teams (messaging)
 * - Custom user-defined templates
 *
 * Design principles:
 * - System presets (seeded) vs company-custom presets
 * - Multi-tenant isolation via company_id
 * - Soft deletes for audit trail
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('webhook_presets', function (Blueprint $table) {
            $table->id();

            // Ownership: null = system preset, set = company-custom
            $table->unsignedBigInteger('company_id')->nullable();
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');

            // Identification
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();

            // Classification
            $table->string('target_system', 50);
            // Values: jira, servicenow, otrs, zendesk, slack, teams, custom
            $table->string('category', 50)->default('ticketing');
            // Values: ticketing, messaging, custom

            // Template configuration
            $table->json('payload_template');
            // The actual JSON template with {{variable}} placeholders

            $table->json('headers_template')->nullable();
            // Custom headers template (can also use {{variable}})

            $table->json('variable_schema')->nullable();
            // JSON Schema defining available variables for validation
            // Example: {"case.subject": {"type": "string", "required": true}}

            $table->json('default_values')->nullable();
            // Default values for optional variables
            // Example: {"priority": "Medium", "project_key": "SUPPORT"}

            // Authentication hints
            $table->string('auth_type', 50)->default('hmac');
            // Values: hmac, bearer, basic, api_key, none
            $table->text('auth_instructions')->nullable();
            // Human-readable setup instructions

            // Metadata
            $table->string('version', 20)->default('1.0.0');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            // System presets cannot be deleted by companies

            // Documentation
            $table->string('documentation_url')->nullable();
            $table->json('example_response')->nullable();
            // Expected response format for external ID extraction

            // Audit
            $table->char('created_by', 36)->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->nullable();
            $table->foreign('created_by')
                ->references('id')
                ->on('staff')
                ->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('company_id');
            $table->index('target_system');
            $table->index('category');
            $table->index(['is_system', 'is_active']);
            $table->index(['company_id', 'target_system']);
        });

        // Link presets to output configurations (optional relationship)
        Schema::table('service_output_configurations', function (Blueprint $table) {
            $table->unsignedBigInteger('webhook_preset_id')->nullable()->after('webhook_payload_template');
            $table->foreign('webhook_preset_id')
                ->references('id')
                ->on('webhook_presets')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_output_configurations', function (Blueprint $table) {
            $table->dropForeign(['webhook_preset_id']);
            $table->dropColumn('webhook_preset_id');
        });

        Schema::dropIfExists('webhook_presets');
    }
};
