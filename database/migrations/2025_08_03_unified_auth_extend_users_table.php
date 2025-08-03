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
        Schema::table('users', function (Blueprint $table) {
            // Add fields from portal_users that don't exist in users table
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->after('email');
            }
            
            if (!Schema::hasColumn('users', 'role')) {
                // Temporary field for migration - will use Spatie permissions
                $table->string('portal_role')->nullable()->after('password');
            }
            
            if (!Schema::hasColumn('users', 'permissions')) {
                // Legacy permissions field from portal_users
                $table->json('legacy_permissions')->nullable();
            }
            
            if (!Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
            
            if (!Schema::hasColumn('users', 'can_access_child_companies')) {
                $table->boolean('can_access_child_companies')->default(false);
            }
            
            if (!Schema::hasColumn('users', 'accessible_company_ids')) {
                $table->json('accessible_company_ids')->nullable();
            }
            
            if (!Schema::hasColumn('users', 'settings')) {
                $table->json('settings')->nullable();
            }
            
            if (!Schema::hasColumn('users', 'notification_preferences')) {
                $table->json('notification_preferences')->nullable();
            }
            
            if (!Schema::hasColumn('users', 'call_notification_preferences')) {
                $table->json('call_notification_preferences')->nullable();
            }
            
            if (!Schema::hasColumn('users', 'preferred_language')) {
                $table->string('preferred_language', 5)->default('de');
            }
            
            if (!Schema::hasColumn('users', 'timezone')) {
                $table->string('timezone')->default('Europe/Berlin');
            }
            
            // Add indexes for performance
            $table->index('phone');
            $table->index('is_active');
            $table->index(['company_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['phone']);
            $table->dropIndex(['is_active']);
            $table->dropIndex(['company_id', 'is_active']);
            
            // Drop columns
            $table->dropColumn([
                'phone',
                'portal_role',
                'legacy_permissions',
                'is_active',
                'can_access_child_companies',
                'accessible_company_ids',
                'settings',
                'notification_preferences',
                'call_notification_preferences',
                'preferred_language',
                'timezone'
            ]);
        });
    }
};