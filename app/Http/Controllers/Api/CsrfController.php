<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class CsrfController extends Controller
{
    /**
     * Get a fresh CSRF token
     */
    public function token(): JsonResponse
    {
        return response()->json([
            'token' => csrf_token()
        ]);
    }
}