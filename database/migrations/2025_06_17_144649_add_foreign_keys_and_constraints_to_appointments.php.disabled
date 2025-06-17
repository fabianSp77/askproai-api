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
        // Add foreign key constraints to appointments table
        Schema::table('appointments', function (Blueprint $table) {
            // Check if foreign keys already exist before adding
            $existingForeignKeys = collect(Schema::getConnection()->getDoctrineSchemaManager()->listTableForeignKeys('appointments'))
                ->map(fn($fk) => $fk->getName())->toArray();
            
            if (!in_array('appointments_company_id_foreign', $existingForeignKeys)) {
                $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            }
            
            if (!in_array('appointments_branch_id_foreign', $existingForeignKeys)) {
                $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            }
            
            if (!in_array('appointments_staff_id_foreign', $existingForeignKeys)) {
                $table->foreign('staff_id')->references('id')->on('staff')->onDelete('set null');
            }
            
            if (!in_array('appointments_service_id_foreign', $existingForeignKeys)) {
                $table->foreign('service_id')->references('id')->on('services')->onDelete('set null');
            }
            
            if (!in_array('appointments_call_id_foreign', $existingForeignKeys)) {
                $table->foreign('call_id')->references('id')->on('calls')->onDelete('set null');
            }
            
            // Add unique constraint to prevent double booking
            // Unique constraint on staff_id + starts_at + status (where status != 'cancelled')
            $table->index(['staff_id', 'starts_at', 'status'], 'idx_staff_time_status');
            
            // Add composite indexes for common queries
            $table->index(['company_id', 'starts_at'], 'idx_company_starts_at');
            $table->index(['customer_id', 'starts_at'], 'idx_customer_starts_at');
            $table->index(['branch_id', 'starts_at'], 'idx_branch_starts_at');
            $table->index(['status', 'starts_at'], 'idx_status_starts_at');
        });
        
        // Add foreign key constraints to calls table
        Schema::table('calls', function (Blueprint $table) {
            $existingForeignKeys = collect(Schema::getConnection()->getDoctrineSchemaManager()->listTableForeignKeys('calls'))
                ->map(fn($fk) => $fk->getName())->toArray();
            
            if (!in_array('calls_company_id_foreign', $existingForeignKeys)) {
                $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            }
            
            if (!in_array('calls_branch_id_foreign', $existingForeignKeys)) {
                $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            }
            
            if (!in_array('calls_staff_id_foreign', $existingForeignKeys)) {
                $table->foreign('staff_id')->references('id')->on('staff')->onDelete('set null');
            }
            
            if (!in_array('calls_appointment_id_foreign', $existingForeignKeys)) {
                $table->foreign('appointment_id')->references('id')->on('appointments')->onDelete('set null');
            }
            
            // Add indexes for common queries
            $table->index(['company_id', 'created_at'], 'idx_company_created_at');
            $table->index(['retell_call_id'], 'idx_retell_call_id');
            $table->index(['from_number', 'created_at'], 'idx_from_number_created');
        });
        
        // Add foreign key constraints to customers table
        Schema::table('customers', function (Blueprint $table) {
            $existingForeignKeys = collect(Schema::getConnection()->getDoctrineSchemaManager()->listTableForeignKeys('customers'))
                ->map(fn($fk) => $fk->getName())->toArray();
            
            if (!in_array('customers_company_id_foreign', $existingForeignKeys)) {
                $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            }
            
            // Add unique constraint on phone + company_id to prevent duplicates
            $table->unique(['phone', 'company_id'], 'uniq_phone_company');
            
            // Add indexes
            $table->index(['email', 'company_id'], 'idx_email_company');
        });
        
        // Add foreign key constraints to staff table
        Schema::table('staff', function (Blueprint $table) {
            $existingForeignKeys = collect(Schema::getConnection()->getDoctrineSchemaManager()->listTableForeignKeys('staff'))
                ->map(fn($fk) => $fk->getName())->toArray();
            
            if (!in_array('staff_company_id_foreign', $existingForeignKeys)) {
                $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            }
            
            if (!in_array('staff_home_branch_id_foreign', $existingForeignKeys)) {
                $table->foreign('home_branch_id')->references('id')->on('branches')->onDelete('set null');
            }
        });
        
        // Add foreign key constraints to services table
        Schema::table('services', function (Blueprint $table) {
            $existingForeignKeys = collect(Schema::getConnection()->getDoctrineSchemaManager()->listTableForeignKeys('services'))
                ->map(fn($fk) => $fk->getName())->toArray();
            
            if (!in_array('services_company_id_foreign', $existingForeignKeys)) {
                $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            }
            
            if (!in_array('services_branch_id_foreign', $existingForeignKeys)) {
                $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            }
        });
        
        // Add foreign key constraints to branches table
        Schema::table('branches', function (Blueprint $table) {
            $existingForeignKeys = collect(Schema::getConnection()->getDoctrineSchemaManager()->listTableForeignKeys('branches'))
                ->map(fn($fk) => $fk->getName())->toArray();
            
            if (!in_array('branches_company_id_foreign', $existingForeignKeys)) {
                $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign keys from appointments
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['staff_id']);
            $table->dropForeign(['service_id']);
            $table->dropForeign(['call_id']);
            
            $table->dropIndex('idx_staff_time_status');
            $table->dropIndex('idx_company_starts_at');
            $table->dropIndex('idx_customer_starts_at');
            $table->dropIndex('idx_branch_starts_at');
            $table->dropIndex('idx_status_starts_at');
        });
        
        // Drop foreign keys from calls
        Schema::table('calls', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['staff_id']);
            $table->dropForeign(['appointment_id']);
            
            $table->dropIndex('idx_company_created_at');
            $table->dropIndex('idx_retell_call_id');
            $table->dropIndex('idx_from_number_created');
        });
        
        // Drop foreign keys from customers
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropUnique('uniq_phone_company');
            $table->dropIndex('idx_email_company');
        });
        
        // Drop foreign keys from staff
        Schema::table('staff', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['home_branch_id']);
        });
        
        // Drop foreign keys from services
        Schema::table('services', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['branch_id']);
        });
        
        // Drop foreign keys from branches
        Schema::table('branches', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
        });
    }
};