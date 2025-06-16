<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FrontendErrorController extends Controller
{
    public function log(Request $request)
    {
        $errorData = $request->all();
        $errorData['user_id'] = auth()->id();
        $errorData['user_name'] = auth()->user()?->name;
        $errorData['session_id'] = session()->getId();
        $errorData['ip'] = $request->ip();
        
        // Log to a separate channel for frontend errors
        Log::channel('frontend')->error('Frontend Error', $errorData);
        
        // Also log redirects to main log for easier debugging
        if (($errorData['type'] ?? '') === 'unexpected-redirect') {
            Log::warning('FRONTEND REDIRECT DETECTED', $errorData);
        }
        
        return response()->json(['logged' => true]);
    }
}