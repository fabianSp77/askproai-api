<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates hierarchical notification configuration system:
     * - Company level: Default notification policies
     * - Branch level: Location-specific overrides
     * - Service level: Service-specific notification requirements
     * - Staff level: Personal notification preferences ONLY (not business policies)
     */
    public function up(): void
    {
        if (Schema::hasTable('notification_configurations')) {
            return;
        }

        Schema::create('notification_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete()
                ->comment('Company owning this notification configuration');

            // Polymorphic relationship: Company, Branch, Service, or Staff
            $table->string('configurable_type', 255)
                ->comment('Polymorphic type: App\\Models\\Company, App\\Models\\Branch, App\\Models\\Service, App\\Models\\Staff');
            $table->unsignedBigInteger('configurable_id')
                ->comment('Polymorphic ID of the related entity');

            // Event and channel configuration
            $table->string('event_type', 100)
                ->comment('Event identifier: booking_confirmed, reminder_24h, cancellation, reschedule_confirmed, callback_request_received, etc.');
            $table->enum('channel', ['email', 'sms', 'whatsapp', 'push'])
                ->comment('Primary notification channel for this event');
            $table->enum('fallback_channel', ['email', 'sms', 'whatsapp', 'push', 'none'])
                ->nullable()
                ->comment('Fallback channel if primary fails (null = no fallback)');

            // Delivery configuration
            $table->boolean('is_enabled')->default(true)
                ->comment('Enable/disable this notification channel for this event');
            $table->integer('retry_count')->default(3)
                ->comment('Number of retry attempts for failed notifications');
            $table->integer('retry_delay_minutes')->default(5)
                ->comment('Initial delay between retries (uses exponential backoff)');

            // Template customization
            $table->string('template_override', 255)->nullable()
                ->comment('Optional custom template name (overrides default template for event_type)');

            // Channel-specific metadata
            $table->json('metadata')->nullable()
                ->comment('Channel-specific settings: {from_number, sender_name, priority, attachments, etc.}');

            $table->timestamps();

            // Indexes for performance
            $table->index('company_id', 'notif_config_company_idx');
            $table->index(['company_id', 'configurable_type', 'configurable_id', 'event_type', 'channel'],
                'notif_config_lookup_idx');
            $table->index(['company_id', 'event_type', 'is_enabled'], 'notif_config_event_enabled_idx');
            $table->index(['configurable_type', 'configurable_id'], 'notif_config_polymorphic_idx');

            // Unique constraint: One configuration per company-entity-event-channel combination
            $table->unique(
                ['company_id', 'configurable_type', 'configurable_id', 'event_type', 'channel'],
                'notif_config_unique_constraint'
            );
        });

        // Table comment: Hierarchical notification configuration: Company → Branch → Service → Staff
        // IMPORTANT: Staff entries override ONLY notification preferences (channels, templates), NOT business policies (timing, retry logic)
        // Note: SQLite doesn't support COMMENT ON TABLE, so this is documented in code only
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_configurations');
    }
};
