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
        Schema::create('company_pricings', function (Blueprint $table) {
            $table->id();
            $table->uuid('company_id');
            
            // Pricing model
            $table->enum('pricing_model', ['per_minute', 'per_appointment', 'package', 'combined'])
                ->default('per_minute');
            
            // Base pricing
            $table->decimal('base_fee', 10, 2)->default(0);
            $table->integer('included_minutes')->default(0);
            
            // Rate-based pricing
            $table->decimal('per_minute_rate', 10, 4)->default(0.10);
            $table->decimal('per_appointment_rate', 10, 2)->default(2.00);
            
            // Package pricing
            $table->integer('package_minutes')->default(0);
            $table->integer('package_appointments')->default(0);
            
            // Overage rates
            $table->decimal('overage_per_minute', 10, 4)->nullable();
            $table->decimal('overage_per_appointment', 10, 2)->nullable();
            
            // Validity period
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            
            // Metadata
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['company_id', 'is_active']);
            $table->index(['valid_from', 'valid_until']);
            
            // Foreign key commented out due to different ID types
            // $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_pricings');
    }
};