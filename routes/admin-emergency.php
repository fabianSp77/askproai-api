<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

// SECURITY WARNING: Emergency admin login route - USE WITH EXTREME CAUTION
// This route bypasses CSRF but is heavily rate limited (2 attempts per hour)
Route::post('/admin-emergency-login', function (Request $request) {
    // Log all emergency login attempts for security audit
    \Log::warning('Emergency admin login attempt', [
        'ip' => $request->ip(),
        'user_agent' => $request->userAgent(),
        'email' => $request->input('email'),
    ]);
    
    $email = $request->input('email');
    $password = $request->input('password');
    
    $user = User::where('email', $email)->first();
    
    if ($user && Hash::check($password, $user->password)) {
        Auth::guard('web')->login($user);
        session()->regenerate();
        
        // Log successful emergency login
        \Log::warning('Emergency admin login successful', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $request->ip(),
        ]);
        
        return redirect('/admin')->with('success', 'Logged in via emergency route');
    }
    
    return back()->withErrors(['email' => 'Invalid credentials']);
})
->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])
->middleware(['web', 'auth.rate.limit']); // Apply rate limiting even to emergency route

// Emergency login form
Route::get('/admin-emergency', function () {
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <title>Emergency Admin Login</title>
        <style>
            body { font-family: Arial; max-width: 400px; margin: 50px auto; padding: 20px; }
            input, button { display: block; width: 100%; margin: 10px 0; padding: 10px; }
            .error { color: red; }
        </style>
    </head>
    <body>
        <h2>Emergency Admin Login</h2>
        <p style="color: orange;">⚠️ Use only if normal login fails with 419 error</p>
        
        <form method="POST" action="/admin-emergency-login">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        
        <hr>
        <p><a href="/admin">Try Normal Login</a></p>
    </body>
    </html>
    ';
});