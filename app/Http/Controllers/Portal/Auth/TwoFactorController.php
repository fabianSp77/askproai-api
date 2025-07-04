<?php

namespace App\Http\Controllers\Portal\Auth;

use App\Http\Controllers\Controller;
use App\Models\PortalUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

class TwoFactorController extends Controller
{
    /**
     * Show 2FA setup page
     */
    public function showSetupForm()
    {
        $userId = session('portal_2fa_user');
        if (!$userId) {
            return redirect()->route('business.login');
        }
        
        $user = PortalUser::find($userId);
        if (!$user) {
            return redirect()->route('business.login');
        }
        
        // Enable 2FA temporarily to generate QR code
        if (!$user->two_factor_secret) {
            app(EnableTwoFactorAuthentication::class)($user);
            $user->refresh();
        }
        
        return view('portal.auth.two-factor-setup', [
            'user' => $user,
            'qrCode' => $user->twoFactorQrCodeSvg(),
        ]);
    }
    
    /**
     * Confirm 2FA setup
     */
    public function confirmSetup(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);
        
        $userId = session('portal_2fa_user');
        if (!$userId) {
            return redirect()->route('business.login');
        }
        
        $user = PortalUser::find($userId);
        if (!$user) {
            return redirect()->route('business.login');
        }
        
        // Verify code
        $provider = app(TwoFactorAuthenticationProvider::class);
        
        if (!$provider->verify($user->two_factor_secret, $request->code)) {
            return back()->withErrors(['code' => 'Der eingegebene Code ist ungültig.']);
        }
        
        // Confirm 2FA
        $user->two_factor_confirmed_at = now();
        $user->save();
        
        // Clear session
        session()->forget('portal_2fa_user');
        
        // Login user
        Auth::guard('portal')->login($user);
        $user->recordLogin($request->ip());
        
        return redirect()->route('business.dashboard')
            ->with('success', 'Zwei-Faktor-Authentifizierung wurde erfolgreich aktiviert.');
    }
    
    /**
     * Show 2FA challenge page
     */
    public function showChallengeForm()
    {
        $userId = session('portal_2fa_user');
        if (!$userId) {
            return redirect()->route('business.login');
        }
        
        return view('portal.auth.two-factor-challenge');
    }
    
    /**
     * Verify 2FA challenge
     */
    public function verifyChallenge(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);
        
        $userId = session('portal_2fa_user');
        if (!$userId) {
            return redirect()->route('business.login');
        }
        
        $user = PortalUser::find($userId);
        if (!$user) {
            return redirect()->route('business.login');
        }
        
        // Check if using recovery code
        if (strlen($request->code) > 6) {
            return $this->verifyRecoveryCode($request, $user);
        }
        
        // Verify TOTP code
        $provider = app(TwoFactorAuthenticationProvider::class);
        
        if (!$provider->verify($user->two_factor_secret, $request->code)) {
            return back()->withErrors(['code' => 'Der eingegebene Code ist ungültig.']);
        }
        
        // Clear session
        session()->forget('portal_2fa_user');
        
        // Login user
        Auth::guard('portal')->login($user, session('portal_remember', false));
        $user->recordLogin($request->ip());
        
        return redirect()->intended(route('business.dashboard'));
    }
    
    /**
     * Verify recovery code
     */
    protected function verifyRecoveryCode(Request $request, PortalUser $user)
    {
        $codes = json_decode(decrypt($user->two_factor_recovery_codes), true);
        
        if (!in_array($request->code, $codes)) {
            return back()->withErrors(['code' => 'Der eingegebene Recovery-Code ist ungültig.']);
        }
        
        // Remove used code
        $codes = array_values(array_diff($codes, [$request->code]));
        $user->two_factor_recovery_codes = encrypt(json_encode($codes));
        $user->save();
        
        // Clear session
        session()->forget('portal_2fa_user');
        
        // Login user
        Auth::guard('portal')->login($user, session('portal_remember', false));
        $user->recordLogin($request->ip());
        
        return redirect()->intended(route('business.dashboard'))
            ->with('warning', 'Sie haben einen Recovery-Code verwendet. Bitte generieren Sie neue Recovery-Codes.');
    }
    
    /**
     * Disable 2FA
     */
    public function disable(Request $request)
    {
        $user = Auth::guard('portal')->user();
        
        // Check if 2FA is required by role
        if ($user->requires2FA()) {
            return back()->with('error', 'Zwei-Faktor-Authentifizierung ist für Ihre Rolle verpflichtend.');
        }
        
        $request->validate([
            'password' => 'required|current_password:portal',
        ]);
        
        app(DisableTwoFactorAuthentication::class)($user);
        
        return back()->with('success', 'Zwei-Faktor-Authentifizierung wurde deaktiviert.');
    }
}