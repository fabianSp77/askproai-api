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
        Schema::create('billing_spending_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained()->onDelete('cascade');
            
            // Limit-Einstellungen
            $table->decimal('daily_limit', 15, 2)->nullable(); // Tageslimit (null = unbegrenzt)
            $table->decimal('weekly_limit', 15, 2)->nullable(); // Wochenlimit
            $table->decimal('monthly_limit', 15, 2)->nullable(); // Monatslimit
            
            // Alert-Schwellenwerte (JSON Array z.B. [50, 80, 100])
            $table->json('alert_thresholds')->default('[]');
            
            // Aktuelle Verbrauchswerte
            $table->decimal('current_day_spent', 15, 2)->default(0.00);
            $table->decimal('current_week_spent', 15, 2)->default(0.00);
            $table->decimal('current_month_spent', 15, 2)->default(0.00);
            
            // Alert-Tracking
            $table->integer('last_daily_alert_level')->default(0);
            $table->integer('last_weekly_alert_level')->default(0);
            $table->integer('last_monthly_alert_level')->default(0);
            
            // Reset-Zeitpunkte
            $table->date('current_day_date')->default(now()->toDateString());
            $table->date('current_week_start')->default(now()->startOfWeek()->toDateString());
            $table->date('current_month_start')->default(now()->startOfMonth()->toDateString());
            
            // Benachrichtigungen
            $table->boolean('send_alerts')->default(true);
            $table->timestamp('last_alert_sent_at')->nullable();
            
            // Hard/Soft Limits
            $table->boolean('hard_limit')->default(false); // true = Blockiert weitere Nutzung
            
            $table->timestamps();
            
            // Indices
            $table->index('company_id');
            $table->index(['current_day_date', 'current_week_start', 'current_month_start'], 'idx_spending_limits_dates');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_spending_limits');
    }
};
