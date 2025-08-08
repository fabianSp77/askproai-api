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
        Schema::table('companies', function (Blueprint $table) {
            // Add commission type if it doesn't exist
            if (!Schema::hasColumn('companies', 'commission_type')) {
                $table->enum('commission_type', ['percentage', 'fixed', 'tiered'])
                    ->default('percentage')
                    ->after('commission_rate');
            }
            
            // Add contact person if it doesn't exist
            if (!Schema::hasColumn('companies', 'contact_person')) {
                $table->string('contact_person')->nullable()->after('email');
            }
            
            // Add logo field if it doesn't exist
            if (!Schema::hasColumn('companies', 'logo')) {
                $table->string('logo')->nullable()->after('name');
            }
            
            // Ensure company_type has default value
            if (Schema::hasColumn('companies', 'company_type')) {
                $table->string('company_type')->default('company')->change();
            } else {
                $table->string('company_type')->default('company')->after('parent_company_id');
            }
            
            // Add indexes for better performance
            if (!Schema::hasIndex('companies', ['company_type'])) {
                $table->index('company_type');
            }
            
            if (!Schema::hasIndex('companies', ['parent_company_id'])) {
                $table->index('parent_company_id');
            }
            
            if (!Schema::hasIndex('companies', ['is_active'])) {
                $table->index('is_active');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex(['company_type']);
            $table->dropIndex(['parent_company_id']);
            $table->dropIndex(['is_active']);
            
            $table->dropColumn([
                'commission_type',
                'contact_person', 
                'logo'
            ]);
        });
    }
};