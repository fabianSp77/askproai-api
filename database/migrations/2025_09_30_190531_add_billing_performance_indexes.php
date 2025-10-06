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
        // Invoice Performance Indexes
        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                // Status filtering for navigation badges and stats
                if (!$this->indexExists('invoices', 'idx_invoices_status')) {
                    $table->index(['status'], 'idx_invoices_status');
                }

                // Issue date with status for revenue queries
                if (!$this->indexExists('invoices', 'idx_invoices_issue_date_status')) {
                    $table->index(['issue_date', 'status'], 'idx_invoices_issue_date_status');
                }

                // Created at for trend calculations
                if (!$this->indexExists('invoices', 'idx_invoices_created_at')) {
                    $table->index(['created_at'], 'idx_invoices_created_at');
                }
            });
        }

        // Transaction Performance Indexes
        if (Schema::hasTable('transactions')) {
            Schema::table('transactions', function (Blueprint $table) {
                // Created at with type for stats and trends
                if (!$this->indexExists('transactions', 'idx_transactions_created_type')) {
                    $table->index(['created_at', 'type'], 'idx_transactions_created_type');
                }

                // Tenant ID with created at for tenant-specific queries
                if (!$this->indexExists('transactions', 'idx_transactions_tenant_created')) {
                    $table->index(['tenant_id', 'created_at'], 'idx_transactions_tenant_created');
                }

                // Type only for filtering
                if (!$this->indexExists('transactions', 'idx_transactions_type')) {
                    $table->index(['type'], 'idx_transactions_type');
                }
            });
        }

        // Balance Topup Performance Indexes
        if (Schema::hasTable('balance_topups')) {
            Schema::table('balance_topups', function (Blueprint $table) {
                // Status for navigation badge and filtering
                if (!$this->indexExists('balance_topups', 'idx_balance_topups_status')) {
                    $table->index(['status'], 'idx_balance_topups_status');
                }

                // Created at with status for stats and trends
                if (!$this->indexExists('balance_topups', 'idx_balance_topups_created_status')) {
                    $table->index(['created_at', 'status'], 'idx_balance_topups_created_status');
                }
            });
        }

        // Tenant Performance Indexes (for pricing plan queries)
        if (Schema::hasTable('tenants')) {
            Schema::table('tenants', function (Blueprint $table) {
                // Pricing plan with is_active for subscription queries
                if (!$this->indexExists('tenants', 'idx_tenants_pricing_plan_active')) {
                    $table->index(['pricing_plan', 'is_active'], 'idx_tenants_pricing_plan_active');
                }
            });
        }

        // Pricing Plan Performance Indexes
        if (Schema::hasTable('pricing_plans')) {
            Schema::table('pricing_plans', function (Blueprint $table) {
                // Is active for navigation badge
                if (!$this->indexExists('pricing_plans', 'idx_pricing_plans_is_active')) {
                    $table->index(['is_active'], 'idx_pricing_plans_is_active');
                }

                // Internal name for tenant lookups
                if (!$this->indexExists('pricing_plans', 'idx_pricing_plans_internal_name')) {
                    $table->index(['internal_name'], 'idx_pricing_plans_internal_name');
                }
            });
        }

        // Balance Bonus Tier Performance Indexes
        if (Schema::hasTable('balance_bonus_tiers')) {
            Schema::table('balance_bonus_tiers', function (Blueprint $table) {
                // Is active for navigation badge
                if (!$this->indexExists('balance_bonus_tiers', 'idx_balance_bonus_tiers_active')) {
                    $table->index(['is_active'], 'idx_balance_bonus_tiers_active');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                if ($this->indexExists('invoices', 'idx_invoices_status')) {
                    $table->dropIndex('idx_invoices_status');
                }
                if ($this->indexExists('invoices', 'idx_invoices_issue_date_status')) {
                    $table->dropIndex('idx_invoices_issue_date_status');
                }
                if ($this->indexExists('invoices', 'idx_invoices_created_at')) {
                    $table->dropIndex('idx_invoices_created_at');
                }
            });
        }

        if (Schema::hasTable('transactions')) {
            Schema::table('transactions', function (Blueprint $table) {
                if ($this->indexExists('transactions', 'idx_transactions_created_type')) {
                    $table->dropIndex('idx_transactions_created_type');
                }
                if ($this->indexExists('transactions', 'idx_transactions_tenant_created')) {
                    $table->dropIndex('idx_transactions_tenant_created');
                }
                if ($this->indexExists('transactions', 'idx_transactions_type')) {
                    $table->dropIndex('idx_transactions_type');
                }
            });
        }

        if (Schema::hasTable('balance_topups')) {
            Schema::table('balance_topups', function (Blueprint $table) {
                if ($this->indexExists('balance_topups', 'idx_balance_topups_status')) {
                    $table->dropIndex('idx_balance_topups_status');
                }
                if ($this->indexExists('balance_topups', 'idx_balance_topups_created_status')) {
                    $table->dropIndex('idx_balance_topups_created_status');
                }
            });
        }

        if (Schema::hasTable('tenants')) {
            Schema::table('tenants', function (Blueprint $table) {
                if ($this->indexExists('tenants', 'idx_tenants_pricing_plan_active')) {
                    $table->dropIndex('idx_tenants_pricing_plan_active');
                }
            });
        }

        if (Schema::hasTable('pricing_plans')) {
            Schema::table('pricing_plans', function (Blueprint $table) {
                if ($this->indexExists('pricing_plans', 'idx_pricing_plans_is_active')) {
                    $table->dropIndex('idx_pricing_plans_is_active');
                }
                if ($this->indexExists('pricing_plans', 'idx_pricing_plans_internal_name')) {
                    $table->dropIndex('idx_pricing_plans_internal_name');
                }
            });
        }

        if (Schema::hasTable('balance_bonus_tiers')) {
            Schema::table('balance_bonus_tiers', function (Blueprint $table) {
                if ($this->indexExists('balance_bonus_tiers', 'idx_balance_bonus_tiers_active')) {
                    $table->dropIndex('idx_balance_bonus_tiers_active');
                }
            });
        }
    }

    /**
     * Check if index exists
     */
    private function indexExists($table, $index): bool
    {
        $indexes = Schema::getIndexes($table);
        return collect($indexes)->pluck('name')->contains($index);
    }
};
