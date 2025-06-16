<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class DebugLoginController extends Controller
{
    public function showForm()
    {
        return view('debug-login-form');
    }
    
    public function attemptLogin(Request $request)
    {
        $logData = [];
        
        // 1. Basic info
        $logData['1_request'] = [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'session_id' => session()->getId(),
            'has_csrf' => $request->has('_token'),
            'ip' => $request->ip(),
        ];
        
        // 2. Check user exists
        $user = User::where('email', 'fabian@askproai.de')->first();
        $logData['2_user_check'] = [
            'user_exists' => $user ? 'YES' : 'NO',
            'user_id' => $user?->id,
            'user_table' => $user?->getTable(),
        ];
        
        // 3. Verify password
        $passwordValid = $user ? password_verify('Qwe421as1!1', $user->password) : false;
        $logData['3_password_check'] = [
            'password_valid' => $passwordValid ? 'YES' : 'NO',
        ];
        
        // 4. Try Auth::attempt
        $credentials = [
            'email' => 'fabian@askproai.de',
            'password' => 'Qwe421as1!1'
        ];
        
        $logData['4_before_attempt'] = [
            'auth_check' => Auth::check(),
            'session_id' => session()->getId(),
        ];
        
        $result = Auth::attempt($credentials);
        
        $logData['5_after_attempt'] = [
            'result' => $result ? 'SUCCESS' : 'FAILED',
            'auth_check' => Auth::check(),
            'auth_user' => Auth::user()?->email,
            'session_id' => session()->getId(),
        ];
        
        // 6. Check session persistence
        session()->save();
        
        $logData['6_after_session_save'] = [
            'auth_check' => Auth::check(),
            'session_driver' => config('session.driver'),
        ];
        
        // Write to file directly
        $logFile = storage_path('logs/debug-login.log');
        file_put_contents($logFile, "\n=== DEBUG LOGIN ATTEMPT " . now() . " ===\n" . json_encode($logData, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);
        
        // 7. Return response
        if ($result) {
            return redirect('/admin')->with('debug', 'Login successful!');
        } else {
            return back()->with('error', 'Login failed - check /storage/logs/debug-login.log');
        }
    }
}