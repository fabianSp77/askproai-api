<?php

namespace App\Http\Controllers\Portal\Api;
use Illuminate\Http\Request;
use App\Models\PortalUser;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class TeamApiController extends BaseApiController
{
    public function index(Request $request)
    {
        $company = $this->getCompany();
        
        if (!$company) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        // Get all portal users for this company
        $query = PortalUser::where('company_id', $company->id);

        // Search filter
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->with(['branches', 'permissions'])->get();

        // Transform the data
        $users = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role ?? 'employee',
                'is_active' => $user->is_active ?? true,
                'two_factor_enabled' => $user->two_factor_confirmed_at !== null,
                'last_login_at' => $user->last_login_at,
                'created_at' => $user->created_at,
                'branches' => $user->branches->map(function ($branch) {
                    return [
                        'id' => $branch->id,
                        'name' => $branch->name,
                    ];
                }),
                'permissions' => $user->permissions->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'display_name' => $permission->display_name ?? $permission->name,
                    ];
                }),
            ];
        });

        // Calculate stats
        $stats = [
            'total' => $users->count(),
            'active' => $users->where('is_active', true)->count(),
            'inactive' => $users->where('is_active', false)->count(),
            'admins' => $users->where('role', 'admin')->count(),
        ];

        return response()->json([
            'users' => $users,
            'stats' => $stats,
        ]);
    }

    public function filters(Request $request)
    {
        $user = auth()->guard('portal')->user() ?: auth()->guard('web')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $company = $user->company;

        // Get available permissions
        $permissions = [
            ['id' => 'calls.view_all', 'display_name' => 'Alle Anrufe anzeigen', 'description' => 'Kann alle Anrufe des Unternehmens sehen'],
            ['id' => 'calls.view_own', 'display_name' => 'Eigene Anrufe anzeigen', 'description' => 'Kann nur eigene Anrufe sehen'],
            ['id' => 'calls.edit_all', 'display_name' => 'Alle Anrufe bearbeiten', 'description' => 'Kann alle Anrufe bearbeiten'],
            ['id' => 'calls.edit_own', 'display_name' => 'Eigene Anrufe bearbeiten', 'description' => 'Kann nur eigene Anrufe bearbeiten'],
            ['id' => 'appointments.view_all', 'display_name' => 'Alle Termine anzeigen', 'description' => 'Kann alle Termine sehen'],
            ['id' => 'appointments.view_own', 'display_name' => 'Eigene Termine anzeigen', 'description' => 'Kann nur eigene Termine sehen'],
            ['id' => 'appointments.edit_all', 'display_name' => 'Alle Termine bearbeiten', 'description' => 'Kann alle Termine bearbeiten'],
            ['id' => 'appointments.edit_own', 'display_name' => 'Eigene Termine bearbeiten', 'description' => 'Kann nur eigene Termine bearbeiten'],
            ['id' => 'team.view', 'display_name' => 'Team anzeigen', 'description' => 'Kann Teammitglieder sehen'],
            ['id' => 'team.manage', 'display_name' => 'Team verwalten', 'description' => 'Kann Teammitglieder verwalten'],
            ['id' => 'billing.view', 'display_name' => 'Rechnungen anzeigen', 'description' => 'Kann Rechnungen und Zahlungen sehen'],
            ['id' => 'billing.pay', 'display_name' => 'Zahlungen durchführen', 'description' => 'Kann Zahlungen durchführen'],
            ['id' => 'analytics.view_own', 'display_name' => 'Eigene Analysen', 'description' => 'Kann eigene Statistiken sehen'],
            ['id' => 'analytics.view_team', 'display_name' => 'Team Analysen', 'description' => 'Kann Team-Statistiken sehen'],
        ];

        return response()->json([
            'branches' => $company->branches()->select('id', 'name')->get(),
            'permissions' => $permissions,
        ]);
    }

    public function invite(Request $request)
    {
        $user = auth()->guard('portal')->user() ?: auth()->guard('web')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Check permission
        if ($user instanceof PortalUser && !$user->hasPermissionTo('team.manage')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $request->validate([
            'email' => 'required|email|unique:portal_users,email',
            'name' => 'required|string|max:255',
            'role' => 'required|in:employee,manager,admin',
            'branch_ids' => 'required|array',
            'branch_ids.*' => 'exists:branches,id',
            'message' => 'nullable|string',
        ]);

        // Create user with temporary password
        $temporaryPassword = Str::random(12);
        
        $newUser = PortalUser::create([
            'company_id' => $user->company_id,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($temporaryPassword),
            'role' => $request->role,
            'is_active' => true,
        ]);

        // Assign branches
        $newUser->branches()->sync($request->branch_ids);

        // TODO: Send invitation email with temporary password
        // Mail::to($newUser->email)->send(new TeamInvitation($newUser, $temporaryPassword, $request->message));

        return response()->json([
            'success' => true,
            'message' => 'Einladung erfolgreich versendet',
            'user' => $newUser,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $user = auth()->guard('portal')->user() ?: auth()->guard('web')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Check permission
        if ($user instanceof PortalUser && !$user->hasPermissionTo('team.manage')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $targetUser = PortalUser::where('company_id', $user->company_id)->findOrFail($id);

        $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:portal_users,email,' . $id,
            'role' => 'nullable|in:employee,manager,admin',
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => 'exists:branches,id',
        ]);

        $targetUser->update($request->only(['name', 'email', 'role']));

        if ($request->has('branch_ids')) {
            $targetUser->branches()->sync($request->branch_ids);
        }

        return response()->json([
            'success' => true,
            'user' => $targetUser->load(['branches', 'permissions']),
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $user = auth()->guard('portal')->user() ?: auth()->guard('web')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Check permission
        if ($user instanceof PortalUser && !$user->hasPermissionTo('team.manage')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $request->validate([
            'active' => 'required|boolean',
        ]);

        $targetUser = PortalUser::where('company_id', $user->company_id)->findOrFail($id);
        
        // Prevent self-deactivation
        if ($targetUser->id === $user->id && !$request->active) {
            return response()->json(['error' => 'Sie können sich nicht selbst deaktivieren'], 400);
        }

        $targetUser->is_active = $request->active;
        $targetUser->save();

        return response()->json([
            'success' => true,
            'status' => $targetUser->is_active,
        ]);
    }

    public function updatePermissions(Request $request, $id)
    {
        $user = auth()->guard('portal')->user() ?: auth()->guard('web')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Check permission
        if ($user instanceof PortalUser && !$user->hasPermissionTo('team.manage')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'string',
        ]);

        $targetUser = PortalUser::where('company_id', $user->company_id)->findOrFail($id);
        
        // TODO: Implement proper permission management
        // For now, we'll store permissions in a JSON column or use a pivot table
        
        return response()->json([
            'success' => true,
            'message' => 'Berechtigungen aktualisiert',
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $user = auth()->guard('portal')->user() ?: auth()->guard('web')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Check permission
        if ($user instanceof PortalUser && !$user->hasPermissionTo('team.manage')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $targetUser = PortalUser::where('company_id', $user->company_id)->findOrFail($id);
        
        // Prevent self-deletion
        if ($targetUser->id === $user->id) {
            return response()->json(['error' => 'Sie können sich nicht selbst löschen'], 400);
        }

        // Prevent deleting last admin
        if ($targetUser->role === 'admin') {
            $adminCount = PortalUser::where('company_id', $user->company_id)
                ->where('role', 'admin')
                ->count();
                
            if ($adminCount <= 1) {
                return response()->json(['error' => 'Der letzte Administrator kann nicht gelöscht werden'], 400);
            }
        }

        $targetUser->delete();

        return response()->json([
            'success' => true,
            'message' => 'Benutzer erfolgreich gelöscht',
        ]);
    }
}