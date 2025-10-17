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
        
        if (!Schema::hasTable('calls')) {
            return;
        }

        Schema::table('calls', function (Blueprint $table) {
            // Add profit tracking fields only if they don't exist
            if (!Schema::hasColumn('calls', 'platform_profit')) {
                $table->integer('platform_profit')->nullable()
                    ->comment('Platform profit (reseller_cost - base_cost or customer_cost - base_cost)');
            }

            if (!Schema::hasColumn('calls', 'reseller_profit')) {
                $table->integer('reseller_profit')->nullable()
                    ->comment('Reseller/Mandant profit (customer_cost - reseller_cost)');
            }

            if (!Schema::hasColumn('calls', 'total_profit')) {
                $table->integer('total_profit')->nullable()
                    ->comment('Total profit (customer_cost - base_cost)');
            }

            if (!Schema::hasColumn('calls', 'profit_margin_platform')) {
                $table->decimal('profit_margin_platform', 5, 2)->nullable()
                    ->comment('Platform profit margin percentage');
            }

            if (!Schema::hasColumn('calls', 'profit_margin_reseller')) {
                $table->decimal('profit_margin_reseller', 5, 2)->nullable()
                    ->comment('Reseller profit margin percentage');
            }

            if (!Schema::hasColumn('calls', 'profit_margin_total')) {
                $table->decimal('profit_margin_total', 5, 2)->nullable()
                    ->comment('Total profit margin percentage');
            }

            // Add index only if columns exist
            if (!Schema::hasIndex('calls', 'idx_profit_tracking') &&
                Schema::hasColumn('calls', 'platform_profit')) {
                $table->index(['platform_profit', 'reseller_profit', 'total_profit'], 'idx_profit_tracking');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            // Drop index first
            $table->dropIndex('idx_profit_tracking');

            // Drop columns
            $table->dropColumn([
                'platform_profit',
                'reseller_profit',
                'total_profit',
                'profit_margin_platform',
                'profit_margin_reseller',
                'profit_margin_total'
            ]);
        });
    }
};
