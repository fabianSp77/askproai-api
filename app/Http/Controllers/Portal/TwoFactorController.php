<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TwoFactorController extends Controller
{
    public function setup(Request $request)
    {
        // Skip 2FA setup and redirect to dashboard
        return redirect()->route('business.dashboard');
    }

    public function confirm(Request $request)
    {
        return redirect()->route('business.dashboard');
    }

    public function enable(Request $request)
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
}
