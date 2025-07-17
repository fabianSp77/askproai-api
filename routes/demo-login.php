<?php
// Demo login route
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Models\PortalUser;

Route::get('/business/demo-login', function () {
    $token = request('token');
    if (!$token) {
        return redirect('/business/login')->with('error', 'No token provided');
    }
    
    $userId = cache('demo_login_token_' . $token);
    if (!$userId) {
        return redirect('/business/login')->with('error', 'Invalid or expired token');
    }
    
    $user = PortalUser::find($userId);
    if (!$user || !$user->is_active) {
        return redirect('/business/login')->with('error', 'User not found or inactive');
    }
    
    Auth::guard('portal')->login($user);
    cache()->forget('demo_login_token_' . $token);
    
    return redirect('/business')->with('success', 'Logged in as demo user');
})->name('demo-login');