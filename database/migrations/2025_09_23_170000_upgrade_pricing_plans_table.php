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
        
        if (!Schema::hasTable('pricing_plans')) {
            return;
        }

        Schema::table('pricing_plans', function (Blueprint $table) {
            // Add new columns if they don't exist
            if (!Schema::hasColumn('pricing_plans', 'internal_name')) {
                $table->string('internal_name')->unique();
            }

            if (!Schema::hasColumn('pricing_plans', 'category')) {
                $table->string('category')->default('starter');
            }

            if (!Schema::hasColumn('pricing_plans', 'tagline')) {
                $table->string('tagline')->nullable();
            }

            if (!Schema::hasColumn('pricing_plans', 'long_description')) {
                $table->text('long_description')->nullable();
            }

            if (!Schema::hasColumn('pricing_plans', 'billing_period')) {
                $table->enum('billing_period', ['monthly', 'yearly', 'one_time', 'custom'])->default('monthly');
            }

            if (!Schema::hasColumn('pricing_plans', 'yearly_discount_percentage')) {
                $table->decimal('yearly_discount_percentage', 5, 2)->nullable();
            }

            if (!Schema::hasColumn('pricing_plans', 'setup_fee')) {
                $table->decimal('setup_fee', 10, 2)->default(0);
            }

            if (!Schema::hasColumn('pricing_plans', 'sms_included')) {
                $table->integer('sms_included')->default(0);
            }

            if (!Schema::hasColumn('pricing_plans', 'price_per_sms')) {
                $table->decimal('price_per_sms', 10, 3)->default(0.19);
            }

            if (!Schema::hasColumn('pricing_plans', 'unlimited_minutes')) {
                $table->boolean('unlimited_minutes')->default(false);
            }

            if (!Schema::hasColumn('pricing_plans', 'fair_use_policy')) {
                $table->boolean('fair_use_policy')->default(false);
            }

            // Technical limits
            if (!Schema::hasColumn('pricing_plans', 'max_users')) {
                $table->integer('max_users')->nullable();
            }

            if (!Schema::hasColumn('pricing_plans', 'max_agents')) {
                $table->integer('max_agents')->nullable();
            }

            if (!Schema::hasColumn('pricing_plans', 'max_campaigns')) {
                $table->integer('max_campaigns')->nullable();
            }

            if (!Schema::hasColumn('pricing_plans', 'storage_gb')) {
                $table->integer('storage_gb')->nullable();
            }

            if (!Schema::hasColumn('pricing_plans', 'api_calls_per_month')) {
                $table->integer('api_calls_per_month')->nullable();
            }

            if (!Schema::hasColumn('pricing_plans', 'retention_days')) {
                $table->integer('retention_days')->nullable();
            }

            // Availability
            if (!Schema::hasColumn('pricing_plans', 'available_from')) {
                $table->datetime('available_from')->nullable();
            }

            if (!Schema::hasColumn('pricing_plans', 'available_until')) {
                $table->datetime('available_until')->nullable();
            }

            if (!Schema::hasColumn('pricing_plans', 'target_countries')) {
                $table->json('target_countries')->nullable();
            }

            if (!Schema::hasColumn('pricing_plans', 'customer_types')) {
                $table->json('customer_types')->nullable();
            }

            if (!Schema::hasColumn('pricing_plans', 'min_contract_months')) {
                $table->integer('min_contract_months')->default(1);
            }

            if (!Schema::hasColumn('pricing_plans', 'notice_period_days')) {
                $table->integer('notice_period_days')->default(30);
            }

            // Status flags
            if (!Schema::hasColumn('pricing_plans', 'is_visible')) {
                $table->boolean('is_visible')->default(true);
            }

            if (!Schema::hasColumn('pricing_plans', 'is_new')) {
                $table->boolean('is_new')->default(false);
            }

            if (!Schema::hasColumn('pricing_plans', 'requires_approval')) {
                $table->boolean('requires_approval')->default(false);
            }

            if (!Schema::hasColumn('pricing_plans', 'auto_upgrade_eligible')) {
                $table->boolean('auto_upgrade_eligible')->default(false);
            }

            // Stripe integration
            if (!Schema::hasColumn('pricing_plans', 'stripe_product_id')) {
                $table->string('stripe_product_id')->nullable()->index();
            }

            if (!Schema::hasColumn('pricing_plans', 'stripe_price_id')) {
                $table->string('stripe_price_id')->nullable()->index();
            }

            if (!Schema::hasColumn('pricing_plans', 'tax_category')) {
                $table->string('tax_category')->default('standard');
            }

            if (!Schema::hasColumn('pricing_plans', 'metadata')) {
                $table->json('metadata')->nullable();
            }

            // Email templates
            if (!Schema::hasColumn('pricing_plans', 'welcome_email_template')) {
                $table->string('welcome_email_template')->nullable();
            }

            if (!Schema::hasColumn('pricing_plans', 'send_usage_alerts')) {
                $table->boolean('send_usage_alerts')->default(false);
            }

            if (!Schema::hasColumn('pricing_plans', 'usage_alert_threshold')) {
                $table->integer('usage_alert_threshold')->default(80);
            }

            // Sorting
            if (!Schema::hasColumn('pricing_plans', 'sort_order')) {
                $table->integer('sort_order')->default(0)->index();
            }
        });

        // Update features column type if needed
        if (Schema::hasColumn('pricing_plans', 'features')) {
            Schema::table('pricing_plans', function (Blueprint $table) {
                $table->json('features')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pricing_plans', function (Blueprint $table) {
            // Drop new columns
            $columnsToRemove = [
                'internal_name', 'category', 'tagline', 'long_description',
                'billing_period', 'yearly_discount_percentage', 'setup_fee',
                'sms_included', 'price_per_sms', 'unlimited_minutes', 'fair_use_policy',
                'max_users', 'max_agents', 'max_campaigns', 'storage_gb',
                'api_calls_per_month', 'retention_days', 'available_from', 'available_until',
                'target_countries', 'customer_types', 'min_contract_months', 'notice_period_days',
                'is_visible', 'is_new', 'requires_approval', 'auto_upgrade_eligible',
                'stripe_product_id', 'stripe_price_id', 'tax_category', 'metadata',
                'welcome_email_template', 'send_usage_alerts', 'usage_alert_threshold', 'sort_order'
            ];

            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('pricing_plans', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};