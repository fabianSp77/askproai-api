<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TwoFactorController extends Controller
{
    public function enable(Request $request)
    {
        return response()->json([
            'message' => '2FA enable not implemented'
        ], 501);
    }
    
    public function disable(Request $request)
    {
        return response()->json([
            'message' => '2FA disable not implemented'
        ], 501);
    }
}