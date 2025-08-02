<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\PortalUser;

class SimpleAuthTestController extends Controller
{
    public function testAdminLogin(Request $request)
    {
        // Find admin user
        $admin = User::where('email', 'fabian@askproai.de')->first();
        
        if (!$admin) {
            return response()->json(['error' => 'Admin user not found']);
        }
        
        // Force login
        Auth::guard('web')->login($admin);
        
        return response()->json([
            'success' => true,
            'user' => $admin->email,
            'authenticated' => Auth::check(),
            'guard' => 'web',
            'redirect' => '/admin'
        ]);
    }
    
    public function testPortalLogin(Request $request)
    {
        // Find portal user
        $portalUser = PortalUser::where("company_id", auth()->user()->company_id)
            ->where('email', 'demo@askproai.de')
            ->orWhere('email', 'admin+1@askproai.de')
            ->first();
        
        if (!$portalUser) {
            return response()->json(['error' => 'Portal user not found']);
        }
        
        // Force login
        Auth::guard('portal')->login($portalUser);
        
        return response()->json([
            'success' => true,
            'user' => $portalUser->email,
            'authenticated' => Auth::guard('portal')->check(),
            'guard' => 'portal',
            'redirect' => '/business/dashboard'
        ]);
    }
}