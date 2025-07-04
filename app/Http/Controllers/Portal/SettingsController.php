<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class SettingsController extends Controller
{
    /**
     * Show settings overview
     */
    public function index()
    {
        $user = Auth::guard('portal')->user();
        
        return view('portal.settings.index', [
            'user' => $user,
        ]);
    }
    
    /**
     * Show profile settings
     */
    public function profile()
    {
        $user = Auth::guard('portal')->user();
        
        return view('portal.settings.profile', [
            'user' => $user,
        ]);
    }
    
    /**
     * Update profile
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::guard('portal')->user();
        
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:portal_users,email,'.$user->id],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);
        
        $user->update($validated);
        
        return back()->with('success', 'Profil wurde aktualisiert.');
    }
    
    /**
     * Show password change form
     */
    public function password()
    {
        return view('portal.settings.password');
    }
    
    /**
     * Update password
     */
    public function updatePassword(Request $request)
    {
        $user = Auth::guard('portal')->user();
        
        $validated = $request->validate([
            'current_password' => ['required', 'current_password:portal'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);
        
        $user->update([
            'password' => Hash::make($validated['password'])
        ]);
        
        return redirect()->route('business.settings.index')
            ->with('success', 'Passwort wurde geändert.');
    }
    
    /**
     * Show notification preferences
     */
    public function notifications()
    {
        $user = Auth::guard('portal')->user();
        
        return view('portal.settings.notifications', [
            'user' => $user,
            'preferences' => $user->notification_preferences ?? [],
        ]);
    }
    
    /**
     * Update notification preferences
     */
    public function updateNotifications(Request $request)
    {
        $user = Auth::guard('portal')->user();
        
        $validated = $request->validate([
            'email_notifications' => ['boolean'],
            'call_assigned' => ['boolean'],
            'daily_summary' => ['boolean'],
            'callback_reminder' => ['boolean'],
            'low_balance_alert' => ['boolean'],
        ]);
        
        $user->notification_preferences = $validated;
        $user->save();
        
        return back()->with('success', 'Benachrichtigungseinstellungen wurden aktualisiert.');
    }
}