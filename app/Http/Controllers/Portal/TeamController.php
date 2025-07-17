<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\PortalUser;
use App\Models\PhoneNumber;
use App\Notifications\TeamInviteNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class TeamController extends Controller
{
    /**
     * Display team members
     */
    public function index(Request $request)
    {
        $user = Auth::guard('portal')->user();
        
        // If admin viewing, get company from session
        if (session('is_admin_viewing')) {
            $companyId = session('admin_impersonation.company_id');
        } else {
            // Check permission only for regular users
            if (!$user || !$user->hasPermission('team.view')) {
                abort(403);
            }
            $companyId = $user->company_id;
        }
        
        $query = PortalUser::where('company_id', $companyId);
        
        // Filter by role
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }
        
        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }
        
        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        
        // Get company phone numbers for filtering
        $companyPhoneNumbers = PhoneNumber::where('company_id', $companyId)
            ->where('is_active', true)
            ->pluck('number')
            ->toArray();
        
        // Get statistics for each user
        $teamMembers = $query->paginate(20)->through(function ($member) use ($companyId, $companyPhoneNumbers) {
            // Get call statistics for last 7 days
            $stats = DB::table('calls')
                ->join('call_portal_data', 'calls.id', '=', 'call_portal_data.call_id')
                ->where('calls.company_id', $companyId)
                ->whereIn('calls.to_number', $companyPhoneNumbers)
                ->where('call_portal_data.assigned_to', $member->id)
                ->whereDate('calls.created_at', '>=', now()->subDays(7))
                ->selectRaw('
                    COUNT(*) as total_calls,
                    SUM(CASE WHEN call_portal_data.status = "completed" THEN 1 ELSE 0 END) as completed_calls,
                    SUM(CASE WHEN call_portal_data.status IN ("new", "in_progress", "callback_scheduled", "requires_action") THEN 1 ELSE 0 END) as open_calls
                ')
                ->first();
            
            $member->stats = $stats;
            $member->completion_rate = $stats->total_calls > 0 
                ? round(($stats->completed_calls / $stats->total_calls) * 100) 
                : 0;
            
            return $member;
        });
        
        // Load React SPA directly
        return app(\App\Http\Controllers\Portal\ReactDashboardController::class)->index();
    }
    
    /**
     * Show invite form
     */
    public function showInviteForm()
    {
        $user = Auth::guard('portal')->user();
        
        // Skip permission check for admin viewing
        if (!session('is_admin_viewing')) {
            if (!$user || !$user->hasPermission('team.manage')) {
                abort(403);
            }
        }
        
        // Load React SPA directly
        return app(\App\Http\Controllers\Portal\ReactDashboardController::class)->index();
    }
    
    /**
     * Send team invite
     */
    public function sendInvite(Request $request)
    {
        $user = Auth::guard('portal')->user();
        
        // Skip permission check for admin viewing
        if (!session('is_admin_viewing')) {
            if (!$user || !$user->hasPermission('team.manage')) {
                abort(403);
            }
        }
        
        // Get company ID for validation and creation
        if (session('is_admin_viewing')) {
            $companyId = session('admin_impersonation.company_id');
        } else {
            $companyId = $user->company_id;
        }
        
        $validated = $request->validate([
            'email' => [
                'required',
                'email',
                Rule::unique('portal_users'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', Rule::in(array_keys($this->getAvailableRoles($user)))],
            'phone' => ['nullable', 'string', 'max:20'],
            'send_welcome_email' => ['boolean'],
        ]);
        
        DB::transaction(function () use ($validated, $user, $companyId) {
            // Generate temporary password
            $temporaryPassword = Str::random(12);
            
            // Create user
            $newUser = PortalUser::create([
                'company_id' => $companyId,
                'email' => $validated['email'],
                'name' => $validated['name'],
                'password' => Hash::make($temporaryPassword),
                'role' => $validated['role'],
                'phone' => $validated['phone'] ?? null,
                'is_active' => true,
                'notification_preferences' => [
                    'email' => true,
                    'call_assigned' => true,
                    'daily_summary' => true,
                    'callback_reminder' => true,
                ],
            ]);
            
            // Send welcome email if requested
            if ($validated['send_welcome_email'] ?? true) {
                $newUser->notify(new TeamInviteNotification($temporaryPassword, $user));
            }
        });
        
        return redirect()->route('business.team.index')
            ->with('success', 'Teammitglied wurde eingeladen. Eine E-Mail mit den Zugangsdaten wurde versendet.');
    }
    
    /**
     * Update team member
     */
    public function updateUser(Request $request, PortalUser $user)
    {
        $currentUser = Auth::guard('portal')->user();
        
        // Skip permission check for admin viewing
        if (!session('is_admin_viewing')) {
            if (!$currentUser || !$currentUser->hasPermission('team.manage')) {
                abort(403);
            }
        }
        
        // Ensure user is from same company
        if (session('is_admin_viewing')) {
            $companyId = session('admin_impersonation.company_id');
        } else {
            $companyId = $currentUser->company_id;
        }
        
        if ($user->company_id !== $companyId) {
            abort(404);
        }
        
        // Cannot edit yourself (skip for admin viewing)
        if (!session('is_admin_viewing') && $currentUser && $user->id === $currentUser->id) {
            return back()->with('error', 'Sie können Ihre eigenen Berechtigungen nicht ändern.');
        }
        
        // Cannot edit owner if not owner (skip for admin viewing)
        if (!session('is_admin_viewing') && $user->role === PortalUser::ROLE_OWNER && (!$currentUser || $currentUser->role !== PortalUser::ROLE_OWNER)) {
            return back()->with('error', 'Nur Inhaber können andere Inhaber bearbeiten.');
        }
        
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                Rule::unique('portal_users')->ignore($user->id),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', Rule::in(array_keys($this->getAvailableRoles($currentUser)))],
            'is_active' => ['boolean'],
        ]);
        
        // Cannot change role to owner unless current user is owner (skip for admin viewing)
        if (!session('is_admin_viewing') && $validated['role'] === PortalUser::ROLE_OWNER && (!$currentUser || $currentUser->role !== PortalUser::ROLE_OWNER)) {
            return back()->with('error', 'Nur Inhaber können neue Inhaber ernennen.');
        }
        
        $user->update($validated);
        
        return back()->with('success', 'Teammitglied wurde aktualisiert.');
    }
    
    /**
     * Deactivate team member
     */
    public function deactivateUser(Request $request, PortalUser $user)
    {
        $currentUser = Auth::guard('portal')->user();
        
        // Skip permission check for admin viewing
        if (!session('is_admin_viewing')) {
            if (!$currentUser || !$currentUser->hasPermission('team.manage')) {
                abort(403);
            }
        }
        
        // Ensure user is from same company
        if (session('is_admin_viewing')) {
            $companyId = session('admin_impersonation.company_id');
        } else {
            $companyId = $currentUser->company_id;
        }
        
        if ($user->company_id !== $companyId) {
            abort(404);
        }
        
        // Cannot deactivate yourself (skip for admin viewing)
        if (!session('is_admin_viewing') && $currentUser && $user->id === $currentUser->id) {
            return back()->with('error', 'Sie können sich nicht selbst deaktivieren.');
        }
        
        // Cannot deactivate owner
        if ($user->role === PortalUser::ROLE_OWNER) {
            return back()->with('error', 'Inhaber können nicht deaktiviert werden.');
        }
        
        $user->update(['is_active' => false]);
        
        // Unassign all open calls
        DB::table('call_portal_data')
            ->where('assigned_to', $user->id)
            ->whereNotIn('status', ['completed', 'abandoned'])
            ->update([
                'assigned_to' => null,
                'status' => 'new',
            ]);
        
        return back()->with('success', 'Teammitglied wurde deaktiviert und alle offenen Anrufe wurden freigegeben.');
    }
    
    /**
     * Reactivate team member
     */
    public function reactivateUser(Request $request, PortalUser $user)
    {
        $currentUser = Auth::guard('portal')->user();
        
        // Skip permission check for admin viewing
        if (!session('is_admin_viewing')) {
            if (!$currentUser || !$currentUser->hasPermission('team.manage')) {
                abort(403);
            }
        }
        
        // Ensure user is from same company
        if (session('is_admin_viewing')) {
            $companyId = session('admin_impersonation.company_id');
        } else {
            $companyId = $currentUser->company_id;
        }
        
        if ($user->company_id !== $companyId) {
            abort(404);
        }
        
        $user->update(['is_active' => true]);
        
        return back()->with('success', 'Teammitglied wurde reaktiviert.');
    }
    
    /**
     * Reset user password
     */
    public function resetPassword(Request $request, PortalUser $user)
    {
        $currentUser = Auth::guard('portal')->user();
        
        // Skip permission check for admin viewing
        if (!session('is_admin_viewing')) {
            if (!$currentUser || !$currentUser->hasPermission('team.manage')) {
                abort(403);
            }
        }
        
        // Ensure user is from same company
        if (session('is_admin_viewing')) {
            $companyId = session('admin_impersonation.company_id');
        } else {
            $companyId = $currentUser->company_id;
        }
        
        if ($user->company_id !== $companyId) {
            abort(404);
        }
        
        // Generate new password
        $newPassword = Str::random(12);
        $user->update(['password' => Hash::make($newPassword)]);
        
        // Send notification with new password
        $user->notify(new \App\Notifications\PasswordResetNotification($newPassword));
        
        return back()->with('success', 'Passwort wurde zurückgesetzt. Eine E-Mail mit dem neuen Passwort wurde versendet.');
    }
    
    /**
     * Get available roles based on current user's role
     */
    private function getAvailableRoles(?PortalUser $user): array
    {
        // Admin viewing gets all roles
        if (session('is_admin_viewing') || !$user) {
            return PortalUser::ROLES;
        }
        
        if ($user->role === PortalUser::ROLE_OWNER) {
            return PortalUser::ROLES;
        }
        
        if ($user->role === PortalUser::ROLE_ADMIN) {
            return [
                PortalUser::ROLE_ADMIN => PortalUser::ROLES[PortalUser::ROLE_ADMIN],
                PortalUser::ROLE_MANAGER => PortalUser::ROLES[PortalUser::ROLE_MANAGER],
                PortalUser::ROLE_STAFF => PortalUser::ROLES[PortalUser::ROLE_STAFF],
            ];
        }
        
        if ($user->role === PortalUser::ROLE_MANAGER) {
            return [
                PortalUser::ROLE_STAFF => PortalUser::ROLES[PortalUser::ROLE_STAFF],
            ];
        }
        
        return [];
    }
}