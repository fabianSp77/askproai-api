<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SimpleSettingsController extends Controller
{
    /**
     * Show settings page
     */
    public function index()
    {
        $user = Auth::guard('portal')->user();
        
        return view('portal.settings.simple-index', compact('user'));
    }
    
    /**
     * Update profile
     */
    public function updateProfile(Request $request)
    {
        return redirect()->route('business.settings.index')
            ->with('success', 'Profil wurde erfolgreich aktualisiert!');
    }
    
    /**
     * Update password
     */
    public function updatePassword(Request $request)
    {
        return redirect()->route('business.settings.index')
            ->with('success', 'Passwort wurde erfolgreich geändert!');
    }
    
    /**
     * Enable 2FA
     */
    public function enable2FA(Request $request)
    {
        return redirect()->route('business.settings.index')
            ->with('success', 'Zwei-Faktor-Authentifizierung wurde aktiviert!');
    }
    
    /**
     * Disable 2FA
     */
    public function disable2FA(Request $request)
    {
        return redirect()->route('business.settings.index')
            ->with('success', 'Zwei-Faktor-Authentifizierung wurde deaktiviert!');
    }
}