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
        // Cleanup any existing tables first
        Schema::dropIfExists('conversion_targets');
        Schema::dropIfExists('billing_periods');
        Schema::dropIfExists('branch_pricing_overrides');
        Schema::dropIfExists('company_pricing');
        
        // Preismodelle für Companies
        Schema::create('company_pricing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->decimal('price_per_minute', 10, 4)->comment('Preis pro Minute in EUR');
            $table->decimal('setup_fee', 10, 2)->nullable()->comment('Einrichtungsgebühr');
            $table->decimal('monthly_base_fee', 10, 2)->nullable()->comment('Monatliche Grundgebühr');
            $table->integer('included_minutes')->default(0)->comment('Inkludierte Minuten pro Monat');
            $table->decimal('overage_price_per_minute', 10, 4)->nullable()->comment('Preis für Minuten über Kontingent');
            $table->boolean('is_active')->default(true);
            $table->date('valid_from')->default(now());
            $table->date('valid_until')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['company_id', 'is_active', 'valid_from']);
        });
        
        // Spezielle Preise für einzelne Branches (optional)
        Schema::create('branch_pricing_overrides', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->foreignId('company_pricing_id')->nullable()->constrained('company_pricing');
            $table->decimal('price_per_minute', 10, 4)->nullable()->comment('Überschreibt Company-Preis');
            $table->integer('included_minutes')->nullable()->comment('Überschreibt Company-Kontingent');
            $table->boolean('is_active')->default(true);
            $table->date('valid_from')->default(now());
            $table->date('valid_until')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['branch_id', 'is_active']);
        });
        
        // Kosten-Tracking für Abrechnungsperioden
        Schema::create('billing_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->date('period_start');
            $table->date('period_end');
            $table->integer('total_minutes')->default(0);
            $table->integer('included_minutes')->default(0);
            $table->integer('overage_minutes')->default(0);
            $table->decimal('total_cost', 10, 2)->comment('Unsere Kosten');
            $table->decimal('total_revenue', 10, 2)->comment('Kundenpreis');
            $table->decimal('margin', 10, 2)->comment('Gewinn');
            $table->decimal('margin_percentage', 5, 2)->comment('Gewinnmarge in %');
            $table->boolean('is_invoiced')->default(false);
            $table->timestamp('invoiced_at')->nullable();
            $table->timestamps();
            
            $table->index(['company_id', 'period_start']);
            $table->index(['branch_id', 'period_start']);
        });
        
        // Zielwerte für Terminbuchungsquoten
        Schema::create('conversion_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->decimal('target_conversion_rate', 5, 2)->default(30.00)->comment('Ziel-Terminbuchungsquote in %');
            $table->decimal('min_conversion_rate', 5, 2)->default(20.00)->comment('Minimum akzeptable Quote in %');
            $table->boolean('alert_on_low_conversion')->default(true);
            $table->string('alert_email')->nullable();
            $table->timestamps();
            
            $table->unique(['company_id', 'branch_id']);
        });
        
        // Erweitere companies Tabelle
        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies', 'billing_type')) {
                $table->enum('billing_type', ['prepaid', 'postpaid'])->default('postpaid')->after('is_active');
            }
            if (!Schema::hasColumn('companies', 'credit_balance')) {
                $table->decimal('credit_balance', 10, 2)->default(0)->after('billing_type')->comment('Guthaben für Prepaid-Kunden');
            }
            if (!Schema::hasColumn('companies', 'low_credit_threshold')) {
                $table->decimal('low_credit_threshold', 10, 2)->default(10.00)->after('credit_balance');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['billing_type', 'credit_balance', 'low_credit_threshold']);
        });
        
        Schema::dropIfExists('conversion_targets');
        Schema::dropIfExists('billing_periods');
        Schema::dropIfExists('branch_pricing_overrides');
        Schema::dropIfExists('company_pricing');
    }
};