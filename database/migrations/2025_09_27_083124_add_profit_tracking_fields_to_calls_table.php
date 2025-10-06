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
            // Profit-Tracking Felder
            $table->integer('platform_profit')->nullable()
                ->comment('Platform profit (reseller_cost - base_cost or customer_cost - base_cost)');

            $table->integer('reseller_profit')->nullable()
                ->comment('Reseller/Mandant profit (customer_cost - reseller_cost)');

            $table->integer('total_profit')->nullable()
                ->comment('Total profit (customer_cost - base_cost)');

            $table->decimal('profit_margin_platform', 5, 2)->nullable()
                ->comment('Platform profit margin percentage');

            $table->decimal('profit_margin_reseller', 5, 2)->nullable()
                ->comment('Reseller profit margin percentage');

            $table->decimal('profit_margin_total', 5, 2)->nullable()
                ->comment('Total profit margin percentage');

            // Index für Performance bei Profit-Abfragen
            $table->index(['platform_profit', 'reseller_profit', 'total_profit'], 'idx_profit_tracking');
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
