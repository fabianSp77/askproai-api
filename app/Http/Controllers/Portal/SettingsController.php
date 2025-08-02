<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Traits\UsesMCPServers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class SettingsController extends Controller
{
    use UsesMCPServers;

    public function __construct()
    {
        $this->setMCPPreferences([
            'settings' => true,
            'user' => true,
            'company' => true
        ]);
    }

    /**
     * Show settings overview
     */
    public function index()
    {
        // Redirect to React settings page
        return app(\App\Http\Controllers\Portal\ReactDashboardController::class)->index();
    }
    
    /**
     * Show profile settings
     */
    public function profile()
    {
        // Redirect to React settings page
        return app(\App\Http\Controllers\Portal\ReactDashboardController::class)->index();
    }
    
    /**
     * Update profile
     */
    public function updateProfile(Request $request)
    {
        $userId = $this->getCurrentUserId();
        
        if (!$userId) {
            abort(401, 'Unauthorized');
        }
        
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);
        
        // Update via MCP
        $result = $this->executeMCPTask('updateUserProfile', [
            'user_id' => $userId,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null
        ]);
        
        if (!($result['result']['success'] ?? false)) {
            return back()->withErrors(['error' => $result['result']['error'] ?? 'Failed to update profile']);
        }
        
        return back()->with('success', 'Profil wurde aktualisiert.');
    }
    
    /**
     * Show password change form
     */
    public function password()
    {
        // Redirect to React settings page
        return app(\App\Http\Controllers\Portal\ReactDashboardController::class)->index();
    }
    
    /**
     * Update password
     */
    public function updatePassword(Request $request)
    {
        $userId = $this->getCurrentUserId();
        
        if (!$userId) {
            abort(401, 'Unauthorized');
        }
        
        $validated = $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);
        
        // Change password via MCP
        $result = $this->executeMCPTask('changePassword', [
            'user_id' => $userId,
            'current_password' => $validated['current_password'],
            'new_password' => $validated['password']
        ]);
        
        if (!($result['result']['success'] ?? false)) {
            return back()->withErrors(['current_password' => $result['result']['error'] ?? 'Failed to change password']);
        }
        
        return redirect()->route('business.settings.index')
            ->with('success', 'Passwort wurde geändert.');
    }
    
    /**
     * Show notification preferences
     */
    public function notifications()
    {
        // Redirect to React settings page
        return app(\App\Http\Controllers\Portal\ReactDashboardController::class)->index();
    }
    
    /**
     * Update notification preferences
     */
    public function updateNotifications(Request $request)
    {
        $userId = $this->getCurrentUserId();
        
        if (!$userId) {
            abort(401, 'Unauthorized');
        }
        
        $validated = $request->validate([
            'email_notifications' => ['boolean'],
            'call_assigned' => ['boolean'],
            'daily_summary' => ['boolean'],
            'callback_reminder' => ['boolean'],
            'low_balance_alert' => ['boolean'],
        ]);
        
        // Update preferences via MCP
        $result = $this->executeMCPTask('updateNotificationPreferences', array_merge(
            ['user_id' => $userId],
            $validated
        ));
        
        if (!($result['result']['success'] ?? false)) {
            return back()->withErrors(['error' => $result['result']['error'] ?? 'Failed to update preferences']);
        }
        
        return back()->with('success', 'Benachrichtigungseinstellungen wurden aktualisiert.');
    }
    
    /**
     * Get current user ID.
     */
    protected function getCurrentUserId(): ?int
    {
        $user = Auth::guard('portal')->user();
        return $user ? $user->id : null;
    }
}