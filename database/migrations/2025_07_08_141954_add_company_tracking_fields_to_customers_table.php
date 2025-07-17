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
        Schema::table('customers', function (Blueprint $table) {
            // Firmenname für bessere Zuordnung
            $table->string('company_name')->nullable()->after('name');
            
            // Eindeutige Kundennummer pro Company
            $table->string('customer_number')->nullable()->after('company_name');
            
            // Alternative Telefonnummern als JSON
            $table->json('phone_variants')->nullable()->after('phone');
            
            // Matching-Confidence für letzte Zuordnung
            $table->integer('matching_confidence')->default(100)->after('phone_variants');
            
            // Anzahl der Anrufe (wird per Event aktualisiert)
            $table->integer('call_count')->default(0)->after('appointment_count');
            
            // Letzter Anruf
            $table->timestamp('last_call_at')->nullable()->after('last_seen_at');
            
            // Indizes für bessere Performance
            $table->index('company_name');
            $table->index('customer_number');
            $table->index(['company_id', 'company_name']);
            $table->index(['company_id', 'phone']);
            $table->index(['company_id', 'customer_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'customer_number']);
            $table->dropIndex(['company_id', 'phone']);
            $table->dropIndex(['company_id', 'company_name']);
            $table->dropIndex(['customer_number']);
            $table->dropIndex(['company_name']);
            
            $table->dropColumn([
                'company_name',
                'customer_number',
                'phone_variants',
                'matching_confidence',
                'call_count',
                'last_call_at'
            ]);
        });
    }
};
