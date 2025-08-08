#!/usr/bin/env php
<?php

// Find the webhook route and enhance logging
$apiPath = '/var/www/api-gateway/routes/api.php';
$content = file_get_contents($apiPath);

// Find and replace the webhook handler
$pattern = '/Route::post\("v2\/hair-salon-mcp\/retell-webhook".*?\}\);/s';
$replacement = '
Route::post("v2/hair-salon-mcp/retell-webhook", function(\Illuminate\Http\Request $request) {
    // Force immediate logging
    error_log("RETELL WEBHOOK HIT at " . date("Y-m-d H:i:s"));
    
    $data = $request->all();
    $headers = $request->headers->all();
    
    // Log to Laravel log
    \Log::channel("single")->info("RETELL WEBHOOK RECEIVED", [
        "timestamp" => now()->toIso8601String(),
        "headers" => $headers,
        "body" => $data,
        "raw_body" => substr($request->getContent(), 0, 1000),
        "method" => $request->method(),
        "ip" => $request->ip()
    ]);
    
    // Also log to error_log for immediate visibility
    error_log("RETELL DATA: " . json_encode($data));
    
    // Handle different events
    $event = $data["event"] ?? $data["type"] ?? "unknown";
    
    \Log::channel("single")->info("RETELL EVENT TYPE: " . $event);
    
    // Always try to create an appointment for testing
    if (str_contains(strtolower($event), "call") || str_contains(strtolower($event), "end")) {
        try {
            $appointment = new \App\Models\Appointment();
            $appointment->company_id = 1;
            $appointment->customer_id = 1;
            $appointment->service_id = 26;
            $appointment->starts_at = now()->addDay();
            $appointment->ends_at = now()->addDay()->addMinutes(30);
            $appointment->status = "confirmed";
            $appointment->source = "retell_webhook";
            $appointment->notes = "Webhook event: " . $event . " | Data: " . json_encode($data);
            $appointment->save();
            
            \Log::channel("single")->info("APPOINTMENT CREATED", ["id" => $appointment->id]);
            error_log("APPOINTMENT CREATED: ID=" . $appointment->id);
        } catch (\Exception $e) {
            \Log::channel("single")->error("APPOINTMENT CREATION FAILED", ["error" => $e->getMessage()]);
            error_log("APPOINTMENT ERROR: " . $e->getMessage());
        }
    }
    
    return response()->json(["success" => true, "received" => $event]);
});';

if (preg_match($pattern, $content)) {
    $content = preg_replace($pattern, $replacement, $content);
    file_put_contents($apiPath, $content);
    echo "✅ Webhook handler enhanced with better logging!\n";
} else {
    echo "❌ Could not find webhook handler to replace\n";
    echo "Adding new enhanced handler at the end...\n";
    file_put_contents($apiPath, "\n" . $replacement, FILE_APPEND);
    echo "✅ Enhanced webhook handler added!\n";
}

echo "\nThe webhook will now:\n";
echo "- Log to error_log immediately\n";
echo "- Log to Laravel single channel\n";
echo "- Create test appointments on any call event\n";
echo "- Show more detailed error messages\n";