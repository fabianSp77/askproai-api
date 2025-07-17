<?php

namespace App\Http\Controllers\Portal\Api;

use Illuminate\Http\Request;

class UserApiController extends BaseApiController
{
    /**
     * Get current user's permissions
     */
    public function permissions(Request $request)
    {
        $user = $this->getCurrentUser();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Get all permissions for the user
        $permissions = [];
        
        // For admin users (from web guard), grant all permissions
        if (auth()->guard('web')->check()) {
            // Admin users have all permissions
            $permissions = [
                'calls.view_all',
                'calls.edit_all', 
                'calls.export',
                'calls.export_sensitive',
                'calls.view_transcript',
                'calls.delete',
                'billing.view',
                'billing.view_costs',
                'billing.manage',
                'billing.export',
                'customers.view',
                'customers.edit',
                'customers.create',
                'customers.delete',
                'customers.export',
                'team.view',
                'team.manage',
                'team.permissions',
                'settings.view',
                'settings.edit',
                'settings.security',
                'audit.view',
                'audit.export',
                'analytics.view',
                'analytics.export',
                'analytics.financial',
            ];
        } else {
            // For portal users, get their actual permissions
            if (method_exists($user, 'portalPermissions')) {
                $permissions = $user->portalPermissions()->pluck('name')->toArray();
            }
            
            // Also add role-based permissions
            if (method_exists($user, 'hasPermission') && isset($user->role)) {
                // Add default permissions based on role
                if (defined('\App\Models\PortalUser::ROLE_PERMISSIONS')) {
                    $rolePermissions = \App\Models\PortalUser::ROLE_PERMISSIONS[$user->role] ?? [];
                    $permissions = array_unique(array_merge($permissions, $rolePermissions));
                }
            }
            
            // If no permissions found, add default view permissions
            if (empty($permissions)) {
                $permissions = [
                    'calls.view_own',
                    'billing.view',
                    'settings.view',
                ];
            }
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role ?? 'admin',
                'role_display' => $user->role_display ?? 'Administrator',
                'is_admin' => isAdminViewingPortal(),
                'can_delete_business_data' => canDeleteBusinessData(),
            ],
            'permissions' => $permissions,
        ]);
    }
}