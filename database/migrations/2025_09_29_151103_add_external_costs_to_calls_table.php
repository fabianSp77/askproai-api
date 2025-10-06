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
            // External service costs
            $table->decimal('retell_cost_usd', 10, 4)->nullable()
                
                ->comment('Cost from Retell.ai in USD');
            $table->integer('retell_cost_eur_cents')->nullable()
                
                ->comment('Retell cost converted to EUR cents');
            $table->decimal('twilio_cost_usd', 10, 4)->nullable()
                
                ->comment('Twilio telephony cost in USD');
            $table->integer('twilio_cost_eur_cents')->nullable()
                
                ->comment('Twilio cost converted to EUR cents');
            $table->decimal('exchange_rate_used', 10, 6)->nullable()
                
                ->comment('USD to EUR exchange rate used');
            $table->integer('total_external_cost_eur_cents')->nullable()
                
                ->comment('Total external costs in EUR cents');

            // Add index for cost queries
            $table->index(['created_at', 'total_external_cost_eur_cents']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->dropColumn([
                'retell_cost_usd',
                'retell_cost_eur_cents',
                'twilio_cost_usd',
                'twilio_cost_eur_cents',
                'exchange_rate_used',
                'total_external_cost_eur_cents'
            ]);
        });
    }
};
