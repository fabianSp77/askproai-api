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
        Schema::create('billing_bonus_rules', function (Blueprint $table) {
            $table->id();
            
            // Company-spezifisch oder global (null = global)
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            
            // Regel-Name für interne Verwaltung
            $table->string('name');
            $table->text('description')->nullable();
            
            // Bonus-Konfiguration
            $table->decimal('min_amount', 10, 2); // Ab welchem Aufladebetrag
            $table->decimal('max_amount', 10, 2)->nullable(); // Bis zu welchem Betrag (null = unbegrenzt)
            $table->decimal('bonus_percentage', 5, 2); // Bonus in Prozent
            $table->decimal('max_bonus_amount', 10, 2)->nullable(); // Maximaler Bonus-Betrag
            
            // Bedingungen
            $table->boolean('is_first_time_only')->default(false); // Nur für erste Aufladung
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0); // Höhere Priorität wird zuerst angewendet
            
            // Gültigkeitszeitraum
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            
            // Tracking
            $table->integer('times_used')->default(0);
            $table->decimal('total_bonus_given', 15, 2)->default(0.00);
            
            $table->timestamps();
            
            // Indices
            $table->index(['company_id', 'is_active', 'priority']);
            $table->index(['valid_from', 'valid_until']);
            $table->index('min_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_bonus_rules');
    }
};
