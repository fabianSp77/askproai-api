<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\PortalUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class DirectAccessController extends Controller
{
    public function access()
    {
        // Find or create demo user - bypass all scopes
        $user = PortalUser::withoutGlobalScopes()->where('email', 'demo-user@askproai.de')->first();
        
        if (!$user) {
            // Check if user exists in database
            $exists = \DB::table('portal_users')->where('email', 'demo-user@askproai.de')->exists();
            
            if ($exists) {
                // Update existing user
                \DB::table('portal_users')
                    ->where('email', 'demo-user@askproai.de')
                    ->update([
                        'is_active' => true,
                        'password' => Hash::make('demo123'),
                        'updated_at' => now()
                    ]);
                $user = PortalUser::withoutGlobalScopes()->where('email', 'demo-user@askproai.de')->first();
            } else {
                // Create new user
                $user = PortalUser::create([
                    'email' => 'demo-user@askproai.de',
                    'password' => Hash::make('demo123'),
                    'name' => 'Demo User',
                    'company_id' => 1,
                    'is_active' => true,
                    'role' => 'admin',
                    'permissions' => json_encode([
                        'calls.view_all' => true,
                        'billing.view' => true,
                        'billing.manage' => true,
                        'appointments.view_all' => true,
                        'customers.view_all' => true
                    ])
                ]);
            }
        }
        
        // Login the user
        Auth::guard('portal')->login($user);
        
        // Record the login
        $user->recordLogin(request()->ip());
        
        // Set session data
        session(['portal_direct_access' => true]);
        session(['portal_user_id' => $user->id]);
        
        return view('portal.direct-access-success', compact('user'));
    }
}