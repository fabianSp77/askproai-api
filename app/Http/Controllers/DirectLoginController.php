<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class DirectLoginController extends Controller
{
    public function login(Request $request)
    {
        // Get demo user
        $user = User::where('email', 'demo@askproai.de')->first();
        
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
        
        // Set the user on the guard without calling login()
        // This avoids the problematic migrate() call
        $guard = Auth::guard('web');
        $guard->setUser($user);
        
        // Manually set session data
        $session = app('session.store');
        $session->put('login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d', $user->id);
        $session->put('password_hash_web', $user->password);
        
        // Fire login event manually
        event(new \Illuminate\Auth\Events\Login('web', $user, false));
        
        // Save session without migration
        $session->save();
        
        // Set remember token if needed
        if ($request->get('remember')) {
            $guard->setRememberToken($user->getRememberToken());
        }
        
        return redirect('/admin')->with('success', 'Logged in successfully');
    }
    
    public function apiLogin(Request $request)
    {
        $user = User::where('email', 'demo@askproai.de')->first();
        
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
        
        // Direct session manipulation
        $sessionId = session_id() ?: bin2hex(random_bytes(20));
        session_id($sessionId);
        
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        // Set session data
        $_SESSION['login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d'] = $user->id;
        $_SESSION['password_hash_web'] = $user->password;
        $_SESSION['_token'] = csrf_token();
        
        // Also use Laravel's session
        Session::put('login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d', $user->id);
        Session::put('password_hash_web', $user->password);
        Session::save();
        
        return response()->json([
            'success' => true,
            'session_id' => $sessionId,
            'user' => $user->email,
            'redirect' => '/admin'
        ]);
    }
}