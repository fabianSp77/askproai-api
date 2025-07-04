<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Call;
use Illuminate\Support\Facades\Log;

/**
 * Middleware to check if the company requires appointment booking
 * Blocks access to appointment-related endpoints for companies with needs_appointment_booking = false
 */
class CheckAppointmentBookingRequired
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Extract call_id from various possible locations in the request
        $callId = null;
        
        // Check in request data
        $data = $request->all();
        
        // Check different possible locations for call_id
        if (isset($data['call_id'])) {
            $callId = $data['call_id'];
        } elseif (isset($data['call']['call_id'])) {
            $callId = $data['call']['call_id'];
        } elseif (isset($data['args']['call_id']) && $data['args']['call_id'] !== '{{call_id}}') {
            $callId = $data['args']['call_id'];
        }
        
        // If we have a call_id, check if the company needs appointment booking
        if ($callId) {
            $call = Call::where('retell_call_id', $callId)
                ->orWhere('call_id', $callId)
                ->first();
                
            if ($call && $call->company) {
                if (!$call->company->needsAppointmentBooking()) {
                    Log::warning('Appointment endpoint blocked by middleware', [
                        'endpoint' => $request->path(),
                        'company_id' => $call->company_id,
                        'call_id' => $callId,
                        'company_name' => $call->company->name
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Diese Funktion ist für Ihr Unternehmen nicht verfügbar.',
                        'error_code' => 'APPOINTMENT_BOOKING_NOT_ENABLED'
                    ], 403);
                }
            }
        }
        
        // If no call_id found or company allows appointment booking, proceed
        return $next($request);
    }
}