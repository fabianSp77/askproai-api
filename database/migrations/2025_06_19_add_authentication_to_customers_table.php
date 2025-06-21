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
        Schema::table('customers', function (Blueprint $table) {
            // Authentication fields
            if (!Schema::hasColumn('customers', 'password')) {
                $table->string('password')->nullable()->after('email');
            }
            
            if (!Schema::hasColumn('customers', 'remember_token')) {
                $table->rememberToken()->after('password');
            }
            
            if (!Schema::hasColumn('customers', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable()->after('remember_token');
            }
            
            // Portal access fields
            if (!Schema::hasColumn('customers', 'portal_enabled')) {
                $table->boolean('portal_enabled')->default(false)->after('email_verified_at');
            }
            
            if (!Schema::hasColumn('customers', 'portal_access_token')) {
                $table->string('portal_access_token')->nullable()->after('portal_enabled');
            }
            
            if (!Schema::hasColumn('customers', 'portal_token_expires_at')) {
                $table->timestamp('portal_token_expires_at')->nullable()->after('portal_access_token');
            }
            
            if (!Schema::hasColumn('customers', 'last_portal_login_at')) {
                $table->timestamp('last_portal_login_at')->nullable()->after('portal_token_expires_at');
            }
            
            if (!Schema::hasColumn('customers', 'preferred_language')) {
                $table->string('preferred_language', 5)->default('de')->after('last_portal_login_at');
            }
            
            // Indexes
            if (!Schema::hasIndex('customers', 'customers_email_index')) {
                $table->index('email');
            }
            
            if (!Schema::hasIndex('customers', 'customers_portal_access_token_index')) {
                $table->index('portal_access_token');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'password',
                'remember_token', 
                'email_verified_at',
                'portal_enabled',
                'portal_access_token',
                'portal_token_expires_at',
                'last_portal_login_at',
                'preferred_language'
            ]);
        });
    }
};