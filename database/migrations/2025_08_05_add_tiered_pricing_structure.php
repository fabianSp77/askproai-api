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
        // Create tiered pricing structure for resellers
        Schema::create('company_pricing_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->comment('The reseller company');
            $table->foreignId('child_company_id')->nullable()->constrained('companies')->comment('The client company, null for reseller own pricing');
            $table->enum('pricing_type', ['inbound', 'outbound', 'sms', 'monthly', 'setup']);
            $table->decimal('cost_price', 10, 4)->comment('What the reseller pays');
            $table->decimal('sell_price', 10, 4)->comment('What the end customer pays');
            $table->decimal('setup_fee', 10, 2)->default(0);
            $table->decimal('monthly_fee', 10, 2)->default(0);
            $table->integer('included_minutes')->default(0);
            $table->decimal('overage_rate', 10, 4)->nullable()->comment('Rate for minutes over included amount');
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable()->comment('Additional pricing rules or conditions');
            $table->timestamps();
            
            $table->unique(['company_id', 'child_company_id', 'pricing_type'], 'company_pricing_unique');
            $table->index(['company_id', 'is_active']);
            $table->index('child_company_id');
        });

        // Add pricing margin tracking
        Schema::create('pricing_margins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_pricing_tier_id')->constrained();
            $table->decimal('margin_amount', 10, 4);
            $table->decimal('margin_percentage', 5, 2);
            $table->date('calculated_date');
            $table->timestamps();
            
            $table->index(['company_pricing_tier_id', 'calculated_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_margins');
        Schema::dropIfExists('company_pricing_tiers');
    }
};