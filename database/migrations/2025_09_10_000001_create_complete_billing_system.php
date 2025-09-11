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
        // 1. Pricing Plans - Verschiedene Preismodelle
        if (!Schema::hasTable('pricing_plans')) {
            Schema::create('pricing_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            
            // Basis-Preise (in Cents)
            $table->integer('price_per_minute_cents')->default(42); // 0.42€ pro Minute
            $table->integer('price_per_call_cents')->default(10);
            $table->integer('price_per_appointment_cents')->default(100);
            $table->integer('setup_fee_cents')->default(0);
            
            // Paket-Optionen
            $table->integer('included_minutes')->default(0);
            $table->decimal('monthly_fee', 10, 2)->default(0);
            $table->integer('overage_rate_cents')->nullable(); // Preis für Minuten über Paket
            
            // Mengenrabatte
            $table->integer('volume_discount_percent')->default(0);
            $table->integer('volume_threshold_minutes')->default(0);
            
            // Billing-Typ
            $table->enum('billing_type', ['prepaid', 'postpaid', 'hybrid'])->default('prepaid');
            $table->integer('billing_increment_seconds')->default(1); // Abrechnungs-Takt
            
            // Features als JSON
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            
            $table->timestamps();
            
            $table->index(['slug', 'is_active']);
        });
        }

        // 2. Tenant Pricing Plan Zuordnung
        if (Schema::hasTable('tenants')) {
            Schema::table('tenants', function (Blueprint $table) {
                if (!Schema::hasColumn('tenants', 'pricing_plan_id')) {
                    $table->foreignId('pricing_plan_id')->nullable()->constrained('pricing_plans');
                }
                if (!Schema::hasColumn('tenants', 'custom_rates')) {
                    $table->json('custom_rates')->nullable(); // Überschreibt Plan-Preise
                }
                if (!Schema::hasColumn('tenants', 'credit_limit_cents')) {
                    $table->integer('credit_limit_cents')->default(0); // Kredit-Limit für Postpaid
                }
            });
        }

        // 3. Balance Topups - Aufladungen
        if (!Schema::hasTable('balance_topups')) {
            Schema::create('balance_topups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('EUR');
            $table->enum('status', ['pending', 'processing', 'succeeded', 'failed', 'cancelled'])
                  ->default('pending');
            
            // Stripe Integration
            $table->string('stripe_payment_intent_id')->nullable()->unique();
            $table->string('stripe_checkout_session_id')->nullable()->unique();
            $table->json('stripe_response')->nullable();
            
            // Zusätzliche Infos
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('initiated_by')->nullable(); // User ID
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_method')->nullable(); // card, sepa, etc.
            
            // Bonus
            $table->decimal('bonus_amount', 10, 2)->default(0);
            $table->string('bonus_reason')->nullable();
            
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index(['tenant_id', 'status']);
            $table->index('created_at');
        });
        }

        // 4. Transactions - Alle Transaktionen (Guthaben-Bewegungen)
        if (!Schema::hasTable('transactions')) {
            Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->enum('type', [
                'topup',        // Aufladung
                'usage',        // Verbrauch
                'refund',       // Erstattung
                'adjustment',   // Manuelle Anpassung
                'bonus',        // Bonus-Gutschrift
                'fee'           // Gebühr
            ]);
            
            $table->integer('amount_cents'); // Positiv = Gutschrift, Negativ = Belastung
            $table->integer('balance_before_cents');
            $table->integer('balance_after_cents');
            
            $table->string('description');
            $table->json('metadata')->nullable(); // Zusätzliche Daten (z.B. Call ID)
            
            // Referenzen
            $table->foreignId('topup_id')->nullable()->constrained('balance_topups');
            $table->unsignedBigInteger('call_id')->nullable();
            $table->unsignedBigInteger('appointment_id')->nullable();
            
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index(['tenant_id', 'created_at']);
            $table->index('type');
        });
        }

        // 5. Billing Periods - Abrechnungszeiträume
        if (!Schema::hasTable('billing_periods')) {
            Schema::create('billing_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['open', 'closed', 'invoiced'])->default('open');
            
            // Zusammenfassung
            $table->integer('total_minutes')->default(0);
            $table->integer('total_calls')->default(0);
            $table->integer('total_appointments')->default(0);
            $table->integer('total_cost_cents')->default(0);
            $table->integer('total_topups_cents')->default(0);
            
            // Invoice Referenz
            $table->unsignedBigInteger('invoice_id')->nullable();
            
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->unique(['tenant_id', 'start_date', 'end_date']);
            $table->index('status');
        });
        }

        // 6. Invoices - Rechnungen
        if (!Schema::hasTable('invoices')) {
            Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('invoice_number')->unique();
            $table->enum('status', ['draft', 'sent', 'paid', 'overdue', 'cancelled'])->default('draft');
            
            // Beträge
            $table->decimal('subtotal', 15, 2);
            $table->decimal('tax_rate', 5, 2)->default(19.00); // Deutsche MwSt
            $table->decimal('tax_amount', 15, 2);
            $table->decimal('total', 15, 2);
            
            // Daten
            $table->date('invoice_date');
            $table->date('due_date');
            $table->date('paid_date')->nullable();
            
            // Kunde Info (Snapshot)
            $table->json('customer_details'); // Name, Adresse etc.
            
            // PDF
            $table->string('pdf_path')->nullable();
            $table->string('stripe_invoice_id')->nullable();
            
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index(['tenant_id', 'status']);
            $table->index('invoice_date');
        });
        }

        // 7. Invoice Items - Rechnungspositionen
        if (!Schema::hasTable('invoice_items')) {
            Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->string('description');
            $table->integer('quantity');
            $table->string('unit')->default('minutes');
            $table->decimal('unit_price', 10, 4);
            $table->decimal('total', 15, 2);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index('invoice_id');
        });
        }

        // 8. Billing Alerts - Benachrichtigungen
        if (!Schema::hasTable('billing_alerts')) {
            Schema::create('billing_alerts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->enum('type', [
                'low_balance',      // Niedriger Kontostand
                'balance_depleted', // Guthaben aufgebraucht
                'high_usage',       // Hoher Verbrauch
                'payment_failed',   // Zahlung fehlgeschlagen
                'invoice_created',  // Rechnung erstellt
                'payment_reminder'  // Zahlungserinnerung
            ]);
            
            $table->enum('severity', ['info', 'warning', 'critical'])->default('info');
            $table->string('title');
            $table->text('message');
            $table->json('metadata')->nullable();
            
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->boolean('email_sent')->default(false);
            
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index(['tenant_id', 'is_read']);
            $table->index('created_at');
        });
        }

        // 9. Billing Settings - Konfiguration pro Tenant
        if (!Schema::hasTable('billing_settings')) {
            Schema::create('billing_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->unique();
            
            // Alert Schwellwerte
            $table->integer('low_balance_threshold_cents')->default(1000); // 10€
            $table->integer('high_usage_threshold_minutes')->default(1000);
            
            // Email Einstellungen
            $table->boolean('send_low_balance_alerts')->default(true);
            $table->boolean('send_usage_reports')->default(true);
            $table->boolean('send_invoices')->default(true);
            $table->json('alert_email_addresses')->nullable();
            
            // Auto-Topup
            $table->boolean('auto_topup_enabled')->default(false);
            $table->integer('auto_topup_amount_cents')->default(5000); // 50€
            $table->integer('auto_topup_threshold_cents')->default(500); // Bei < 5€
            
            // Rechnungsstellung
            $table->enum('invoice_frequency', ['monthly', 'quarterly', 'yearly'])->default('monthly');
            $table->integer('invoice_day_of_month')->default(1);
            
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
        }

        // 10. Payment Methods - Gespeicherte Zahlungsmethoden
        if (!Schema::hasTable('payment_methods')) {
            Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('stripe_payment_method_id')->unique();
            $table->enum('type', ['card', 'sepa_debit', 'paypal'])->default('card');
            $table->boolean('is_default')->default(false);
            
            // Anzeige-Info
            $table->string('brand')->nullable(); // visa, mastercard, etc.
            $table->string('last4')->nullable();
            $table->string('exp_month', 2)->nullable();
            $table->string('exp_year', 4)->nullable();
            
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index(['tenant_id', 'is_default']);
        });
        }

        // Seed Default Pricing Plan
        if (!DB::table('pricing_plans')->where('slug', 'standard')->exists()) {
            DB::table('pricing_plans')->insert([
            'name' => 'Standard Plan',
            'slug' => 'standard',
            'description' => 'Standard-Preismodell für alle Kunden',
            'price_per_minute_cents' => 42,
            'price_per_call_cents' => 10,
            'price_per_appointment_cents' => 100,
            'billing_type' => 'prepaid',
            'is_default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign keys first
        if (Schema::hasTable('tenants')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->dropForeign(['pricing_plan_id']);
                $table->dropColumn(['pricing_plan_id', 'custom_rates', 'credit_limit_cents']);
            });
        }

        // Drop tables in reverse order
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('billing_settings');
        Schema::dropIfExists('billing_alerts');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('billing_periods');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('balance_topups');
        Schema::dropIfExists('pricing_plans');
    }
};