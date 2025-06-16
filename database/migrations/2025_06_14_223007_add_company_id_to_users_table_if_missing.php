<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if laravel_users table exists and add company_id if missing
        if (Schema::hasTable('laravel_users') && !Schema::hasColumn('laravel_users', 'company_id')) {
            Schema::table('laravel_users', function (Blueprint $table) {
                $table->unsignedBigInteger('company_id')->nullable()->after('tenant_id');
                $table->index('company_id');
            });
            
            // Update existing users to have a company_id
            // Since the relationship is companies->hasMany(tenants), we need to find the company differently
            $users = DB::table('laravel_users')->whereNull('company_id')->get();
            foreach ($users as $user) {
                $companyId = null;
                
                if ($user->tenant_id) {
                    // Try to find company that has this tenant
                    $tenant = DB::table('tenants')->where('id', $user->tenant_id)->first();
                    if ($tenant) {
                        // Assuming tenants have a company_id column or similar relationship
                        // For now, just use the first active company
                        $companyId = DB::table('companies')->where('is_active', 1)->value('id');
                    }
                } else {
                    // No tenant, use first active company
                    $companyId = DB::table('companies')->where('is_active', 1)->value('id');
                }
                
                if ($companyId) {
                    DB::table('laravel_users')
                        ->where('id', $user->id)
                        ->update(['company_id' => $companyId]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('laravel_users') && Schema::hasColumn('laravel_users', 'company_id')) {
            Schema::table('laravel_users', function (Blueprint $table) {
                $table->dropColumn('company_id');
            });
        }
    }
};
