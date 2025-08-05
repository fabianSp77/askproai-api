<?php

namespace App\Http\Controllers\Portal\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthCheckController extends Controller
{
    /**
     * Check if the user is authenticated
     */
    public function check(Request $request): JsonResponse
    {
        if (auth()->check()) {
            return response()->json([
                'authenticated' => true,
                'user' => auth()->user()->only(['id', 'name', 'email']),
            ]);
        }

        return response()->json([
            'authenticated' => false,
        ], 401);
    }
}