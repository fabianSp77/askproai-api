<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Traits\UsesMCPServers;
use App\Models\PortalUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TeamController extends Controller
{
    use UsesMCPServers;

    public function __construct()
    {
        $this->setMCPPreferences([
            'team' => true,
            'company' => true,
            'database' => true
        ]);
    }

    /**
     * Display team members
     */
    public function index(Request $request)
    {
        $companyId = $this->getCompanyId();
        $user = Auth::guard('portal')->user();
        
        // Check permissions
        if (!$this->canViewTeam($user)) {
            abort(403);
        }
        
        // Get team members via MCP
        $result = $this->executeMCPTask('listTeamMembers', [
            'company_id' => $companyId,
            'filters' => [
                'role' => $request->input('role'),
                'status' => $request->input('status'),
                'search' => $request->input('search')
            ],
            'page' => $request->get('page', 1),
            'per_page' => 20,
            'include_stats' => true
        ]);

        // Store data for React
        session()->flash('team_data', $result['result'] ?? []);
        
        // Load React SPA
        return app(\App\Http\Controllers\Portal\ReactDashboardController::class)->index();
    }
    
    /**
     * Show invite form
     */
    public function showInviteForm()
    {
        $user = Auth::guard('portal')->user();
        
        // Check permissions
        if (!$this->canManageTeam($user)) {
            abort(403);
        }
        
        // Load React SPA
        return app(\App\Http\Controllers\Portal\ReactDashboardController::class)->index();
    }
    
    /**
     * Send team invite
     */
    public function sendInvite(Request $request)
    {
        $user = Auth::guard('portal')->user();
        
        // Check permissions
        if (!$this->canManageTeam($user)) {
            abort(403);
        }
        
        // Get available roles for validation
        $availableRoles = $this->getAvailableRoles($user);
        
        $validated = $request->validate([
            'email' => [
                'required',
                'email',
                Rule::unique('portal_users'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', Rule::in(array_keys($availableRoles))],
            'phone' => ['nullable', 'string', 'max:20'],
            'send_welcome_email' => ['boolean'],
        ]);
        
        // Invite via MCP
        $result = $this->executeMCPTask('inviteTeamMember', [
            'company_id' => $this->getCompanyId(),
            'user_data' => $validated,
            'invited_by_id' => $user ? $user->id : null
        ]);

        if (!($result['result']['success'] ?? false)) {
            return back()->with('error', $result['result']['message'] ?? 'Fehler beim Einladen des Teammitglieds.');
        }

        return redirect()->route('business.team.index')
            ->with('success', 'Teammitglied wurde eingeladen. Eine E-Mail mit den Zugangsdaten wurde versendet.');
    }
    
    /**
     * Update team member
     */
    public function updateUser(Request $request, PortalUser $targetUser)
    {
        $user = Auth::guard('portal')->user();
        
        // Check permissions
        if (!$this->canManageTeam($user)) {
            abort(403);
        }
        
        // Ensure user is from same company
        if (!$this->isFromSameCompany($targetUser)) {
            abort(404);
        }
        
        // Cannot edit yourself (skip for admin viewing)
        if (!session('is_admin_viewing') && $user && $targetUser->id === $user->id) {
            return back()->with('error', 'Sie können Ihre eigenen Berechtigungen nicht ändern.');
        }
        
        $availableRoles = $this->getAvailableRoles($user);
        
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                Rule::unique('portal_users')->ignore($targetUser->id),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', Rule::in(array_keys($availableRoles))],
            'is_active' => ['boolean'],
        ]);
        
        // Update via MCP
        $result = $this->executeMCPTask('updateTeamMember', [
            'company_id' => $this->getCompanyId(),
            'user_id' => $targetUser->id,
            'user_data' => $validated,
            'updated_by_id' => $user ? $user->id : null
        ]);

        if (!($result['result']['success'] ?? false)) {
            return back()->with('error', $result['result']['message'] ?? 'Fehler beim Aktualisieren.');
        }
        
        return back()->with('success', 'Teammitglied wurde aktualisiert.');
    }
    
    /**
     * Deactivate team member
     */
    public function deactivateUser(Request $request, PortalUser $targetUser)
    {
        $user = Auth::guard('portal')->user();
        
        // Check permissions
        if (!$this->canManageTeam($user)) {
            abort(403);
        }
        
        // Ensure user is from same company
        if (!$this->isFromSameCompany($targetUser)) {
            abort(404);
        }
        
        // Cannot deactivate yourself (skip for admin viewing)
        if (!session('is_admin_viewing') && $user && $targetUser->id === $user->id) {
            return back()->with('error', 'Sie können sich nicht selbst deaktivieren.');
        }
        
        // Deactivate via MCP
        $result = $this->executeMCPTask('updateMemberStatus', [
            'company_id' => $this->getCompanyId(),
            'user_id' => $targetUser->id,
            'is_active' => false,
            'reassign_calls' => true
        ]);

        if (!($result['result']['success'] ?? false)) {
            return back()->with('error', $result['result']['message'] ?? 'Fehler beim Deaktivieren.');
        }
        
        return back()->with('success', 'Teammitglied wurde deaktiviert und alle offenen Anrufe wurden freigegeben.');
    }
    
    /**
     * Reactivate team member
     */
    public function reactivateUser(Request $request, PortalUser $targetUser)
    {
        $user = Auth::guard('portal')->user();
        
        // Check permissions
        if (!$this->canManageTeam($user)) {
            abort(403);
        }
        
        // Ensure user is from same company
        if (!$this->isFromSameCompany($targetUser)) {
            abort(404);
        }
        
        // Reactivate via MCP
        $result = $this->executeMCPTask('updateMemberStatus', [
            'company_id' => $this->getCompanyId(),
            'user_id' => $targetUser->id,
            'is_active' => true
        ]);

        if (!($result['result']['success'] ?? false)) {
            return back()->with('error', $result['result']['message'] ?? 'Fehler beim Reaktivieren.');
        }
        
        return back()->with('success', 'Teammitglied wurde reaktiviert.');
    }
    
    /**
     * Reset user password
     */
    public function resetPassword(Request $request, PortalUser $targetUser)
    {
        $user = Auth::guard('portal')->user();
        
        // Check permissions
        if (!$this->canManageTeam($user)) {
            abort(403);
        }
        
        // Ensure user is from same company
        if (!$this->isFromSameCompany($targetUser)) {
            abort(404);
        }
        
        // Reset password via MCP
        $result = $this->executeMCPTask('resetTeamMemberPassword', [
            'company_id' => $this->getCompanyId(),
            'user_id' => $targetUser->id,
            'send_notification' => true
        ]);

        if (!($result['result']['success'] ?? false)) {
            return back()->with('error', $result['result']['message'] ?? 'Fehler beim Zurücksetzen.');
        }
        
        return back()->with('success', 'Passwort wurde zurückgesetzt. Eine E-Mail mit dem neuen Passwort wurde versendet.');
    }
    
    /**
     * Get company ID for current context
     */
    protected function getCompanyId(): ?int
    {
        if (session('is_admin_viewing')) {
            return session('admin_impersonation.company_id');
        }
        
        $user = Auth::guard('portal')->user();
        return $user ? $user->company_id : null;
    }
    
    /**
     * Check if user can view team
     */
    protected function canViewTeam($user): bool
    {
        if (session('is_admin_viewing')) {
            return true;
        }
        
        return $user && $user->hasPermission('team.view');
    }
    
    /**
     * Check if user can manage team
     */
    protected function canManageTeam($user): bool
    {
        if (session('is_admin_viewing')) {
            return true;
        }
        
        return $user && $user->hasPermission('team.manage');
    }
    
    /**
     * Check if target user is from same company
     */
    protected function isFromSameCompany(PortalUser $targetUser): bool
    {
        $companyId = $this->getCompanyId();
        return $targetUser->company_id === $companyId;
    }
    
    /**
     * Get available roles based on current user's role
     */
    protected function getAvailableRoles(?PortalUser $user): array
    {
        if (!$user || session('is_admin_viewing')) {
            // Get all roles via MCP
            $result = $this->executeMCPTask('getAvailableRoles', [
                'for_admin' => true
            ]);
            
            return $result['result']['roles'] ?? PortalUser::ROLES;
        }
        
        // Get roles based on user permissions
        $result = $this->executeMCPTask('getAvailableRoles', [
            'current_role' => $user->role
        ]);
        
        return $result['result']['roles'] ?? [];
    }
}