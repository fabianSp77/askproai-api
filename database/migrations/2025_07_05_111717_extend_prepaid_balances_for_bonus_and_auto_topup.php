<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('prepaid_balances', function (Blueprint $table) {
            // Bonus-Guthaben (nicht auszahlbar)
            $table->decimal('bonus_balance', 15, 2)->default(0.00)->after('balance');
            
            // Auto-Topup Einstellungen
            $table->boolean('auto_topup_enabled')->default(false)->after('last_warning_sent_at');
            $table->decimal('auto_topup_threshold', 15, 2)->nullable()->after('auto_topup_enabled');
            $table->decimal('auto_topup_amount', 15, 2)->nullable()->after('auto_topup_threshold');
            $table->string('stripe_payment_method_id')->nullable()->after('auto_topup_amount');
            $table->timestamp('last_auto_topup_at')->nullable()->after('stripe_payment_method_id');
            $table->integer('auto_topup_daily_count')->default(0)->after('last_auto_topup_at');
            $table->decimal('auto_topup_monthly_limit', 15, 2)->default(5000.00)->after('auto_topup_daily_count');
            
            // Indices für Performance
            $table->index(['company_id', 'auto_topup_enabled']);
            $table->index('bonus_balance');
        });
        
        // Erweitere balance_transactions für Bonus-Tracking
        Schema::table('balance_transactions', function (Blueprint $table) {
            $table->boolean('affects_bonus')->default(false)->after('description');
            $table->decimal('bonus_amount', 15, 2)->default(0.00)->after('affects_bonus');
        });
        
        // Erweitere type enum nur für MySQL
        if (config('database.default') === 'mysql') {
            DB::statement("ALTER TABLE balance_transactions MODIFY COLUMN type ENUM('topup', 'charge', 'refund', 'adjustment', 'reservation', 'release', 'bonus', 'withdrawal') NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prepaid_balances', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'auto_topup_enabled']);
            $table->dropIndex(['bonus_balance']);
            
            $table->dropColumn([
                'bonus_balance',
                'auto_topup_enabled',
                'auto_topup_threshold',
                'auto_topup_amount',
                'stripe_payment_method_id',
                'last_auto_topup_at',
                'auto_topup_daily_count',
                'auto_topup_monthly_limit'
            ]);
        });
        
        Schema::table('balance_transactions', function (Blueprint $table) {
            $table->dropColumn(['affects_bonus', 'bonus_amount']);
        });
        
        // Revert type enum nur für MySQL
        if (config('database.default') === 'mysql') {
            DB::statement("ALTER TABLE balance_transactions MODIFY COLUMN type ENUM('topup', 'charge', 'refund', 'adjustment', 'reservation', 'release') NOT NULL");
        }
    }
};
