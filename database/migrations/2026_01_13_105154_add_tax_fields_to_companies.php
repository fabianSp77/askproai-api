<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds German tax compliance fields required for §14 UStG invoicing:
     * - tax_number: German Steuernummer (e.g., "123/456/78901")
     * - vat_id: EU VAT ID / USt-IdNr. (e.g., "DE123456789")
     * - trade_register: Commercial register number (e.g., "HRB 12345")
     * - trade_register_court: Register court (e.g., "Amtsgericht Berlin")
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('tax_number', 50)
                ->nullable()
                ->after('partner_payment_terms_days')
                ->comment('Steuernummer (§14 UStG)');

            $table->string('vat_id', 20)
                ->nullable()
                ->after('tax_number')
                ->comment('USt-IdNr. / EU VAT ID');

            $table->string('trade_register', 100)
                ->nullable()
                ->after('vat_id')
                ->comment('Handelsregisternummer (z.B. HRB 12345)');

            $table->string('trade_register_court', 100)
                ->nullable()
                ->after('trade_register')
                ->comment('Registergericht (z.B. Amtsgericht Berlin)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'tax_number',
                'vat_id',
                'trade_register',
                'trade_register_court',
            ]);
        });
    }
};
