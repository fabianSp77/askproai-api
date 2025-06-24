<?php

use App\Services\MCP\CalcomMCPServer;
use App\Models\Company;
use Illuminate\Support\Facades\Log;

require_once __DIR__ . "/vendor/autoload.php";
$app = require_once __DIR__ . "/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing MCP Cal.com Server directly\n";
echo "===================================\n\n";

try {
    // Test CalcomMCPServer directly
    $calcomMCP = new CalcomMCPServer();
    
    echo "1. Testing getEventTypes method...\n";
    $company = Company::first();
    
    if ($company) {
        $result = $calcomMCP->getEventTypes(["company_id" => $company->id]);
        
        if (isset($result["error"])) {
            echo "Error: " . $result["error"] . "\n";
        } else {
            echo "Success\! Found event types for company: " . ($result["company"] ?? "Unknown") . "\n";
            echo "Count: " . ($result["count"] ?? 0) . "\n";
        }
    } else {
        echo "No company found for testing\n";
    }
    
    echo "\n✅ MCP CalcomMCPServer test completed\n";
    
} catch (\Exception $e) {
    echo "\n❌ Error during test:\n";
    echo $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
