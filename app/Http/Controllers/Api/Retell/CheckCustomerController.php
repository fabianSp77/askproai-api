<?php

namespace App\Http\Controllers\Api\Retell;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckCustomerController extends Controller
{
    /**
     * Check if customer is known and predict their service preference
     * V110 - Simplified version for initial deployment
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkCustomer(Request $request)
    {
        try {
            $callId = $request->input('call_id');
            $phoneNumber = $request->input('from_number') ?? null;

            Log::info('Retell check_customer called', [
                'call_id' => $callId,
                'phone_number' => $phoneNumber
            ]);

            // For initial V110 deployment: Always return found=false
            // This allows V110 to work immediately without database dependencies
            // TODO: Implement full customer recognition logic after deployment
            return response()->json([
                'found' => false
            ]);

        } catch (\Exception $e) {
            Log::error('Error in check_customer', [
                'call_id' => $request->input('call_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'found' => false
            ]);
        }
    }
}
