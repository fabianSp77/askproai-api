<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Consolidated companies and branches tables
     * Companies represent the main tenant organizations
     * Branches represent multiple locations per customer
     */
    public function up(): void
    {
        // Companies - Main tenant organizations
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id');
            $table->string('name');
            $table->text('address')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->json('opening_hours')->nullable();
            $table->string('calcom_api_key')->nullable();
            $table->string('calcom_user_id')->nullable();
            $table->string('retell_api_key')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            
            // Indexes for performance
            $table->index('tenant_id');
            $table->index(['tenant_id', 'active']);
            $table->index(['tenant_id', 'name']);
        });

        // Branches - Multiple locations per customer
        Schema::create('branches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('customer_id')->index();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('phone')->nullable();
            $table->string('phone_number')->nullable(); // Legacy field
            $table->boolean('active')->default(false);
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index('tenant_id');
            $table->index('customer_id');
            $table->index(['tenant_id', 'active']);
            $table->index(['tenant_id', 'city']);
            
            // Unique constraint for slug per tenant
            $table->unique(['tenant_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
        Schema::dropIfExists('companies');
    }
};