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
        // Prepaid Balance für jede Company
        Schema::create('prepaid_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained()->onDelete('cascade');
            $table->decimal('balance', 15, 2)->default(0.00); // Aktuelles Guthaben
            $table->decimal('reserved_balance', 15, 2)->default(0.00); // Reserviert für laufende Anrufe
            $table->decimal('low_balance_threshold', 15, 2)->default(20.00); // Warnschwelle
            $table->timestamp('last_warning_sent_at')->nullable();
            $table->timestamps();
            
            $table->index('company_id');
        });

        // Alle Transaktionen
        Schema::create('balance_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['topup', 'charge', 'refund', 'adjustment', 'reservation', 'release']);
            $table->decimal('amount', 15, 2); // Positiv für Gutschrift, Negativ für Belastung
            $table->decimal('balance_before', 15, 2);
            $table->decimal('balance_after', 15, 2);
            $table->string('description');
            $table->string('reference_type')->nullable(); // z.B. 'call', 'topup', 'manual'
            $table->string('reference_id')->nullable(); // z.B. call_id, topup_id
            $table->foreignId('created_by')->nullable()->constrained('portal_users');
            $table->timestamps();
            
            $table->index(['company_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });

        // Aufladungen via Stripe
        Schema::create('balance_topups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('EUR');
            $table->enum('status', ['pending', 'processing', 'succeeded', 'failed', 'cancelled']);
            $table->string('stripe_payment_intent_id')->unique()->nullable();
            $table->string('stripe_checkout_session_id')->unique()->nullable();
            $table->json('stripe_response')->nullable();
            $table->foreignId('initiated_by')->constrained('portal_users');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            
            $table->index(['company_id', 'status']);
            $table->index('stripe_payment_intent_id');
        });

        // Abrechnungssätze pro Company
        Schema::create('billing_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained()->onDelete('cascade');
            $table->decimal('rate_per_minute', 10, 4)->default(0.42); // 0,42€ pro Minute
            $table->integer('billing_increment')->default(1); // Sekundengenau = 1
            $table->decimal('minimum_charge', 10, 2)->default(0.00); // Mindestgebühr pro Anruf
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('company_id');
        });

        // Abrechnung pro Anruf
        Schema::create('call_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('call_id')->unique()->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->integer('duration_seconds');
            $table->decimal('rate_per_minute', 10, 4);
            $table->decimal('amount_charged', 15, 2);
            $table->foreignId('balance_transaction_id')->nullable()->constrained();
            $table->timestamp('charged_at');
            $table->timestamps();
            
            $table->index(['company_id', 'charged_at']);
            $table->index('call_id');
        });

        // Balance Warning für companies table
        if (!Schema::hasColumn('companies', 'balance_warning_sent_at')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->timestamp('balance_warning_sent_at')->nullable();
            });
        }
        
        if (!Schema::hasColumn('companies', 'prepaid_billing_enabled')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->boolean('prepaid_billing_enabled')->default(false);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('companies', 'balance_warning_sent_at')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropColumn('balance_warning_sent_at');
            });
        }
        
        if (Schema::hasColumn('companies', 'prepaid_billing_enabled')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropColumn('prepaid_billing_enabled');
            });
        }
        
        Schema::dropIfExists('call_charges');
        Schema::dropIfExists('billing_rates');
        Schema::dropIfExists('balance_topups');
        Schema::dropIfExists('balance_transactions');
        Schema::dropIfExists('prepaid_balances');
    }
};
