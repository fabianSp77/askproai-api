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
        Schema::create('customer_relationships', function (Blueprint $table) {
            $table->id();
            
            // Die zwei verknüpften Kunden
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('related_customer_id')->constrained('customers')->onDelete('cascade');
            
            // Firma für Gruppierung
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            
            // Beziehungstyp
            $table->enum('relationship_type', [
                'same_person',      // Gleiche Person
                'same_company',     // Gleiche Firma
                'phone_match',      // Nur Telefonnummer gleich
                'possible_match'    // Mögliche Übereinstimmung
            ]);
            
            // Confidence Score (0-100)
            $table->integer('confidence_score')->default(50);
            
            // Matching-Details als JSON
            $table->json('matching_details')->nullable();
            
            // Status der Verknüpfung
            $table->enum('status', [
                'auto_detected',    // Automatisch erkannt
                'user_confirmed',   // Vom Benutzer bestätigt
                'user_rejected',    // Vom Benutzer abgelehnt
                'merged'           // Zusammengeführt
            ])->default('auto_detected');
            
            // Wer hat die Verknüpfung erstellt/bestätigt
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            
            $table->timestamps();
            
            // Indizes für Performance
            $table->index(['customer_id', 'related_customer_id']);
            $table->index(['company_id', 'relationship_type']);
            $table->index(['confidence_score', 'status']);
            
            // Verhindere doppelte Beziehungen
            $table->unique(['customer_id', 'related_customer_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_relationships');
    }
};
