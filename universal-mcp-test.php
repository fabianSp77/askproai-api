#!/usr/bin/env php
<?php
/**
 * Create a universal test endpoint that accepts ANY request format
 */

$routeCode = '
// UNIVERSAL MCP TEST - Accepts any format
Route::any("v2/hair-salon-mcp/universal", function(\Illuminate\Http\Request $request) {
    \Log::info("UNIVERSAL MCP REQUEST", [
        "timestamp" => now()->toIso8601String(),
        "method" => $request->method(),
        "all_headers" => $request->headers->all(),
        "body" => $request->all(),
        "raw_body" => $request->getContent(),
        "query_params" => $request->query(),
        "ip" => $request->ip(),
        "user_agent" => $request->userAgent()
    ]);
    
    // Try to detect what Retell wants
    $method = $request->input("method") 
        ?? $request->input("tool") 
        ?? $request->input("function")
        ?? "unknown";
    
    // Always return services for testing
    if (str_contains($method, "list") || str_contains($method, "services") || $method == "unknown") {
        return response()->json([
            "jsonrpc" => "2.0",
            "id" => $request->input("id") ?? "test",
            "result" => [
                "services" => [
                    ["id" => 26, "name" => "Herrenhaarschnitt", "price" => "35.00", "duration" => 30],
                    ["id" => 27, "name" => "Damenhaarschnitt", "price" => "55.00", "duration" => 45]
                ]
            ]
        ]);
    }
    
    return response()->json([
        "jsonrpc" => "2.0",
        "id" => $request->input("id") ?? "test",
        "result" => ["message" => "Universal endpoint received: " . $method]
    ]);
});
';

echo "Adding universal test route...\n";
file_put_contents(
    '/var/www/api-gateway/routes/api.php',
    $routeCode,
    FILE_APPEND
);

echo "✅ Universal test endpoint added!\n\n";
echo "URL: https://api.askproai.de/api/v2/hair-salon-mcp/universal\n\n";
echo "This endpoint:\n";
echo "- Accepts ANY request format\n";
echo "- Logs EVERYTHING\n";
echo "- Always returns services for testing\n\n";
echo "You can temporarily use this URL in Retell to test if it connects at all.\n";