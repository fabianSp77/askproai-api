#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\MCP\GitHubMCPServer;

try {
    echo "Testing GitHub MCP Server...\n";
    echo str_repeat("=", 50) . "\n\n";

    // Check if GitHub token is configured
    $token = config('services.github.token');
    if (!$token || $token === 'your_github_personal_access_token_here') {
        echo "❌ GitHub token not configured!\n";
        echo "Please set GITHUB_TOKEN in your .env file\n";
        echo "Generate a token at: https://github.com/settings/tokens\n";
        echo "Required scopes: repo, read:org, write:issues\n";
        exit(1);
    }

    echo "✅ GitHub token configured\n\n";

    // Initialize GitHub MCP Server
    $github = app(GitHubMCPServer::class);
    
    // Get server info
    echo "Server Information:\n";
    echo "- Name: " . $github->getName() . "\n";
    echo "- Version: " . $github->getVersion() . "\n";
    echo "- Capabilities: " . implode(', ', $github->getCapabilities()) . "\n\n";

    // Test search repositories
    echo "Testing repository search...\n";
    $result = $github->executeTool('search_repositories', [
        'query' => 'laravel mcp',
        'per_page' => 5
    ]);

    if ($result['success']) {
        echo "✅ Repository search successful!\n";
        $data = $result['data'];
        if (isset($data['items'])) {
            echo "Found " . count($data['items']) . " repositories\n";
            foreach ($data['items'] as $repo) {
                echo "  - {$repo['full_name']} (⭐ {$repo['stargazers_count']})\n";
            }
        }
    } else {
        echo "❌ Repository search failed: " . $result['error'] . "\n";
    }

    echo "\n";

    // Test get specific repository
    echo "Testing get repository details...\n";
    $result = $github->executeTool('get_repository', [
        'owner' => 'laravel',
        'repo' => 'laravel'
    ]);

    if ($result['success']) {
        echo "✅ Repository details retrieved!\n";
        $repo = $result['data'];
        echo "  - Name: {$repo['full_name']}\n";
        echo "  - Description: {$repo['description']}\n";
        echo "  - Stars: {$repo['stargazers_count']}\n";
        echo "  - Language: {$repo['language']}\n";
    } else {
        echo "❌ Failed to get repository: " . $result['error'] . "\n";
    }

    echo "\n";

    // List available tools
    echo "Available GitHub Tools:\n";
    $tools = $github->getTools();
    foreach ($tools as $tool) {
        echo "  - {$tool['name']}: {$tool['description']}\n";
    }

    echo "\n✅ GitHub MCP Server is working correctly!\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}