<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Service Fee Templates - Preiskatalog für standardisierte Service-Gebühren
 *
 * Ermöglicht:
 * - Zentrale Pflege von Standard-Services und Preisen
 * - Kategorisierung nach Leistungsart
 * - Verschiedene Abrechnungsmodelle (einmalig, monatlich, pro Einheit)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_fee_templates', function (Blueprint $table) {
            $table->id();

            // Identifikation
            $table->string('code', 50)->unique()->comment('Eindeutiger Code z.B. SETUP_BASIC');
            $table->string('name')->comment('Anzeigename');
            $table->text('description')->nullable()->comment('Detaillierte Beschreibung');

            // Kategorisierung
            $table->string('category', 50)->comment('setup, change, support, capacity, integration');
            $table->string('subcategory', 50)->nullable()->comment('Unterkategorie');

            // Preisgestaltung
            $table->decimal('default_price', 10, 2)->comment('Standard-Preis in EUR');
            $table->string('pricing_type', 20)->default('one_time')
                ->comment('one_time, monthly, yearly, per_hour, per_unit');
            $table->string('unit_name', 50)->nullable()->comment('Einheitsname bei per_unit (z.B. "Ticket", "GB")');

            // Optionen
            $table->decimal('min_price', 10, 2)->nullable()->comment('Mindestpreis');
            $table->decimal('max_price', 10, 2)->nullable()->comment('Höchstpreis');
            $table->boolean('is_negotiable')->default(true)->comment('Preis verhandelbar?');
            $table->boolean('requires_approval')->default(false)->comment('Freigabe erforderlich?');

            // Anzeige
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false)->comment('Prominent anzeigen');

            // Metadaten
            $table->json('metadata')->nullable()->comment('Zusätzliche Konfiguration');

            $table->timestamps();

            // Indizes
            $table->index('category');
            $table->index('pricing_type');
            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_fee_templates');
    }
};
