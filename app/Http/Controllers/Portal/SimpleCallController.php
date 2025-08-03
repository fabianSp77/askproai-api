<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Call;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SimpleCallController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::guard('portal')->user();
        
        if (!$user || !$user->company_id) {
            abort(401, 'Unauthorized');
        }
        
        $calls = Call::where('company_id', $user->company_id)
            ->with(['customer', 'branch', 'staff'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
            
        // Use unified layout for consistency
        return view("portal.calls.index-unified", compact('calls'));
    }
}
