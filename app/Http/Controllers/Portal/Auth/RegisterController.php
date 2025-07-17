<?php

namespace App\Http\Controllers\Portal\Auth;

use App\Http\Controllers\Controller;
use App\Models\PortalUser;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use App\Mail\PortalRegistrationNotification;
use App\Mail\PortalRegistrationConfirmation;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        return view('portal.auth.register');
    }

    public function register(Request $request)
    {
        // Rate limiting - 3 Registrierungen pro IP pro Stunde
        $key = 'register:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            return back()->withErrors([
                'email' => "Zu viele Registrierungsversuche. Bitte versuchen Sie es in {$seconds} Sekunden erneut."
            ]);
        }
        RateLimiter::hit($key, 3600); // 1 Stunde

        // Honeypot check
        if ($request->filled('website')) {
            Log::warning('Honeypot triggered', ['ip' => $request->ip()]);
            return back()->with('success', 'Ihre Registrierung wurde erfolgreich eingereicht.');
        }

        // Validation
        $validator = Validator::make($request->all(), [
            'company_name' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:portal_users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string|max:500',
            'terms' => 'required|accepted',
            'website' => 'nullable|string', // Honeypot field
        ], [
            'company_name.required' => 'Bitte geben Sie Ihren Firmennamen ein.',
            'name.required' => 'Bitte geben Sie Ihren Namen ein.',
            'email.required' => 'Bitte geben Sie Ihre E-Mail-Adresse ein.',
            'email.unique' => 'Diese E-Mail-Adresse ist bereits registriert.',
            'password.required' => 'Bitte wählen Sie ein Passwort.',
            'password.min' => 'Das Passwort muss mindestens 8 Zeichen lang sein.',
            'password.confirmed' => 'Die Passwörter stimmen nicht überein.',
            'phone.required' => 'Bitte geben Sie Ihre Telefonnummer ein.',
            'terms.accepted' => 'Sie müssen den Nutzungsbedingungen zustimmen.',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput($request->except('password', 'password_confirmation'));
        }

        // Check if company exists
        $company = Company::where('name', 'like', '%' . $request->company_name . '%')->first();
        
        if (!$company) {
            // Create new company (inactive by default)
            $company = Company::create([
                'name' => $request->company_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'is_active' => false, // Requires admin activation
                'settings' => [
                    'registration_date' => now()->toDateTimeString(),
                    'registration_ip' => $request->ip(),
                ]
            ]);
        }

        // Create portal user (inactive by default)
        $user = PortalUser::create([
            'company_id' => $company->id,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'role' => 'admin', // First user is admin
            'is_active' => false, // Requires admin activation
            'permissions' => json_encode([]),
            'registration_data' => [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'timestamp' => now()->toDateTimeString(),
            ]
        ]);

        // Send notification to admin
        try {
            $adminEmail = config('mail.admin_notification_email', 'admin@askproai.de');
            Mail::to($adminEmail)->send(new PortalRegistrationNotification($user, $company));
        } catch (\Exception $e) {
            Log::error('Failed to send registration notification', [
                'error' => $e->getMessage(),
                'user_id' => $user->id
            ]);
        }

        // Send confirmation to user
        try {
            Mail::to($user->email)->send(new PortalRegistrationConfirmation($user));
        } catch (\Exception $e) {
            Log::error('Failed to send registration confirmation', [
                'error' => $e->getMessage(),
                'user_id' => $user->id
            ]);
        }

        Log::info('New portal registration', [
            'user_id' => $user->id,
            'company_id' => $company->id,
            'email' => $user->email,
            'company_name' => $company->name
        ]);

        return redirect()->route('business.register.success');
    }

    public function showSuccessPage()
    {
        return view('portal.auth.register-success');
    }
}