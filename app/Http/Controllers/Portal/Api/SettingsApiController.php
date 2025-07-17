<?php

namespace App\Http\Controllers\Portal\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use PragmaRX\Google2FA\Google2FA;

class SettingsApiController extends BaseApiController
{
    public function getProfile(Request $request)
    {
        $user = $this->getCurrentUser();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? null,
                'role' => $user->role ?? 'user',
                'avatar_url' => $user->avatar_url ?? null,
                'two_factor_enabled' => $user->two_factor_confirmed_at !== null,
                'created_at' => $user->created_at,
                'last_login_at' => $user->last_login_at ?? null,
            ],
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
            'email' => 'required|email|unique:portal_users,email,' . $user->id . '|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
        ]);

        $user->update($request->only(['name', 'email', 'phone']));

        return response()->json([
            'success' => true,
            'user' => $user,
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

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'errors' => ['current_password' => ['Das aktuelle Passwort ist falsch']],
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

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

        $company = $user->company;
        
        // Check if company relationship exists
        if (!$company) {
            return response()->json([
                'error' => 'No company associated with this user',
                'company' => null,
                'preferences' => [
                    'email_notifications' => $user->email_notifications ?? true,
                    'sms_notifications' => $user->sms_notifications ?? false,
                    'appointment_reminders' => $user->appointment_reminders ?? true,
                    'daily_summary' => $user->daily_summary ?? true,
                    'marketing_emails' => $user->marketing_emails ?? false,
                ],
            ], 404);
        }
        
        return response()->json([
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'email' => $company->email ?? null,
                'phone' => $company->phone ?? null,
                'address' => $company->address ?? null,
                'timezone' => $company->timezone ?? 'Europe/Berlin',
                'language' => $company->language ?? 'de',
                'currency' => $company->currency ?? 'EUR',
                'subscription' => [
                    'plan_name' => $company->subscription_plan ?? 'Basic',
                    'next_billing_date' => $company->next_billing_date ?? null,
                    'used_minutes' => $company->used_minutes ?? 0,
                    'included_minutes' => $company->included_minutes ?? 1000,
                ],
            ],
            'preferences' => [
                'email_notifications' => $user->email_notifications ?? true,
                'sms_notifications' => $user->sms_notifications ?? false,
                'appointment_reminders' => $user->appointment_reminders ?? true,
                'daily_summary' => $user->daily_summary ?? true,
                'marketing_emails' => $user->marketing_emails ?? false,
            ],
        ]);
    }

    public function updateCompany(Request $request)
    {
        $user = auth()->guard('portal')->user() ?: auth()->guard('web')->user();
        
        if (!$user) {
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

        $company = $user->company;
        
        if (!$company) {
            return response()->json(['error' => 'No company associated with this user'], 404);
        }
        
        $company->update([
            'name' => $request->company_name,
            'email' => $request->company_email,
            'phone' => $request->company_phone,
            'address' => $request->company_address,
            'timezone' => $request->timezone,
            'language' => $request->language,
            'currency' => $request->currency,
        ]);

        return response()->json([
            'success' => true,
            'company' => $company,
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

        // Store in user preferences or separate preferences table
        // For now, we'll simulate the update
        
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

        if ($user->two_factor_confirmed_at) {
            return response()->json(['error' => '2FA ist bereits aktiviert'], 400);
        }

        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();
        
        // Store secret temporarily
        $user->two_factor_secret = encrypt($secret);
        $user->save();

        // Generate QR code
        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        $qrCode = QrCode::size(200)->generate($qrCodeUrl);

        return response()->json([
            'secret' => $secret,
            'qr_code' => $qrCode,
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

        $google2fa = new Google2FA();
        $secret = decrypt($user->two_factor_secret);
        
        if (!$google2fa->verifyKey($secret, $request->code)) {
            return response()->json(['error' => 'Ungültiger Code'], 422);
        }

        $user->two_factor_confirmed_at = now();
        $user->save();

        return response()->json([
            'success' => true,
            'message' => '2FA erfolgreich aktiviert',
        ]);
    }

    public function disable2FA(Request $request)
    {
        $user = auth()->guard('portal')->user() ?: auth()->guard('web')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user->two_factor_secret = null;
        $user->two_factor_confirmed_at = null;
        $user->save();

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

        $company = $user->company;
        
        if (!$company) {
            return response()->json([
                'error' => 'No company associated with this user',
                'settings' => [
                    'send_call_summaries' => false,
                    'call_summary_recipients' => [],
                    'include_transcript_in_summary' => true,
                    'include_csv_export' => false,
                    'summary_email_frequency' => 'immediate',
                ],
                'user_preferences' => $user->call_notification_preferences ?? [
                    'receive_summaries' => false,
                ],
            ], 404);
        }
        
        return response()->json([
            'settings' => [
                'send_call_summaries' => $company->send_call_summaries ?? false,
                'call_summary_recipients' => $company->call_summary_recipients ?? [],
                'include_transcript_in_summary' => $company->include_transcript_in_summary ?? true,
                'include_csv_export' => $company->include_csv_export ?? false,
                'summary_email_frequency' => $company->summary_email_frequency ?? 'immediate',
            ],
            'user_preferences' => $user->call_notification_preferences ?? [
                'receive_summaries' => false,
            ],
        ]);
    }

    public function updateCallNotificationSettings(Request $request)
    {
        $user = auth()->guard('portal')->user() ?: auth()->guard('web')->user();
        
        if (!$user) {
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

        $company = $user->company;
        
        if (!$company) {
            return response()->json(['error' => 'No company associated with this user'], 404);
        }
        
        $company->update($request->only([
            'send_call_summaries',
            'call_summary_recipients',
            'include_transcript_in_summary',
            'include_csv_export',
            'summary_email_frequency',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Anruf-Benachrichtigungseinstellungen aktualisiert',
            'settings' => [
                'send_call_summaries' => $company->send_call_summaries,
                'call_summary_recipients' => $company->call_summary_recipients,
                'include_transcript_in_summary' => $company->include_transcript_in_summary,
                'include_csv_export' => $company->include_csv_export,
                'summary_email_frequency' => $company->summary_email_frequency,
            ],
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

        $preferences = $user->call_notification_preferences ?? [];
        $preferences['receive_summaries'] = $request->receive_summaries ?? false;
        
        $user->call_notification_preferences = $preferences;
        $user->save();

        return response()->json([
            'success' => true,
            'preferences' => $preferences,
        ]);
    }
}