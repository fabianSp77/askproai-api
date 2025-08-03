<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WorkingDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::guard('portal')->user();
        
        if (!$user) {
            return redirect()->route('business.login')
                ->with('error', 'Bitte melden Sie sich an.');
        }
        
        return view('portal.working-dashboard', [
            'user' => $user,
            'company_id' => $user->company_id,
        ]);
    }
}