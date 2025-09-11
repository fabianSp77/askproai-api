<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\Call;
use App\Models\Appointment;
use Illuminate\Support\Facades\Log;

class BillingUsageTracker
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
        $response = $next($request);
        
        // Only track for authenticated API requests with tenant context
        if ($request->is('api/*') && $request->user() && $request->user()->tenant_id) {
            $this->trackUsage($request, $response);
        }
        
        return $response;
    }
    
    /**
     * Track usage based on the request type
     */
    protected function trackUsage(Request $request, $response)
    {
        $tenant = Tenant::find($request->user()->tenant_id);
        
        if (!$tenant || !$tenant->pricingPlan) {
            return; // No billing if no pricing plan
        }
        
        $pricingPlan = $tenant->pricingPlan;
        
        // Track based on endpoint patterns
        if ($request->is('api/retell/webhook') && $request->method() === 'POST') {
            $this->trackCallUsage($request, $tenant, $pricingPlan);
        } elseif ($request->is('api/appointments') && $request->method() === 'POST') {
            $this->trackAppointmentUsage($request, $tenant, $pricingPlan);
        }
    }
    
    /**
     * Track call usage from Retell webhook
     */
    protected function trackCallUsage(Request $request, Tenant $tenant, $pricingPlan)
    {
        $data = $request->all();
        
        // Check if this is a call end event
        if (($data['event'] ?? null) === 'call_ended') {
            $callId = $data['call_id'] ?? null;
            $durationSeconds = $data['duration_seconds'] ?? 0;
            
            if ($callId && $durationSeconds > 0) {
                // Find or create the call record
                $call = Call::where('retell_call_id', $callId)
                    ->orWhere('call_id', $callId)
                    ->first();
                
                if ($call) {
                    // Calculate cost based on pricing plan
                    $durationMinutes = ceil($durationSeconds / 60);
                    $callCost = $pricingPlan->price_per_call_cents;
                    $minuteCost = $pricingPlan->price_per_minute_cents * $durationMinutes;
                    $totalCost = $callCost + $minuteCost;
                    
                    // Apply volume discount if applicable
                    if ($pricingPlan->volume_threshold_minutes > 0 && 
                        $durationMinutes >= $pricingPlan->volume_threshold_minutes) {
                        $discount = $pricingPlan->volume_discount_percent / 100;
                        $totalCost = $totalCost * (1 - $discount);
                    }
                    
                    // Check if tenant has sufficient balance
                    if ($tenant->hasSufficientBalance($totalCost)) {
                        // Deduct balance and create transaction
                        $transaction = $tenant->deductBalance(
                            $totalCost,
                            "Anruf #{$call->id}: {$durationMinutes} Minuten"
                        );
                        
                        // Link transaction to call
                        $transaction->update(['call_id' => $call->id]);
                        
                        // Update call with cost information
                        $call->update([
                            'cost_cents' => $totalCost,
                            'billed_at' => now()
                        ]);
                        
                        Log::info("Call usage tracked", [
                            'tenant_id' => $tenant->id,
                            'call_id' => $call->id,
                            'duration_minutes' => $durationMinutes,
                            'cost_cents' => $totalCost
                        ]);
                    } else {
                        // Insufficient balance - mark call as unbilled
                        $call->update([
                            'cost_cents' => $totalCost,
                            'billing_status' => 'insufficient_balance'
                        ]);
                        
                        Log::warning("Insufficient balance for call", [
                            'tenant_id' => $tenant->id,
                            'call_id' => $call->id,
                            'required_cents' => $totalCost,
                            'available_cents' => $tenant->balance_cents
                        ]);
                        
                        // Optionally send notification to tenant
                        $this->notifyInsufficientBalance($tenant, 'call', $totalCost);
                    }
                }
            }
        }
    }
    
    /**
     * Track appointment usage
     */
    protected function trackAppointmentUsage(Request $request, Tenant $tenant, $pricingPlan)
    {
        // Check if response indicates successful appointment creation
        if ($request->response && $request->response->status() === 201) {
            $appointmentData = json_decode($request->response->content(), true);
            $appointmentId = $appointmentData['id'] ?? null;
            
            if ($appointmentId) {
                $appointment = Appointment::find($appointmentId);
                
                if ($appointment) {
                    $cost = $pricingPlan->price_per_appointment_cents;
                    
                    // Check if tenant has sufficient balance
                    if ($tenant->hasSufficientBalance($cost)) {
                        // Deduct balance and create transaction
                        $transaction = $tenant->deductBalance(
                            $cost,
                            "Terminbuchung #{$appointment->id}"
                        );
                        
                        // Link transaction to appointment
                        $transaction->update(['appointment_id' => $appointment->id]);
                        
                        Log::info("Appointment usage tracked", [
                            'tenant_id' => $tenant->id,
                            'appointment_id' => $appointment->id,
                            'cost_cents' => $cost
                        ]);
                    } else {
                        // Insufficient balance - mark appointment
                        Log::warning("Insufficient balance for appointment", [
                            'tenant_id' => $tenant->id,
                            'appointment_id' => $appointment->id,
                            'required_cents' => $cost,
                            'available_cents' => $tenant->balance_cents
                        ]);
                        
                        $this->notifyInsufficientBalance($tenant, 'appointment', $cost);
                    }
                }
            }
        }
    }
    
    /**
     * Send notification about insufficient balance
     */
    protected function notifyInsufficientBalance(Tenant $tenant, string $type, int $requiredCents)
    {
        // You can implement email/SMS notification here
        // For now, just log it
        Log::alert("Tenant {$tenant->name} has insufficient balance", [
            'tenant_id' => $tenant->id,
            'type' => $type,
            'required_cents' => $requiredCents,
            'available_cents' => $tenant->balance_cents,
            'shortage_cents' => $requiredCents - $tenant->balance_cents
        ]);
        
        // Create a system transaction record for tracking
        Transaction::create([
            'tenant_id' => $tenant->id,
            'type' => Transaction::TYPE_FEE,
            'amount_cents' => 0, // No actual deduction
            'balance_before_cents' => $tenant->balance_cents,
            'balance_after_cents' => $tenant->balance_cents,
            'description' => "ABGELEHNT: {$type} - Unzureichendes Guthaben (benötigt: " . number_format($requiredCents / 100, 2) . " €)",
            'metadata' => [
                'type' => $type,
                'required_cents' => $requiredCents,
                'shortage_cents' => $requiredCents - $tenant->balance_cents,
                'rejected_at' => now()->toISOString()
            ]
        ]);
    }
}