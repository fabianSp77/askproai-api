<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add Hair Salon specific fields to services table
 * 
 * Adds fields for:
 * - Consultation requirements
 * - Multi-block service support
 * - Service metadata storage
 * - Service type categorization
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // Hair salon specific fields
            $table->boolean('consultation_required')->default(false)->after('is_online_bookable');
            $table->boolean('multi_block')->default(false)->after('consultation_required');
            $table->string('service_type')->nullable()->after('multi_block');
            $table->json('metadata')->nullable()->after('service_type');
            
            // Add index for better performance
            $table->index(['company_id', 'active', 'consultation_required'], 'services_company_active_consultation_idx');
            $table->index(['company_id', 'category', 'active'], 'services_company_category_active_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('services_company_active_consultation_idx');
            $table->dropIndex('services_company_category_active_idx');
            
            // Drop columns
            $table->dropColumn([
                'consultation_required',
                'multi_block', 
                'service_type',
                'metadata'
            ]);
        });
    }
};