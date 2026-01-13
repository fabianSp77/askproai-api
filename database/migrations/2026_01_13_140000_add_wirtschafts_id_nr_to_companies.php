<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds the German Wirtschafts-Identifikationsnummer (W-IdNr) field.
     * This is a unique 11-digit identifier for businesses introduced in 2024,
     * distinct from the Steuernummer (tax_number) and USt-IdNr (vat_id).
     *
     * Format: XXX/XXX/XXXXX (e.g., 114/139/00281)
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('wirtschafts_id_nr', 20)
                ->nullable()
                ->after('vat_id')
                ->comment('Wirtschafts-Identifikationsnummer (W-IdNr, seit 2024)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('wirtschafts_id_nr');
        });
    }
};
