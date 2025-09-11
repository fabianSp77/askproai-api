<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Consolidated services, working hours, calendars and relationships
     * Includes staff-service many-to-many and working hours per staff
     */
    public function up(): void
    {
        // Services - Available services with pricing
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('duration_minutes')->default(60);
            $table->integer('price_cents')->unsigned()->default(0);
            $table->decimal('price', 10, 2)->unsigned()->default(0.00); // Legacy decimal field
            $table->boolean('active')->default(true);
            $table->timestamps();
            
            // Indexes for performance
            $table->index('tenant_id');
            $table->index(['tenant_id', 'active']);
            $table->index(['tenant_id', 'name']);
        });

        // Staff Service Pivot Table - Many-to-many relationship
        Schema::create('staff_service', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id');
            $table->unsignedBigInteger('staff_id');
            $table->unsignedBigInteger('service_id');
            $table->timestamps();
            
            // Indexes for performance
            $table->index('tenant_id');
            $table->index('staff_id');
            $table->index('service_id');
            
            // Unique constraint to prevent duplicates
            $table->unique(['tenant_id', 'staff_id', 'service_id']);
        });

        // Working Hours - Staff availability schedule
        Schema::create('working_hours', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id');
            $table->unsignedBigInteger('staff_id');
            $table->tinyInteger('weekday'); // 0=Sunday, 1=Monday, ..., 6=Saturday
            $table->time('start');
            $table->time('end');
            $table->boolean('active')->default(true);
            $table->timestamps();
            
            // Indexes for performance
            $table->index('tenant_id');
            $table->index('staff_id');
            $table->index(['tenant_id', 'staff_id', 'weekday']);
            $table->index(['tenant_id', 'staff_id', 'active']);
        });

        // Calendars - External calendar integrations per staff
        Schema::create('calendars', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->unsignedBigInteger('staff_id')->index();
            $table->enum('provider', ['calcom', 'google', 'outlook'])->default('calcom');
            $table->text('api_key')->nullable();
            $table->string('event_type_id')->nullable();
            $table->string('external_user_id')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index('tenant_id');
            $table->index('staff_id');
            $table->index(['tenant_id', 'staff_id', 'provider']);
            $table->index(['tenant_id', 'active']);
        });

        // Branch Service Pivot Table - Services available per branch
        Schema::create('branch_service', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id');
            $table->uuid('branch_id');
            $table->unsignedBigInteger('service_id');
            $table->timestamps();
            
            // Indexes for performance
            $table->index('tenant_id');
            $table->index('branch_id');
            $table->index('service_id');
            
            // Unique constraint to prevent duplicates
            $table->unique(['tenant_id', 'branch_id', 'service_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_service');
        Schema::dropIfExists('calendars');
        Schema::dropIfExists('working_hours');
        Schema::dropIfExists('staff_service');
        Schema::dropIfExists('services');
    }
};