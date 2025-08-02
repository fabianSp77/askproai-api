<?php

namespace App\Http\Controllers\Portal\Api;

use App\Traits\UsesMCPServers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingsApiController extends BaseApiController
{
    use UsesMCPServers;

    public function __construct()
    {
        parent::__construct();
        $this->setMCPPreferences([
            'settings' => true,
            'user' => true,
            'company' => true
        ]);
    }
    public function getProfile(Request $request)
    {
        $user = $this->getCurrentUser();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Get profile via MCP
        $result = $this->executeMCPTask('getUserProfile', [
            'user_id' => $user->id
        ]);

        if (!($result['result']['success'] ?? false)) {
            return response()->json(['error' => 'Failed to fetch profile'], 500);
        }

        return response()->json([
            'user' => $result['result']['data']
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $this->getCurrentUser();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'nullable|string|max:20',
        ]);

        // Update profile via MCP
        $result = $this->executeMCPTask('updateUserProfile', [
            'user_id' => $user->id,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone
        ]);

        if (!($result['result']['success'] ?? false)) {
            return response()->json([
                'errors' => ['email' => [$result['result']['error'] ?? 'Failed to update profile']]
            ], 422);
        }

        return response()->json([
            'success' => true,
            'user' => $result['result']['data'],
        ]);
    }

    public function updatePassword(Request $request)
    {
        $user = auth()->guard('portal')->user() ?: auth()->guard('web')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        // Change password via MCP
        $result = $this->executeMCPTask('changePassword', [
            'user_id' => $user->id,
            'current_password' => $request->current_password,
            'new_password' => $request->new_password
        ]);

        if (!($result['result']['success'] ?? false)) {
            return response()->json([
                'errors' => ['current_password' => [$result['result']['error'] ?? 'Das aktuelle Passwort ist falsch']],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Passwort erfolgreich geändert',
        ]);
    }

    public function getCompanySettings(Request $request)
    {
        $user = auth()->guard('portal')->user() ?: auth()->guard('web')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if (!$user->company_id) {
            return response()->json([
                'error' => 'No company associated with this user',
                'company' => null
            ], 404);
        }
        
        // Get company settings via MCP
        $companyResult = $this->executeMCPTask('getCompanySettings', [
            'company_id' => $user->company_id
        ]);

        if (!($companyResult['result']['success'] ?? false)) {
            return response()->json(['error' => 'Failed to fetch company settings'], 500);
        }

        // Get user preferences via MCP
        $prefsResult = $this->executeMCPTask('getNotificationPreferences', [
            'user_id' => $user->id
        ]);

        return response()->json([
            'company' => $companyResult['result']['data'],
            'preferences' => $prefsResult['result']['data'] ?? []
        ]);
    }

    public function updateCompany(Request $request)
    {
        $user = auth()->guard('portal')->user() ?: auth()->guard('web')->user();
        
        if (!$user || !$user->company_id) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'company_name' => 'required|string|max:255',
            'company_email' => 'nullable|email',
            'company_phone' => 'nullable|string|max:20',
            'company_address' => 'nullable|string',
            'timezone' => 'nullable|string',
            'language' => 'nullable|string|in:de,en,fr',
            'currency' => 'nullable|string|in:EUR,USD,GBP',
        ]);

        // Update company via MCP
        $result = $this->executeMCPTask('updateCompanySettings', [
            'company_id' => $user->company_id,
            'name' => $request->company_name,
            'email' => $request->company_email,
            'phone' => $request->company_phone,
            'address' => $request->company_address,
            'timezone' => $request->timezone,
            'language' => $request->language,
            'currency' => $request->currency
        ]);

        if (!($result['result']['success'] ?? false)) {
            return response()->json(['error' => $result['result']['error'] ?? 'Failed to update company'], 422);
        }

        return response()->json([
            'success' => true,
            'company' => $result['result']['data'],
        ]);
    }

    public function updateNotifications(Request $request)
    {
        $user = auth()->guard('portal')->user() ?: auth()->guard('web')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'email_notifications' => 'boolean',
            'sms_notifications' => 'boolean',
            'appointment_reminders' => 'boolean',
            'daily_summary' => 'boolean',
            'marketing_emails' => 'boolean',
        ]);

        // Update preferences via MCP
        $result = $this->executeMCPTask('updateNotificationPreferences', array_merge(
            ['user_id' => $user->id],
            $request->only([
                'email_notifications',
                'sms_notifications',
                'appointment_reminders',
                'daily_summary',
                'marketing_emails'
            ])
        ));

        if (!($result['result']['success'] ?? false)) {
            return response()->json(['error' => $result['result']['error'] ?? 'Failed to update preferences'], 422);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Benachrichtigungseinstellungen aktualisiert',
        ]);
    }

    public function enable2FA(Request $request)
    {
        $user = auth()->guard('portal')->user() ?: auth()->guard('web')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Enable 2FA via MCP
        $result = $this->executeMCPTask('enable2FA', [
            'user_id' => $user->id
        ]);

        if (!($result['result']['success'] ?? false)) {
            return response()->json(['error' => $result['result']['error'] ?? '2FA ist bereits aktiviert'], 400);
        }

        return response()->json([
            'secret' => $result['result']['data']['secret'],
            'qr_code' => $result['result']['data']['qr_code'],
        ]);
    }

    public function confirm2FA(Request $request)
    {
        $user = auth()->guard('portal')->user() ?: auth()->guard('web')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        // Confirm 2FA via MCP
        $result = $this->executeMCPTask('confirm2FA', [
            'user_id' => $user->id,
            'code' => $request->code
        ]);

        if (!($result['result']['success'] ?? false)) {
            return response()->json(['error' => $result['result']['error'] ?? 'Ungültiger Code'], 422);
        }

        return response()->json([
            'success' => true,
            'message' => '2FA erfolgreich aktiviert',
            'recovery_codes' => $result['result']['data']['recovery_codes'] ?? []
        ]);
    }

    public function disable2FA(Request $request)
    {
        $user = auth()->guard('portal')->user() ?: auth()->guard('web')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Disable 2FA via MCP
        $result = $this->executeMCPTask('disable2FA', [
            'user_id' => $user->id
        ]);

        if (!($result['result']['success'] ?? false)) {
            return response()->json(['error' => $result['result']['error'] ?? 'Failed to disable 2FA'], 400);
        }

        return response()->json([
            'success' => true,
            'message' => '2FA erfolgreich deaktiviert',
        ]);
    }

    public function getCallNotificationSettings(Request $request)
    {
        $user = auth()->guard('portal')->user() ?: auth()->guard('web')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if (!$user->company_id) {
            return response()->json([
                'error' => 'No company associated with this user',
                'settings' => [],
                'user_preferences' => []
            ], 404);
        }
        
        // Get call notification settings via MCP
        $result = $this->executeMCPTask('getCallNotificationSettings', [
            'company_id' => $user->company_id,
            'user_id' => $user->id
        ]);

        if (!($result['result']['success'] ?? false)) {
            return response()->json(['error' => 'Failed to fetch notification settings'], 500);
        }

        return response()->json([
            'settings' => $result['result']['data']['company_settings'] ?? [],
            'user_preferences' => $result['result']['data']['user_preferences'] ?? ['receive_summaries' => false]
        ]);
    }

    public function updateCallNotificationSettings(Request $request)
    {
        $user = auth()->guard('portal')->user() ?: auth()->guard('web')->user();
        
        if (!$user || !$user->company_id) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Check permission - only admin/owner can update company settings
        if ($user instanceof \App\Models\PortalUser && !$user->hasPermission('settings.manage')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $request->validate([
            'send_call_summaries' => 'nullable|boolean',
            'call_summary_recipients' => 'nullable|array',
            'call_summary_recipients.*' => 'email',
            'include_transcript_in_summary' => 'nullable|boolean',
            'include_csv_export' => 'nullable|boolean',
            'summary_email_frequency' => 'nullable|in:immediate,hourly,daily',
        ]);

        // Update call notification settings via MCP
        $result = $this->executeMCPTask('updateCallNotificationSettings', array_merge(
            ['company_id' => $user->company_id],
            $request->only([
                'send_call_summaries',
                'call_summary_recipients',
                'include_transcript_in_summary',
                'include_csv_export',
                'summary_email_frequency',
            ])
        ));

        if (!($result['result']['success'] ?? false)) {
            return response()->json(['error' => $result['result']['error'] ?? 'Failed to update settings'], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Anruf-Benachrichtigungseinstellungen aktualisiert',
            'settings' => $result['result']['data'] ?? [],
        ]);
    }

    public function updateUserCallPreferences(Request $request)
    {
        $user = auth()->guard('portal')->user() ?: auth()->guard('web')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'receive_summaries' => 'nullable|boolean',
        ]);

        // Update user preferences via MCP
        $result = $this->executeMCPTask('updateNotificationPreferences', [
            'user_id' => $user->id,
            'call_assigned' => $request->receive_summaries
        ]);

        if (!($result['result']['success'] ?? false)) {
            return response()->json(['error' => $result['result']['error'] ?? 'Failed to update preferences'], 422);
        }

        return response()->json([
            'success' => true,
            'preferences' => ['receive_summaries' => $request->receive_summaries],
        ]);
    }
}