<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DocsAuthController extends Controller
{
    /**
     * Get the currently authenticated username from NGINX Basic Auth
     */
    public static function getUsername(): ?string
    {
        return request()->server('PHP_AUTH_USER');
    }

    /**
     * Show the login form
     */
    public function showLogin(Request $request)
    {
        // If already authenticated via session, redirect to docs
        if ($request->session()->has('docs_authenticated')) {
            return redirect()->route('docs.backup-system.index');
        }

        // Return Laravel login form
        return view('docs.auth.login');
    }

    /**
     * Handle login attempt
     *
     * SECURITY FIX (2025-11-02):
     * - Added session regeneration to prevent session fixation attacks
     * - Session ID now changes after successful authentication
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $username = $request->input('username');
        $password = $request->input('password');

        // Get credentials from environment
        $validUsername = env('DOCS_USERNAME', 'admin');
        $validPassword = env('DOCS_PASSWORD', '');

        // Validate credentials (timing-safe comparison)
        if (hash_equals($validUsername, $username) && hash_equals($validPassword, $password)) {
            // Authentication successful

            // SECURITY FIX: Regenerate session ID to prevent session fixation
            // This ensures any pre-authentication session ID is invalidated
            $request->session()->regenerate();

            $request->session()->put('docs_authenticated', true);
            $request->session()->put('docs_username', $username);
            $request->session()->put('docs_last_activity', time());

            // Remember me functionality
            if ($request->has('remember')) {
                $request->session()->put('docs_remember', true);
            }

            Log::info('Docs login successful', [
                'username' => $username,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            // Redirect to intended URL or docs home
            $intended = $request->session()->get('intended', route('docs.backup-system.index'));
            $request->session()->forget('intended');

            return redirect($intended)->with('success', 'Erfolgreich angemeldet!');
        }

        // Authentication failed
        Log::warning('Docs login failed', [
            'username' => $username,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        return back()
            ->withInput($request->only('username'))
            ->withErrors(['credentials' => 'Benutzername oder Passwort ungültig.']);
    }

    /**
     * Handle logout
     *
     * SECURITY IMPROVEMENT (2025-11-02):
     * - Enhanced session cleanup with regeneration
     * - Prevents session reuse after logout
     */
    public function logout(Request $request)
    {
        $username = $request->session()->get('docs_username');

        Log::info('Docs logout', [
            'username' => $username,
            'ip' => $request->ip()
        ]);

        // Clear session data
        $request->session()->forget(['docs_authenticated', 'docs_username', 'docs_last_activity', 'docs_remember']);

        // Regenerate session ID to prevent session reuse
        $request->session()->regenerate();

        return redirect()->route('docs.backup-system.login')
            ->with('success', 'Sie wurden erfolgreich abgemeldet.');
    }
}
