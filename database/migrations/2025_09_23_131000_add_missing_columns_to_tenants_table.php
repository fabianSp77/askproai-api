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
        
        if (!Schema::hasTable('tenants')) {
            return;
        }

        Schema::table('tenants', function (Blueprint $table) {
            // Domain & Access
            if (!Schema::hasColumn('tenants', 'domain')) {
                $table->string('domain')->nullable();
            }
            if (!Schema::hasColumn('tenants', 'subdomain')) {
                $table->string('subdomain')->nullable();
            }

            // API & Security
            if (!Schema::hasColumn('tenants', 'api_key_hash')) {
                $table->string('api_key_hash')->nullable();
            }
            if (!Schema::hasColumn('tenants', 'api_key_prefix')) {
                $table->string('api_key_prefix', 16)->nullable();
            }
            if (!Schema::hasColumn('tenants', 'api_secret')) {
                $table->text('api_secret')->nullable();
            }
            if (!Schema::hasColumn('tenants', 'allowed_ips')) {
                $table->json('allowed_ips')->nullable();
            }

            // Webhook Configuration
            if (!Schema::hasColumn('tenants', 'webhook_url')) {
                $table->string('webhook_url', 500)->nullable();
            }
            if (!Schema::hasColumn('tenants', 'webhook_events')) {
                $table->json('webhook_events')->nullable();
            }
            if (!Schema::hasColumn('tenants', 'webhook_secret')) {
                $table->string('webhook_secret')->nullable();
            }

            // Billing & Pricing
            if (!Schema::hasColumn('tenants', 'pricing_plan')) {
                $table->string('pricing_plan')->default('starter');
            }
            if (!Schema::hasColumn('tenants', 'monthly_fee')) {
                $table->decimal('monthly_fee', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('tenants', 'per_minute_rate')) {
                $table->decimal('per_minute_rate', 5, 2)->default(0.05);
            }
            if (!Schema::hasColumn('tenants', 'discount_percentage')) {
                $table->decimal('discount_percentage', 5, 2)->default(0);
            }
            if (!Schema::hasColumn('tenants', 'billing_info')) {
                $table->json('billing_info')->nullable();
            }
            if (!Schema::hasColumn('tenants', 'payment_method')) {
                $table->string('payment_method')->nullable();
            }
            if (!Schema::hasColumn('tenants', 'billing_cycle')) {
                $table->enum('billing_cycle', ['monthly', 'quarterly', 'yearly'])->default('monthly');
            }
            if (!Schema::hasColumn('tenants', 'next_billing_date')) {
                $table->date('next_billing_date')->nullable();
            }
            if (!Schema::hasColumn('tenants', 'last_payment_date')) {
                $table->date('last_payment_date')->nullable();
            }

            // Integrations
            if (!Schema::hasColumn('tenants', 'calcom_enabled')) {
                $table->boolean('calcom_enabled')->default(false);
            }
            if (!Schema::hasColumn('tenants', 'calcom_api_key')) {
                $table->text('calcom_api_key')->nullable();
            }
            if (!Schema::hasColumn('tenants', 'calcom_event_types')) {
                $table->json('calcom_event_types')->nullable();
            }
            if (!Schema::hasColumn('tenants', 'retell_enabled')) {
                $table->boolean('retell_enabled')->default(false);
            }
            if (!Schema::hasColumn('tenants', 'retell_api_key')) {
                $table->text('retell_api_key')->nullable();
            }
            if (!Schema::hasColumn('tenants', 'integrations')) {
                $table->json('integrations')->nullable();
            }

            // Limits & Quota
            if (!Schema::hasColumn('tenants', 'limits')) {
                $table->json('limits')->nullable();
            }
            if (!Schema::hasColumn('tenants', 'max_users')) {
                $table->integer('max_users')->default(10);
            }
            if (!Schema::hasColumn('tenants', 'max_companies')) {
                $table->integer('max_companies')->default(5);
            }
            if (!Schema::hasColumn('tenants', 'max_branches')) {
                $table->integer('max_branches')->default(10);
            }
            if (!Schema::hasColumn('tenants', 'max_agents')) {
                $table->integer('max_agents')->default(5);
            }
            if (!Schema::hasColumn('tenants', 'max_phone_numbers')) {
                $table->integer('max_phone_numbers')->default(10);
            }
            if (!Schema::hasColumn('tenants', 'max_monthly_calls')) {
                $table->integer('max_monthly_calls')->default(1000);
            }
            if (!Schema::hasColumn('tenants', 'max_storage_gb')) {
                $table->integer('max_storage_gb')->default(10);
            }

            // Settings & Configuration
            if (!Schema::hasColumn('tenants', 'settings')) {
                $table->json('settings')->nullable();
            }
            if (!Schema::hasColumn('tenants', 'features')) {
                $table->json('features')->nullable();
            }
            if (!Schema::hasColumn('tenants', 'feature_flags')) {
                $table->json('feature_flags')->nullable();
            }
            if (!Schema::hasColumn('tenants', 'gdpr_settings')) {
                $table->json('gdpr_settings')->nullable();
            }
            if (!Schema::hasColumn('tenants', 'timezone')) {
                $table->string('timezone')->default('Europe/Berlin');
            }
            if (!Schema::hasColumn('tenants', 'language')) {
                $table->string('language', 5)->default('de');
            }
            if (!Schema::hasColumn('tenants', 'currency')) {
                $table->string('currency', 3)->default('EUR');
            }
            if (!Schema::hasColumn('tenants', 'date_format')) {
                $table->string('date_format')->default('d.m.Y');
            }
            if (!Schema::hasColumn('tenants', 'time_format')) {
                $table->string('time_format')->default('H:i');
            }

            // Statistics & Tracking
            if (!Schema::hasColumn('tenants', 'total_calls')) {
                $table->bigInteger('total_calls')->default(0);
            }
            if (!Schema::hasColumn('tenants', 'total_minutes')) {
                $table->bigInteger('total_minutes')->default(0);
            }
            if (!Schema::hasColumn('tenants', 'monthly_calls')) {
                $table->integer('monthly_calls')->default(0);
            }
            if (!Schema::hasColumn('tenants', 'monthly_minutes')) {
                $table->integer('monthly_minutes')->default(0);
            }
            if (!Schema::hasColumn('tenants', 'storage_used_mb')) {
                $table->bigInteger('storage_used_mb')->default(0);
            }
            if (!Schema::hasColumn('tenants', 'api_calls_today')) {
                $table->integer('api_calls_today')->default(0);
            }
            if (!Schema::hasColumn('tenants', 'api_calls_month')) {
                $table->integer('api_calls_month')->default(0);
            }
            if (!Schema::hasColumn('tenants', 'last_api_call_at')) {
                $table->timestamp('last_api_call_at')->nullable();
            }
            if (!Schema::hasColumn('tenants', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable();
            }

            // Status & Lifecycle
            if (!Schema::hasColumn('tenants', 'status')) {
                $table->string('status')->default('active');
            }
            if (!Schema::hasColumn('tenants', 'trial_ends_at')) {
                $table->timestamp('trial_ends_at')->nullable();
            }
            if (!Schema::hasColumn('tenants', 'subscription_ends_at')) {
                $table->timestamp('subscription_ends_at')->nullable();
            }
            if (!Schema::hasColumn('tenants', 'suspended_at')) {
                $table->timestamp('suspended_at')->nullable();
            }
            if (!Schema::hasColumn('tenants', 'suspended_reason')) {
                $table->text('suspended_reason')->nullable();
            }
            if (!Schema::hasColumn('tenants', 'onboarding_completed')) {
                $table->boolean('onboarding_completed')->default(false);
            }
            if (!Schema::hasColumn('tenants', 'onboarding_step')) {
                $table->string('onboarding_step')->nullable();
            }

            // Company Info
            if (!Schema::hasColumn('tenants', 'company_name')) {
                $table->string('company_name')->nullable();
            }
            if (!Schema::hasColumn('tenants', 'tax_id')) {
                $table->string('tax_id')->nullable();
            }
            if (!Schema::hasColumn('tenants', 'billing_address')) {
                $table->json('billing_address')->nullable();
            }
            if (!Schema::hasColumn('tenants', 'contact_email')) {
                $table->string('contact_email')->nullable();
            }
            if (!Schema::hasColumn('tenants', 'contact_phone')) {
                $table->string('contact_phone')->nullable();
            }

            // Metadata
            if (!Schema::hasColumn('tenants', 'notes')) {
                $table->text('notes')->nullable();
            }
            if (!Schema::hasColumn('tenants', 'tags')) {
                $table->json('tags')->nullable();
            }
            if (!Schema::hasColumn('tenants', 'custom_fields')) {
                $table->json('custom_fields')->nullable();
            }
            if (!Schema::hasColumn('tenants', 'metadata')) {
                $table->json('metadata')->nullable();
            }

            // Indices for performance
            $table->index('domain');
            $table->index('subdomain');
            $table->index('status');
            $table->index('pricing_plan');
            $table->index('api_key_prefix');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Drop indices first
            $table->dropIndex(['domain']);
            $table->dropIndex(['subdomain']);
            $table->dropIndex(['status']);
            $table->dropIndex(['pricing_plan']);
            $table->dropIndex(['api_key_prefix']);

            // Drop columns
            $columns = [
                'domain', 'subdomain', 'api_key_hash', 'api_key_prefix', 'api_secret', 'allowed_ips',
                'webhook_url', 'webhook_events', 'webhook_secret', 'pricing_plan', 'monthly_fee',
                'per_minute_rate', 'discount_percentage', 'billing_info', 'payment_method',
                'billing_cycle', 'next_billing_date', 'last_payment_date', 'calcom_enabled',
                'calcom_api_key', 'calcom_event_types', 'retell_enabled', 'retell_api_key',
                'integrations', 'limits', 'max_users', 'max_companies', 'max_branches', 'max_agents',
                'max_phone_numbers', 'max_monthly_calls', 'max_storage_gb', 'settings', 'features',
                'feature_flags', 'gdpr_settings', 'timezone', 'language', 'currency', 'date_format',
                'time_format', 'total_calls', 'total_minutes', 'monthly_calls', 'monthly_minutes',
                'storage_used_mb', 'api_calls_today', 'api_calls_month', 'last_api_call_at',
                'last_login_at', 'status', 'trial_ends_at', 'subscription_ends_at', 'suspended_at',
                'suspended_reason', 'onboarding_completed', 'onboarding_step', 'company_name',
                'tax_id', 'billing_address', 'contact_email', 'contact_phone', 'notes', 'tags',
                'custom_fields', 'metadata'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('tenants', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};