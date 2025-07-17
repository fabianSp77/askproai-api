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
        Schema::table('billing_rates', function (Blueprint $table) {
            // Grundgebühr (default 0€ für reines Prepaid)
            $table->decimal('base_fee', 10, 2)->default(0.00)->after('rate_per_minute');
            
            // Paket-Modell Einstellungen
            $table->integer('package_minutes')->default(0)->after('base_fee');
            $table->decimal('package_price', 10, 2)->default(0.00)->after('package_minutes');
            
            // Overage-Rate für Minuten über Paket hinaus
            $table->decimal('overage_rate_per_minute', 10, 4)->nullable()->after('package_price');
            
            // Billing-Typ
            $table->enum('billing_type', ['prepaid', 'package', 'hybrid'])->default('prepaid')->after('overage_rate_per_minute');
            
            // Gültigkeitszeitraum für zeitbasierte Preise
            $table->timestamp('valid_from')->nullable()->after('is_active');
            $table->timestamp('valid_until')->nullable()->after('valid_from');
            
            // Index für zeitbasierte Abfragen
            $table->index(['company_id', 'valid_from', 'valid_until']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('billing_rates', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'valid_from', 'valid_until']);
            
            $table->dropColumn([
                'base_fee',
                'package_minutes',
                'package_price',
                'overage_rate_per_minute',
                'billing_type',
                'valid_from',
                'valid_until'
            ]);
        });
    }
};
