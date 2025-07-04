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
        // Pricing Plans (Packages)
        Schema::create('pricing_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type')->default('package'); // package, usage_based, hybrid
            $table->string('billing_interval')->default('monthly'); // monthly, quarterly, yearly, custom
            $table->integer('interval_count')->default(1); // For custom intervals (e.g., every 2 months)
            $table->decimal('base_price', 10, 2)->default(0);
            $table->string('currency', 3)->default('EUR');
            
            // Included quotas
            $table->integer('included_minutes')->default(0);
            $table->integer('included_appointments')->default(0);
            $table->json('included_features')->nullable(); // Array of feature slugs
            
            // Overage pricing
            $table->decimal('overage_price_per_minute', 10, 4)->nullable();
            $table->decimal('overage_price_per_appointment', 10, 2)->nullable();
            
            // Volume discounts
            $table->json('volume_discounts')->nullable(); // Array of {threshold, discount_percent}
            
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->integer('trial_days')->default(0);
            $table->integer('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['company_id', 'is_active']);
            $table->index('type');
        });

        // Service Add-ons
        Schema::create('service_addons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type')->default('recurring'); // recurring, one_time
            $table->decimal('price', 10, 2);
            $table->string('currency', 3)->default('EUR');
            $table->string('billing_interval')->nullable(); // For recurring: monthly, quarterly, yearly
            $table->string('category')->nullable(); // To group add-ons
            $table->boolean('is_active')->default(true);
            $table->boolean('is_metered')->default(false); // For usage-based add-ons
            $table->string('meter_unit')->nullable(); // e.g., 'sms', 'email', 'api_calls'
            $table->decimal('meter_unit_price', 10, 4)->nullable();
            $table->json('features')->nullable(); // What the add-on provides
            $table->json('requirements')->nullable(); // Required plan types or features
            $table->integer('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['company_id', 'is_active']);
            $table->index('type');
        });

        // Subscription Add-ons (Many-to-Many relationship)
        Schema::create('subscription_addons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_addon_id')->constrained()->onDelete('cascade');
            $table->decimal('price_override', 10, 2)->nullable(); // Custom price for this subscription
            $table->integer('quantity')->default(1);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('status')->default('active'); // active, cancelled, expired
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->unique(['subscription_id', 'service_addon_id']);
            $table->index('status');
        });

        // Price Rules (for complex pricing scenarios)
        Schema::create('price_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('pricing_plan_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type'); // time_based, location_based, customer_segment, promotional
            
            // Rule conditions
            $table->json('conditions'); // e.g., {"day_of_week": ["saturday", "sunday"], "time_range": ["18:00", "22:00"]}
            
            // Price modifications
            $table->string('modification_type'); // percentage, fixed_amount, multiplier
            $table->decimal('modification_value', 10, 2);
            
            // Validity period
            $table->dateTime('valid_from')->nullable();
            $table->dateTime('valid_until')->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0); // Higher priority rules apply first
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['company_id', 'is_active']);
            $table->index(['valid_from', 'valid_until']);
        });

        // Update subscriptions table
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreignId('pricing_plan_id')->nullable()->after('company_id')->constrained();
            $table->decimal('custom_price', 10, 2)->nullable()->after('pricing_plan_id');
            $table->json('custom_features')->nullable()->after('custom_price');
            $table->date('next_billing_date')->nullable()->after('ends_at');
            $table->string('billing_interval')->nullable()->after('next_billing_date');
            $table->integer('billing_interval_count')->default(1)->after('billing_interval');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['pricing_plan_id']);
            $table->dropColumn([
                'pricing_plan_id',
                'custom_price',
                'custom_features',
                'next_billing_date',
                'billing_interval',
                'billing_interval_count'
            ]);
        });
        
        Schema::dropIfExists('price_rules');
        Schema::dropIfExists('subscription_addons');
        Schema::dropIfExists('service_addons');
        Schema::dropIfExists('pricing_plans');
    }
};