#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\MCP\ApidogMCPServer;

try {
    echo "Testing Apidog MCP Server...\n";
    echo str_repeat("=", 50) . "\n\n";

    // Initialize Apidog MCP Server
    $apidog = app(ApidogMCPServer::class);
    
    // Get server info
    echo "Server Information:\n";
    echo "- Name: " . $apidog->getName() . "\n";
    echo "- Version: " . $apidog->getVersion() . "\n";
    echo "- Capabilities: " . implode(', ', $apidog->getCapabilities()) . "\n\n";

    // List available tools
    echo "Available Apidog Tools:\n";
    $tools = $apidog->getTools();
    foreach ($tools as $tool) {
        echo "  - {$tool['name']}: {$tool['description']}\n";
    }
    echo "\n";

    // Test with a sample OpenAPI spec (using GitHub's public API spec)
    echo "Testing fetch API specification from public OpenAPI spec...\n";
    $result = $apidog->executeTool('fetch_api_spec', [
        'source' => 'https://raw.githubusercontent.com/github/rest-api-description/main/descriptions/api.github.com/api.github.com.json',
        'format' => 'openapi'
    ]);

    if ($result['success']) {
        echo "✅ API spec fetched successfully!\n";
        $data = $result['data'];
        echo "  - Spec ID: {$data['spec_id']}\n";
        echo "  - Title: {$data['title']}\n";
        echo "  - Version: {$data['version']}\n";
        echo "  - Endpoints: " . count($data['paths']) . " paths\n";
        
        $specId = $data['spec_id'];
        
        // Test list endpoints
        echo "\nTesting list endpoints...\n";
        $result = $apidog->executeTool('list_endpoints', [
            'spec_id' => $specId
        ]);
        
        if ($result['success']) {
            echo "✅ Endpoints listed successfully!\n";
            $endpoints = array_slice($result['data'], 0, 5); // First 5 endpoints
            foreach ($endpoints as $endpoint) {
                echo "  - {$endpoint['method']} {$endpoint['path']}\n";
                if ($endpoint['summary']) {
                    echo "    Summary: {$endpoint['summary']}\n";
                }
            }
            echo "  ... and " . (count($result['data']) - 5) . " more endpoints\n";
        }
        
        // Test get endpoint details
        echo "\nTesting get endpoint details (GET /users/{username})...\n";
        $result = $apidog->executeTool('get_endpoint_details', [
            'spec_id' => $specId,
            'path' => '/users/{username}',
            'method' => 'GET'
        ]);
        
        if ($result['success']) {
            echo "✅ Endpoint details retrieved!\n";
            $details = $result['data'];
            echo "  - Path: {$details['path']}\n";
            echo "  - Method: {$details['method']}\n";
            if (isset($details['operation']['summary'])) {
                echo "  - Summary: {$details['operation']['summary']}\n";
            }
            if (isset($details['operation']['parameters'])) {
                echo "  - Parameters: " . count($details['operation']['parameters']) . "\n";
            }
        }
        
        // Test code generation
        echo "\nTesting PHP client code generation...\n";
        $result = $apidog->executeTool('generate_code', [
            'spec_id' => $specId,
            'language' => 'php',
            'type' => 'client',
            'endpoints' => ['/users/{username}', '/user/repos']
        ]);
        
        if ($result['success']) {
            echo "✅ Code generated successfully!\n";
            echo "  - Language: {$result['data']['language']}\n";
            echo "  - Type: {$result['data']['type']}\n";
            echo "  - Sample of generated code:\n";
            $lines = explode("\n", $result['data']['code']);
            foreach (array_slice($lines, 0, 10) as $line) {
                echo "    {$line}\n";
            }
            echo "    ... (truncated)\n";
        }
        
        // Test list cached specs
        echo "\nTesting list cached specifications...\n";
        $result = $apidog->executeTool('list_cached_specs', []);
        
        if ($result['success']) {
            echo "✅ Cached specs listed!\n";
            foreach ($result['data'] as $spec) {
                echo "  - ID: {$spec['id']}\n";
                echo "    Title: {$spec['title']}\n";
                echo "    Version: {$spec['version']}\n";
            }
        }
        
    } else {
        echo "❌ Failed to fetch API spec: " . $result['error'] . "\n";
    }

    echo "\n✅ Apidog MCP Server is working correctly!\n";
    echo "\nNote: To use with your own Apidog projects:\n";
    echo "1. Set APIDOG_API_KEY in your .env file\n";
    echo "2. Set APIDOG_PROJECT_ID for your default project\n";
    echo "3. Use Apidog project URLs or OpenAPI spec URLs\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}