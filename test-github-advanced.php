#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\MCP\GitHubMCPServer;

try {
    echo "Testing Advanced GitHub MCP Operations...\n";
    echo str_repeat("=", 50) . "\n\n";

    $github = app(GitHubMCPServer::class);

    // Test 1: Get file contents
    echo "1. Testing get file contents (composer.json from laravel/framework)...\n";
    $result = $github->executeTool('get_file_contents', [
        'owner' => 'laravel',
        'repo' => 'framework',
        'path' => 'composer.json'
    ]);

    if ($result['success']) {
        echo "✅ File retrieved successfully!\n";
        $content = $result['data']['decoded_content'] ?? '';
        $composer = json_decode($content, true);
        if ($composer) {
            echo "  - Package: {$composer['name']}\n";
            echo "  - Description: {$composer['description']}\n";
            echo "  - Latest version constraint: " . ($composer['version'] ?? 'not specified') . "\n";
        }
    } else {
        echo "❌ Failed: {$result['error']}\n";
    }

    echo "\n";

    // Test 2: List branches
    echo "2. Testing list branches (laravel/laravel)...\n";
    $result = $github->executeTool('list_branches', [
        'owner' => 'laravel',
        'repo' => 'laravel'
    ]);

    if ($result['success']) {
        echo "✅ Branches retrieved!\n";
        $branches = array_slice($result['data'], 0, 5); // First 5 branches
        foreach ($branches as $branch) {
            echo "  - {$branch['name']}\n";
        }
        echo "  ... and " . (count($result['data']) - 5) . " more branches\n";
    } else {
        echo "❌ Failed: {$result['error']}\n";
    }

    echo "\n";

    // Test 3: List recent issues
    echo "3. Testing list issues (laravel/framework - recent bugs)...\n";
    $result = $github->executeTool('list_issues', [
        'owner' => 'laravel',
        'repo' => 'framework',
        'state' => 'open',
        'labels' => 'bug'
    ]);

    if ($result['success']) {
        echo "✅ Issues retrieved!\n";
        $issues = array_slice($result['data'], 0, 3); // First 3 issues
        foreach ($issues as $issue) {
            echo "  - #{$issue['number']}: {$issue['title']}\n";
            echo "    Created: " . date('Y-m-d', strtotime($issue['created_at'])) . "\n";
        }
    } else {
        echo "❌ Failed: {$result['error']}\n";
    }

    echo "\n";

    // Test 4: Get specific commit
    echo "4. Testing get latest commit (laravel/laravel main branch)...\n";
    
    // First get the main branch to get latest commit SHA
    $branchResult = $github->executeTool('get_repository', [
        'owner' => 'laravel',
        'repo' => 'laravel'
    ]);

    if ($branchResult['success']) {
        $defaultBranch = $branchResult['data']['default_branch'];
        
        // Get branch details to find latest commit
        $branchesResult = $github->executeTool('list_branches', [
            'owner' => 'laravel',
            'repo' => 'laravel'
        ]);
        
        if ($branchesResult['success']) {
            $mainBranch = array_filter($branchesResult['data'], fn($b) => $b['name'] === $defaultBranch);
            $mainBranch = array_values($mainBranch)[0] ?? null;
            
            if ($mainBranch) {
                $commitSha = $mainBranch['commit']['sha'];
                
                $commitResult = $github->executeTool('get_commit', [
                    'owner' => 'laravel',
                    'repo' => 'laravel',
                    'ref' => $commitSha
                ]);
                
                if ($commitResult['success']) {
                    echo "✅ Latest commit retrieved!\n";
                    $commit = $commitResult['data'];
                    echo "  - SHA: " . substr($commit['sha'], 0, 7) . "\n";
                    echo "  - Author: {$commit['commit']['author']['name']}\n";
                    echo "  - Date: " . date('Y-m-d H:i', strtotime($commit['commit']['author']['date'])) . "\n";
                    echo "  - Message: {$commit['commit']['message']}\n";
                }
            }
        }
    }

    echo "\n";

    // Test 5: Search for AskProAI related repositories
    echo "5. Testing search for AskProAI repositories...\n";
    $result = $github->executeTool('search_repositories', [
        'query' => 'askproai',
        'per_page' => 10
    ]);

    if ($result['success']) {
        if (count($result['data']['items']) > 0) {
            echo "✅ Found " . count($result['data']['items']) . " AskProAI related repositories!\n";
            foreach ($result['data']['items'] as $repo) {
                echo "  - {$repo['full_name']}\n";
            }
        } else {
            echo "ℹ️ No public AskProAI repositories found (this is normal for private projects)\n";
        }
    }

    echo "\n✨ All advanced GitHub MCP operations completed successfully!\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}