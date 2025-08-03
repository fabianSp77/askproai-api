<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all portal users
        $portalUsers = DB::table('portal_users')->get();
        
        foreach ($portalUsers as $portalUser) {
            // Check if user already exists in users table
            $existingUser = User::where('email', $portalUser->email)->first();
            
            if ($existingUser) {
                // Update existing user with portal data
                $existingUser->update([
                    'phone' => $portalUser->phone ?? $existingUser->phone,
                    'portal_role' => $portalUser->role,
                    'legacy_permissions' => $portalUser->permissions,
                    'is_active' => $portalUser->is_active,
                    'can_access_child_companies' => $portalUser->can_access_child_companies ?? false,
                    'accessible_company_ids' => $portalUser->accessible_company_ids,
                    'settings' => $portalUser->settings,
                    'notification_preferences' => $portalUser->notification_preferences,
                    'call_notification_preferences' => $portalUser->call_notification_preferences,
                    'preferred_language' => $portalUser->preferred_language ?? 'de',
                    'timezone' => $portalUser->timezone ?? 'Europe/Berlin',
                    'two_factor_enforced' => $portalUser->two_factor_enforced,
                    'last_login_at' => $portalUser->last_login_at,
                    'last_login_ip' => $portalUser->last_login_ip,
                ]);
                
                $user = $existingUser;
            } else {
                // Create new user
                $user = User::create([
                    'name' => $portalUser->name,
                    'email' => $portalUser->email,
                    'password' => $portalUser->password, // Already hashed
                    'company_id' => $portalUser->company_id,
                    'phone' => $portalUser->phone,
                    'portal_role' => $portalUser->role,
                    'legacy_permissions' => $portalUser->permissions,
                    'is_active' => $portalUser->is_active,
                    'can_access_child_companies' => $portalUser->can_access_child_companies ?? false,
                    'accessible_company_ids' => $portalUser->accessible_company_ids,
                    'settings' => $portalUser->settings,
                    'notification_preferences' => $portalUser->notification_preferences,
                    'call_notification_preferences' => $portalUser->call_notification_preferences,
                    'preferred_language' => $portalUser->preferred_language ?? 'de',
                    'timezone' => $portalUser->timezone ?? 'Europe/Berlin',
                    'two_factor_secret' => $portalUser->two_factor_secret,
                    'two_factor_recovery_codes' => $portalUser->two_factor_recovery_codes,
                    'two_factor_confirmed_at' => $portalUser->two_factor_confirmed_at,
                    'two_factor_enforced' => $portalUser->two_factor_enforced,
                    'last_login_at' => $portalUser->last_login_at,
                    'last_login_ip' => $portalUser->last_login_ip,
                    'created_at' => $portalUser->created_at,
                    'updated_at' => $portalUser->updated_at,
                ]);
            }
            
            // Assign role based on portal role
            $roleMapping = [
                'owner' => 'company_owner',
                'admin' => 'company_admin',
                'manager' => 'company_manager',
                'staff' => 'company_staff',
            ];
            
            $newRole = $roleMapping[$portalUser->role] ?? 'company_staff';
            
            // Remove any existing company roles
            $companyRoles = ['company_owner', 'company_admin', 'company_manager', 'company_staff'];
            foreach ($companyRoles as $role) {
                if ($user->hasRole($role)) {
                    $user->removeRole($role);
                }
            }
            
            // Assign new role
            $user->assignRole($newRole);
            
            // Log migration
            \Log::info('Migrated portal user', [
                'email' => $portalUser->email,
                'old_role' => $portalUser->role,
                'new_role' => $newRole,
                'company_id' => $portalUser->company_id,
            ]);
        }
        
        // Create a backup table for portal_users
        if (!Schema::hasTable('portal_users_backup')) {
            DB::statement('CREATE TABLE portal_users_backup LIKE portal_users');
            DB::statement('INSERT INTO portal_users_backup SELECT * FROM portal_users');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is not reversible automatically
        // Manual intervention would be required to restore portal_users
        throw new \Exception('This migration cannot be automatically reversed. Please restore from portal_users_backup table manually.');
    }
};