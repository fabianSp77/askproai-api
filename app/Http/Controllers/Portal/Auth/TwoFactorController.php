<?php

namespace App\Http\Controllers\Portal\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TwoFactorController extends Controller
{
    public function showSetupForm(Request $request)
    {
        // Skip 2FA setup - redirect to dashboard
        return redirect()->route('business.dashboard');
    }

    public function confirmSetup(Request $request)
    {
        return redirect()->route('business.dashboard');
    }

    public function enable(Request $request)
    {
        return redirect()->route('business.dashboard');
    }

    public function showChallengeForm(Request $request)
    {
        return redirect()->route('business.dashboard');
    }

    public function verifyChallenge(Request $request)
    {
        return redirect()->route('business.dashboard');
    }

    public function disable(Request $request)
    {
        $user = $request->user('portal');
        if ($user) {
            $user->two_factor_enforced = false;
            $user->two_factor_secret = null;
            $user->two_factor_confirmed_at = null;
            $user->save();
        }

        return redirect()->route('business.dashboard');
    }

    // Alias methods for route compatibility
    public function setup(Request $request)
    {
        return $this->confirmSetup($request);
    }

    public function challenge(Request $request)
    {
        return $this->verifyChallenge($request);
    }

    // Legacy methods for compatibility
    public function show(Request $request)
    {
        return $this->showChallengeForm($request);
    }

    public function verify(Request $request)
    {
        return $this->verifyChallenge($request);
    }

    public function resend(Request $request)
    {
        return response()->json(['message' => '2FA not implemented']);
    }
}
