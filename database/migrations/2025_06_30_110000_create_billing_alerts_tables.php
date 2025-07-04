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
        // Alert configurations per company
        Schema::create('billing_alert_configs', function (Blueprint $table) {
            $table->id();
            $table->uuid('company_id');
            
            // Alert types
            $table->enum('alert_type', [
                'usage_limit',
                'payment_reminder',
                'subscription_renewal',
                'overage_warning',
                'payment_failed',
                'budget_exceeded',
                'low_balance',
                'invoice_generated'
            ]);
            
            // Configuration
            $table->boolean('is_enabled')->default(true);
            $table->json('thresholds')->nullable(); // e.g., [80, 90, 100] for percentage thresholds
            $table->json('notification_channels')->default('["email"]'); // email, sms, webhook
            $table->integer('advance_days')->nullable(); // For time-based alerts
            $table->decimal('amount_threshold', 10, 2)->nullable(); // For amount-based alerts
            
            // Recipients
            $table->json('recipients')->nullable(); // Additional recipients besides primary contact
            $table->boolean('notify_primary_contact')->default(true);
            $table->boolean('notify_billing_contact')->default(true);
            
            // Scheduling
            $table->time('preferred_time')->nullable(); // Preferred notification time
            $table->json('quiet_hours')->nullable(); // Don't send during these hours
            
            $table->timestamps();
            
            // Indexes
            $table->unique(['company_id', 'alert_type']);
            $table->index('is_enabled');
        });
        
        // Alert history
        Schema::create('billing_alerts', function (Blueprint $table) {
            $table->id();
            $table->uuid('company_id');
            $table->foreignId('config_id')->constrained('billing_alert_configs');
            
            // Alert details
            $table->enum('alert_type', [
                'usage_limit',
                'payment_reminder',
                'subscription_renewal',
                'overage_warning',
                'payment_failed',
                'budget_exceeded',
                'low_balance',
                'invoice_generated'
            ]);
            $table->enum('severity', ['info', 'warning', 'critical'])->default('info');
            
            // Alert data
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable(); // Additional context data
            $table->decimal('threshold_value', 10, 2)->nullable();
            $table->decimal('current_value', 10, 2)->nullable();
            
            // Delivery status
            $table->enum('status', ['pending', 'sent', 'failed', 'acknowledged'])->default('pending');
            $table->json('delivery_attempts')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->uuid('acknowledged_by')->nullable();
            
            // Notification channels used
            $table->json('channels_used')->nullable();
            $table->json('channel_results')->nullable(); // Results per channel
            
            $table->timestamps();
            
            // Indexes
            $table->index(['company_id', 'status']);
            $table->index(['alert_type', 'created_at']);
            $table->index('sent_at');
        });
        
        // Alert suppression rules
        Schema::create('billing_alert_suppressions', function (Blueprint $table) {
            $table->id();
            $table->uuid('company_id');
            
            // Suppression scope
            $table->enum('alert_type', [
                'usage_limit',
                'payment_reminder',
                'subscription_renewal',
                'overage_warning',
                'payment_failed',
                'budget_exceeded',
                'low_balance',
                'invoice_generated',
                'all' // Suppress all alerts
            ]);
            
            // Suppression period
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable(); // Null means indefinite
            $table->string('reason')->nullable();
            $table->uuid('created_by');
            
            $table->timestamps();
            
            // Indexes
            $table->index(['company_id', 'alert_type']);
            $table->index(['starts_at', 'ends_at']);
        });
        
        // Add alert preferences to companies table
        Schema::table('companies', function (Blueprint $table) {
            $table->json('alert_preferences')->nullable()->after('metadata');
            $table->string('billing_contact_email')->nullable()->after('email');
            $table->string('billing_contact_phone')->nullable()->after('billing_contact_email');
            $table->decimal('usage_budget', 10, 2)->nullable()->after('billing_contact_phone');
            $table->boolean('alerts_enabled')->default(true)->after('usage_budget');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'alert_preferences',
                'billing_contact_email',
                'billing_contact_phone',
                'usage_budget',
                'alerts_enabled'
            ]);
        });
        
        Schema::dropIfExists('billing_alert_suppressions');
        Schema::dropIfExists('billing_alerts');
        Schema::dropIfExists('billing_alert_configs');
    }
};