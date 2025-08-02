<?php

namespace App\Http\Controllers\Portal\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Call;

class DebugCallsController extends Controller
{
    public function debug(Request $request)
    {
        $user = auth()->guard('portal')->user() ?: auth()->guard('web')->user();
        
        if (!$user) {
            return response()->json(['error' => 'No user found'], 401);
        }

        try {
            $company = $user->company;
            
            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'company_id' => $user->company_id ?? null,
                ],
                'company' => $company ? [
                    'id' => $company->id,
                    'name' => $company->name,
                ] : null,
                'calls_count' => $company ? Call::where('company_id', $company->id)->count() : 0,
                'test_query' => $company ? Call::where('company_id', $company->id)->limit(1)->toSql() : null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }
}