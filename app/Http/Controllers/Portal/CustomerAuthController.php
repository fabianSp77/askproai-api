<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\CustomerAuth;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use App\Services\CustomerPortalService;
use Illuminate\Auth\Events\PasswordReset;

class CustomerAuthController extends Controller
{
    protected CustomerPortalService $portalService;
    
    public function __construct(CustomerPortalService $portalService)
    {
        $this->portalService = $portalService;
    }
    
    /**
     * Show login form.
     */
    public function showLoginForm(Request $request)
    {
        $company = $this->getCompanyFromSubdomain($request);
        
        return view('portal.auth.login', [
            'company' => $company,
        ]);
    }
    
    /**
     * Handle login request.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);
        
        // Add company scope to credentials
        $company = $this->getCompanyFromSubdomain($request);
        $credentials['company_id'] = $company->id;
        $credentials['portal_enabled'] = true;
        
        if (Auth::guard('customer')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            
            /** @var CustomerAuth $customer */
            $customer = Auth::guard('customer')->user();
            $customer->recordPortalLogin();
            
            return redirect()->intended(route('portal.dashboard'));
        }
        
        return back()->withErrors([
            'email' => 'Die angegebenen Anmeldedaten sind ungültig.',
        ])->onlyInput('email');
    }
    
    /**
     * Handle magic link login.
     */
    public function magicLinkLogin(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);
        
        $company = $this->getCompanyFromSubdomain($request);
        
        $customer = CustomerAuth::where('email', $request->email)
            ->where('company_id', $company->id)
            ->where('portal_enabled', true)
            ->first();
            
        if (!$customer) {
            return back()->with('status', 'Wenn ein Konto mit dieser E-Mail existiert, wurde ein Login-Link gesendet.');
        }
        
        $token = $customer->generatePortalAccessToken();
        
        // Send magic link email
        $this->portalService->sendMagicLink($customer, $token);
        
        return back()->with('status', 'Ein Login-Link wurde an Ihre E-Mail-Adresse gesendet.');
    }
    
    /**
     * Handle magic link verification.
     */
    public function verifyMagicLink(Request $request, string $token)
    {
        $customer = CustomerAuth::where('portal_access_token', hash('sha256', $token))
            ->where('portal_token_expires_at', '>', now())
            ->first();
            
        if (!$customer) {
            return redirect()->route('portal.login')
                ->withErrors(['token' => 'Der Login-Link ist ungültig oder abgelaufen.']);
        }
        
        Auth::guard('customer')->login($customer);
        $customer->recordPortalLogin();
        
        // Clear the token
        $customer->update([
            'portal_access_token' => null,
            'portal_token_expires_at' => null,
        ]);
        
        return redirect()->route('portal.dashboard');
    }
    
    /**
     * Handle logout.
     */
    public function logout(Request $request)
    {
        Auth::guard('customer')->logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('portal.login');
    }
    
    /**
     * Show password reset form.
     */
    public function showResetForm(Request $request)
    {
        $company = $this->getCompanyFromSubdomain($request);
        
        return view('portal.auth.forgot-password', [
            'company' => $company,
        ]);
    }
    
    /**
     * Send password reset link.
     */
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);
        
        $company = $this->getCompanyFromSubdomain($request);
        
        // Add company context
        $credentials = [
            'email' => $request->email,
            'company_id' => $company->id,
        ];
        
        $status = Password::broker('customers')->sendResetLink($credentials);
        
        return $status === Password::RESET_LINK_SENT
            ? back()->with(['status' => __($status)])
            : back()->withErrors(['email' => __($status)]);
    }
    
    /**
     * Show new password form.
     */
    public function showNewPasswordForm(Request $request, string $token)
    {
        $company = $this->getCompanyFromSubdomain($request);
        
        return view('portal.auth.reset-password', [
            'token' => $token,
            'email' => $request->email,
            'company' => $company,
        ]);
    }
    
    /**
     * Reset password.
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8',
        ]);
        
        $company = $this->getCompanyFromSubdomain($request);
        
        $credentials = [
            'email' => $request->email,
            'company_id' => $company->id,
        ];
        
        $status = Password::broker('customers')->reset(
            array_merge($credentials, $request->only('password', 'password_confirmation', 'token')),
            function ($customer, $password) {
                $customer->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));
                
                $customer->save();
                
                event(new PasswordReset($customer));
            }
        );
        
        return $status === Password::PASSWORD_RESET
            ? redirect()->route('portal.login')->with('status', __($status))
            : back()->withErrors(['email' => [__($status)]]);
    }
    
    /**
     * Get company from subdomain.
     */
    protected function getCompanyFromSubdomain(Request $request): Company
    {
        $subdomain = explode('.', $request->getHost())[0];
        
        // For now, return the first active company
        // In production, this should use proper subdomain/slug mapping
        $company = Company::where('is_active', true)
            ->first();
            
        if (!$company) {
            abort(404, 'No active company found');
        }
            
        return $company;
    }
}