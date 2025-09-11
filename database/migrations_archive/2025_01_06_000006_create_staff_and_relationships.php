<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Consolidated staff table with relationships
     * Includes home branch assignment and branch-staff pivot table
     */
    public function up(): void
    {
        // Staff - Employee records with multi-tenant support
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id');
            $table->uuid('branch_id')->nullable()->index(); // Legacy field
            $table->uuid('home_branch_id')->nullable()->index();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index('tenant_id');
            $table->index(['tenant_id', 'active']);
            $table->index(['tenant_id', 'email']);
            $table->index(['tenant_id', 'name']);
            
            // Unique constraint for email per tenant
            $table->unique(['tenant_id', 'email']);
        });

        // Branch Staff Pivot Table - Many-to-many relationship between branches and staff
        Schema::create('branch_staff', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id');
            $table->uuid('branch_id');
            $table->unsignedBigInteger('staff_id');
            $table->timestamps();
            
            // Indexes for performance
            $table->index('tenant_id');
            $table->index('branch_id');
            $table->index('staff_id');
            
            // Unique constraint to prevent duplicate relationships
            $table->unique(['tenant_id', 'branch_id', 'staff_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_staff');
        Schema::dropIfExists('staff');
    }
};