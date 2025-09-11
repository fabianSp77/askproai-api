<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations for multi-tier billing support
     */
    public function up(): void
    {
        // 1. Extend tenants table for hierarchy and reseller support - Add columns first
        Schema::table('tenants', function (Blueprint $table) {
            // Check and add columns if they don't exist
            if (!Schema::hasColumn('tenants', 'parent_tenant_id')) {
                $table->unsignedBigInteger('parent_tenant_id')->nullable()->after('id');
            }
            
            if (!Schema::hasColumn('tenants', 'tenant_type')) {
                $table->enum('tenant_type', [
                    'platform',           // Us - the platform owner
                    'reseller',          // Mandanten who bring customers
                    'direct_customer',   // Direct customers without reseller
                    'reseller_customer'  // Customers brought by resellers
                ])->default('direct_customer')->after('name');
            }
            
            if (!Schema::hasColumn('tenants', 'commission_rate')) {
                $table->decimal('commission_rate', 5, 2)->default(0)->after('balance_cents');
            }
            
            if (!Schema::hasColumn('tenants', 'base_cost_cents')) {
                $table->integer('base_cost_cents')->nullable();
            }
            
            if (!Schema::hasColumn('tenants', 'reseller_markup_cents')) {
                $table->integer('reseller_markup_cents')->nullable();
            }
            
            if (!Schema::hasColumn('tenants', 'can_set_prices')) {
                $table->boolean('can_set_prices')->default(false);
            }
            
            if (!Schema::hasColumn('tenants', 'min_markup_percent')) {
                $table->integer('min_markup_percent')->default(10);
            }
            
            if (!Schema::hasColumn('tenants', 'max_markup_percent')) {
                $table->integer('max_markup_percent')->default(100);
            }
            
            if (!Schema::hasColumn('tenants', 'billing_mode')) {
                $table->enum('billing_mode', ['direct', 'through_reseller'])->default('direct');
            }
            
            if (!Schema::hasColumn('tenants', 'auto_commission_payout')) {
                $table->boolean('auto_commission_payout')->default(false);
            }
            
            if (!Schema::hasColumn('tenants', 'commission_payout_threshold_cents')) {
                $table->integer('commission_payout_threshold_cents')->default(5000);
            }
        });
        
        // Add foreign key and index in separate statement
        Schema::table('tenants', function (Blueprint $table) {
            // Check if foreign key already exists using raw SQL
            $foreignKeyName = 'tenants_parent_tenant_id_foreign';
            $hasForeignKey = DB::select(
                "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE 
                 WHERE TABLE_NAME = 'tenants' 
                 AND COLUMN_NAME = 'parent_tenant_id' 
                 AND REFERENCED_TABLE_NAME = 'tenants'
                 AND CONSTRAINT_NAME = ?
                 AND TABLE_SCHEMA = DATABASE()",
                [$foreignKeyName]
            );
            
            if (empty($hasForeignKey)) {
                $table->foreign('parent_tenant_id')->references('id')->on('tenants')->onDelete('set null');
            }
            
            // Add index if it doesn't exist
            $indexName = 'tenants_parent_tenant_id_tenant_type_index';
            $hasIndex = collect(DB::select("SHOW INDEX FROM tenants WHERE Key_name = ?", [$indexName]))->isNotEmpty();
            
            if (!$hasIndex) {
                $table->index(['parent_tenant_id', 'tenant_type'], $indexName);
            }
        });

        // 2. Create tenant pricing tiers table
        if (!Schema::hasTable('tenant_pricing_tiers')) {
            Schema::create('tenant_pricing_tiers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('child_tenant_id')->nullable(); // Specific pricing for a child
            
            // Pricing per service type
            $table->integer('call_cost_cents')->nullable(); // Per minute
            $table->integer('api_cost_cents')->nullable(); // Per call
            $table->integer('appointment_cost_cents')->nullable(); // Per booking
            
            // Override pricing plan
            $table->foreignId('custom_pricing_plan_id')->nullable()->constrained('pricing_plans');
            
            // Volume discounts for this relationship
            $table->integer('volume_threshold_minutes')->default(0);
            $table->decimal('volume_discount_percent', 5, 2)->default(0);
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('child_tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->unique(['tenant_id', 'child_tenant_id']);
            });
        }

        // 3. Link companies to tenants (for reseller relationships)
        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                $table->unsignedBigInteger('reseller_tenant_id')->nullable()->after('tenant_id');
                
                $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('set null');
                $table->foreign('reseller_tenant_id')->references('id')->on('tenants')->onDelete('set null');
                $table->index(['tenant_id', 'reseller_tenant_id']);
            }
        });

        // 4. Link customers to tenants
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                $table->unsignedBigInteger('assigned_by_tenant_id')->nullable(); // Which reseller assigned them
                
                $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('set null');
                $table->foreign('assigned_by_tenant_id')->references('id')->on('tenants')->onDelete('set null');
                $table->index(['tenant_id', 'assigned_by_tenant_id']);
            }
        });

        // 5. Enhance transactions for multi-party billing
        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'reseller_tenant_id')) {
                $table->unsignedBigInteger('reseller_tenant_id')->nullable()->after('tenant_id');
                $table->integer('commission_amount_cents')->default(0)->after('amount_cents');
                $table->integer('base_cost_cents')->nullable(); // Platform cost
                $table->integer('reseller_revenue_cents')->nullable(); // Reseller earnings
                $table->unsignedBigInteger('parent_transaction_id')->nullable(); // Link related transactions
                
                $table->foreign('reseller_tenant_id')->references('id')->on('tenants')->onDelete('set null');
                $table->index(['reseller_tenant_id', 'type']);
            }
        });

        // 6. Commission tracking and payouts
        if (!Schema::hasTable('commission_ledger')) {
            Schema::create('commission_ledger', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reseller_tenant_id');
            $table->unsignedBigInteger('customer_tenant_id')->nullable();
            $table->unsignedBigInteger('transaction_id');
            
            // Amounts
            $table->integer('gross_amount_cents'); // Total charged to customer
            $table->integer('platform_cost_cents'); // What platform charges reseller
            $table->integer('commission_cents'); // Reseller earnings
            $table->decimal('commission_rate', 5, 2); // Rate at time of transaction
            
            // Status
            $table->enum('status', ['pending', 'approved', 'paid', 'disputed'])->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('payout_reference')->nullable();
            
            // Context
            $table->string('description');
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            $table->foreign('reseller_tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('customer_tenant_id')->references('id')->on('tenants')->onDelete('set null');
            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('cascade');
            
            $table->index(['reseller_tenant_id', 'status']);
            $table->index('created_at');
            });
        }

        // 7. Reseller settlement/payout tracking
        if (!Schema::hasTable('reseller_payouts')) {
            Schema::create('reseller_payouts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reseller_tenant_id');
            $table->integer('amount_cents');
            $table->integer('commission_entries_count');
            $table->date('period_start');
            $table->date('period_end');
            
            // Payment details
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->enum('payout_method', ['bank_transfer', 'stripe', 'manual'])->default('bank_transfer');
            $table->string('payout_reference')->nullable();
            $table->json('payout_details')->nullable();
            
            // Accounting
            $table->integer('total_gross_cents');
            $table->integer('total_platform_cost_cents');
            $table->integer('total_commission_cents');
            $table->decimal('average_commission_rate', 5, 2);
            
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            
            $table->foreign('reseller_tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index(['reseller_tenant_id', 'status']);
            $table->index(['period_start', 'period_end']);
            });
        }

        // 8. Add reseller-specific settings
        if (!Schema::hasTable('reseller_settings')) {
            Schema::create('reseller_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->unique();
            
            // Branding
            $table->boolean('white_label_enabled')->default(false);
            $table->string('brand_name')->nullable();
            $table->string('brand_logo_url')->nullable();
            $table->json('brand_colors')->nullable();
            
            // Billing preferences
            $table->boolean('invoice_customers_directly')->default(false);
            $table->boolean('show_platform_branding')->default(true);
            $table->enum('payout_frequency', ['weekly', 'biweekly', 'monthly'])->default('monthly');
            $table->integer('payout_day')->default(1); // Day of month/week
            
            // Customer management
            $table->boolean('can_create_customers')->default(true);
            $table->boolean('can_delete_customers')->default(false);
            $table->integer('max_customers')->default(0); // 0 = unlimited
            
            // Notification preferences
            $table->json('notification_emails')->nullable();
            $table->boolean('notify_on_customer_usage')->default(true);
            $table->boolean('notify_on_low_customer_balance')->default(true);
            $table->boolean('notify_on_commission_payout')->default(true);
            
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            });
        }

        // 9. Seed initial platform tenant if not exists
        if (!DB::table('tenants')->where('tenant_type', 'platform')->exists()) {
            DB::table('tenants')->insert([
                'name' => 'AskProAI Platform',
                'slug' => 'askproai-platform',
                'tenant_type' => 'platform',
                'api_key_hash' => Hash::make('platform_internal_key_' . Str::random(32)),
                'balance_cents' => 0,
                'commission_rate' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 10. Update existing tenants to have proper type
        DB::table('tenants')
            ->whereNull('tenant_type')
            ->update(['tenant_type' => 'direct_customer']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop new tables first
        Schema::dropIfExists('reseller_settings');
        Schema::dropIfExists('reseller_payouts');
        Schema::dropIfExists('commission_ledger');
        Schema::dropIfExists('tenant_pricing_tiers');

        // Remove foreign keys and columns from existing tables
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'reseller_tenant_id')) {
                $table->dropForeign(['reseller_tenant_id']);
                $table->dropColumn([
                    'reseller_tenant_id', 
                    'commission_amount_cents',
                    'base_cost_cents',
                    'reseller_revenue_cents',
                    'parent_transaction_id'
                ]);
            }
        });

        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'tenant_id')) {
                $table->dropForeign(['tenant_id']);
                $table->dropForeign(['assigned_by_tenant_id']);
                $table->dropColumn(['tenant_id', 'assigned_by_tenant_id']);
            }
        });

        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'tenant_id')) {
                $table->dropForeign(['tenant_id']);
                $table->dropForeign(['reseller_tenant_id']);
                $table->dropColumn(['tenant_id', 'reseller_tenant_id']);
            }
        });

        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'parent_tenant_id')) {
                $table->dropForeign(['parent_tenant_id']);
                $table->dropColumn([
                    'parent_tenant_id',
                    'tenant_type',
                    'commission_rate',
                    'base_cost_cents',
                    'reseller_markup_cents',
                    'can_set_prices',
                    'min_markup_percent',
                    'max_markup_percent',
                    'billing_mode',
                    'auto_commission_payout',
                    'commission_payout_threshold_cents'
                ]);
            }
        });
    }
};