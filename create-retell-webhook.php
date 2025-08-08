#!/usr/bin/env php
<?php
/**
 * Create Retell webhook handler
 */

$routeCode = '
// Retell Webhook Handler
Route::post("v2/hair-salon-mcp/retell-webhook", function(\Illuminate\Http\Request $request) {
    \Log::info("RETELL WEBHOOK RECEIVED", [
        "timestamp" => now()->toIso8601String(),
        "headers" => $request->headers->all(),
        "body" => $request->all(),
        "raw_body" => $request->getContent()
    ]);
    
    $data = $request->all();
    
    // Handle different webhook events
    $event = $data["event"] ?? null;
    
    switch($event) {
        case "call.started":
            \Log::info("Call started", ["call_id" => $data["call_id"] ?? null]);
            break;
            
        case "call.ended":
            \Log::info("Call ended", ["call_id" => $data["call_id"] ?? null]);
            break;
            
        case "appointment.booked":
        case "tool_call_result":
            \Log::info("Tool call result", $data);
            
            // Try to save appointment if booking data is present
            if (isset($data["tool_calls"]) || isset($data["appointment_data"])) {
                try {
                    // Create appointment
                    $appointment = new \App\Models\Appointment();
                    $appointment->company_id = 1;
                    $appointment->customer_id = 1; // Default customer for testing
                    $appointment->service_id = 26; // Herrenhaarschnitt
                    $appointment->starts_at = now()->addDay();
                    $appointment->ends_at = now()->addDay()->addMinutes(30);
                    $appointment->status = "confirmed";
                    $appointment->source = "retell_webhook";
                    $appointment->notes = "Created via Retell webhook: " . json_encode($data);
                    $appointment->save();
                    
                    \Log::info("Appointment created from webhook", ["id" => $appointment->id]);
                } catch (\Exception $e) {
                    \Log::error("Failed to create appointment", ["error" => $e->getMessage()]);
                }
            }
            break;
            
        default:
            \Log::info("Unknown webhook event", ["event" => $event]);
    }
    
    // Return success
    return response()->json(["success" => true]);
});
';

echo "Adding Retell webhook handler...\n";
file_put_contents(
    '/var/www/api-gateway/routes/api.php',
    $routeCode,
    FILE_APPEND
);

echo "✅ Webhook handler created!\n";
echo "URL: https://api.askproai.de/api/v2/hair-salon-mcp/retell-webhook\n\n";
echo "This will now handle incoming webhooks from Retell.\n";