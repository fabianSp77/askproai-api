<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Consolidated customers and phone numbers tables
     * Combines customer data with birthdate and contact information
     */
    public function up(): void
    {
        // Customers - Main customer records with multi-tenant support
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->date('birthdate')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index('tenant_id');
            $table->index(['tenant_id', 'email']);
            $table->index(['tenant_id', 'phone']);
            $table->index(['tenant_id', 'name']);
            
            // Unique constraint for email per tenant
            $table->unique(['tenant_id', 'email']);
        });

        // Phone Numbers - Multiple phone numbers per customer
        Schema::create('phone_numbers', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id');
            $table->unsignedBigInteger('customer_id');
            $table->string('phone_number', 20);
            $table->enum('type', ['mobile', 'home', 'work', 'other'])->default('mobile');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            
            // Indexes for performance
            $table->index('tenant_id');
            $table->index('customer_id');
            $table->index(['tenant_id', 'phone_number']);
            
            // Unique constraint for phone number per tenant
            $table->unique(['tenant_id', 'phone_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phone_numbers');
        Schema::dropIfExists('customers');
    }
};