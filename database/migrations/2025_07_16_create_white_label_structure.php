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
        // Add white-label fields to companies table
        Schema::table('companies', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_company_id')->nullable()->after('id');
            $table->enum('company_type', ['standalone', 'reseller', 'client'])->default('standalone')->after('parent_company_id');
            $table->boolean('is_white_label')->default(false)->after('company_type');
            $table->json('white_label_settings')->nullable()->after('is_white_label');
            $table->decimal('commission_rate', 5, 2)->default(0.00)->after('white_label_settings')->comment('Commission rate for reseller in %');
            
            // Indexes
            $table->index('parent_company_id');
            $table->index('company_type');
            // Foreign key will be added after the column is created
        });
        
        // Create reseller permissions table
        Schema::create('reseller_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reseller_company_id');
            $table->string('permission');
            $table->boolean('is_allowed')->default(true);
            $table->timestamps();
            
            $table->index('reseller_company_id');
            $table->unique(['reseller_company_id', 'permission']);
        });
        
        // Add cross-company access for portal users
        Schema::table('portal_users', function (Blueprint $table) {
            $table->boolean('can_access_child_companies')->default(false)->after('is_active');
            $table->json('accessible_company_ids')->nullable()->after('can_access_child_companies');
        });
        
        // Add foreign keys after all columns are created
        Schema::table('companies', function (Blueprint $table) {
            $table->foreign('parent_company_id')->references('id')->on('companies')->onDelete('cascade');
        });
        
        Schema::table('reseller_permissions', function (Blueprint $table) {
            $table->foreign('reseller_company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('portal_users', function (Blueprint $table) {
            $table->dropColumn(['can_access_child_companies', 'accessible_company_ids']);
        });
        
        Schema::dropIfExists('reseller_permissions');
        
        Schema::table('companies', function (Blueprint $table) {
            $table->dropForeign(['parent_company_id']);
            $table->dropColumn([
                'parent_company_id',
                'company_type',
                'is_white_label',
                'white_label_settings',
                'commission_rate'
            ]);
        });
    }
};