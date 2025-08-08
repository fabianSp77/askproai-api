#!/usr/bin/env php
<?php
/**
 * Create a test endpoint that logs ALL headers
 */

// Add this route temporarily
$routeCode = '
// TEMPORARY: Debug route to log all headers
Route::any("v2/hair-salon-mcp/debug", function(\Illuminate\Http\Request $request) {
    \Log::info("DEBUG MCP Request", [
        "method" => $request->method(),
        "headers" => $request->headers->all(),
        "body" => $request->all(),
        "raw_content" => $request->getContent()
    ]);
    
    // Return a valid MCP response
    return response()->json([
        "jsonrpc" => "2.0",
        "id" => $request->input("id"),
        "result" => [
            "debug" => "Headers received",
            "headers_count" => count($request->headers->all())
        ]
    ]);
});
';

echo "Adding debug route to api.php...\n";
file_put_contents(
    '/var/www/api-gateway/routes/api.php',
    $routeCode,
    FILE_APPEND
);

echo "Debug route added!\n";
echo "URL: https://api.askproai.de/api/v2/hair-salon-mcp/debug\n";
echo "\nYou can now update Retell to use this debug URL temporarily.\n";