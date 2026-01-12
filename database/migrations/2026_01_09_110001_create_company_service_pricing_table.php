<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Company Service Pricing - Kundenspezifische Preise mit Zeiträumen
 *
 * Ermöglicht:
 * - Individuelle Preise pro Kunde
 * - Zeitlich begrenzte Sonderkonditionen
 * - Rabattvereinbarungen
 * - Vertragslaufzeiten
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_service_pricing', function (Blueprint $table) {
            $table->id();

            // Verknüpfungen
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('service_fee_templates')->nullOnDelete();

            // Custom Service (wenn kein Template)
            $table->string('custom_code', 50)->nullable()->comment('Eigener Code wenn custom');
            $table->string('custom_name')->nullable()->comment('Eigener Name wenn custom');
            $table->text('custom_description')->nullable();

            // Preisgestaltung
            $table->decimal('price', 10, 2)->comment('Kundenspezifischer Preis');
            $table->decimal('discount_percentage', 5, 2)->nullable()->comment('Rabatt in %');
            $table->decimal('final_price', 10, 2)->storedAs('price * (1 - COALESCE(discount_percentage, 0) / 100)')
                ->comment('Berechneter Endpreis');

            // Zeitraum
            $table->date('effective_from')->comment('Gültig ab');
            $table->date('effective_until')->nullable()->comment('Gültig bis (null = unbegrenzt)');

            // Kontext
            $table->string('contract_reference', 100)->nullable()->comment('Vertragsnummer/-referenz');
            $table->text('notes')->nullable()->comment('Interne Notizen zur Vereinbarung');
            $table->string('approved_by_name')->nullable()->comment('Wer hat diese Konditionen genehmigt');

            // Status
            $table->boolean('is_active')->default(true);

            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Indizes (kurze Namen wegen MySQL Limit)
            $table->index(['company_id', 'effective_from', 'effective_until'], 'csp_company_validity_idx');
            $table->index(['template_id', 'is_active'], 'csp_template_active_idx');
            $table->index('effective_from', 'csp_eff_from_idx');
            $table->index('effective_until', 'csp_eff_until_idx');

            // Unique: Pro Company nur ein aktiver Preis pro Template/Zeitraum
            // (wird in Model validiert da überlappende Zeiträume komplex sind)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_service_pricing');
    }
};
