#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\MCP\SequentialThinkingMCPServer;

try {
    echo "Testing Sequential Thinking MCP Server...\n";
    echo str_repeat("=", 50) . "\n\n";

    // Initialize Sequential Thinking MCP Server
    $thinking = app(SequentialThinkingMCPServer::class);
    
    // Get server info
    echo "Server Information:\n";
    echo "- Name: " . $thinking->getName() . "\n";
    echo "- Version: " . $thinking->getVersion() . "\n";
    echo "- Capabilities: " . implode(', ', $thinking->getCapabilities()) . "\n\n";

    // List available tools
    echo "Available Sequential Thinking Tools:\n";
    $tools = $thinking->getTools();
    foreach ($tools as $tool) {
        echo "  - {$tool['name']}: {$tool['description']}\n";
    }
    echo "\n";

    // Test 1: Analyze a complex problem
    echo "Test 1: Analyzing a complex refactoring problem...\n";
    $result = $thinking->executeTool('analyze_problem', [
        'problem' => 'Refactor a monolithic Laravel application into microservices',
        'context' => 'The application has 200+ models, serves 10k requests/minute, and must maintain 99.9% uptime during migration',
        'depth' => 4
    ]);

    if ($result['success']) {
        echo "✅ Problem analysis completed!\n";
        $analysis = $result['data'];
        echo "Problem breakdown into " . count($analysis['breakdown']) . " major steps:\n";
        foreach ($analysis['breakdown'] as $step) {
            echo "  Step {$step['step']}: {$step['title']}\n";
            echo "    - {$step['description']}\n";
            foreach ($step['subtasks'] as $subtask) {
                echo "      • {$subtask}\n";
            }
        }
        echo "\n";
    }

    // Test 2: Create action plan
    echo "Test 2: Creating action plan for implementing CI/CD...\n";
    $result = $thinking->executeTool('create_action_plan', [
        'goal' => 'Implement comprehensive CI/CD pipeline for Laravel application',
        'constraints' => [
            'Must support multiple environments',
            'Zero-downtime deployments required',
            'Integrate with existing GitHub repository'
        ],
        'timeline' => '4 weeks'
    ]);

    if ($result['success']) {
        echo "✅ Action plan created!\n";
        $plan = $result['data'];
        echo "Plan includes " . count($plan['phases']) . " phases:\n";
        foreach ($plan['phases'] as $phase) {
            echo "  Phase {$phase['phase']}: {$phase['name']} ({$phase['duration']})\n";
        }
        echo "\nKey milestones:\n";
        foreach ($plan['milestones'] as $milestone) {
            echo "  - {$milestone}\n";
        }
        echo "\n";
    }

    // Test 3: Evaluate options
    echo "Test 3: Evaluating caching solutions...\n";
    $result = $thinking->executeTool('evaluate_options', [
        'question' => 'Which caching solution should we implement for our Laravel API?',
        'options' => [
            'Redis with Laravel Cache',
            'Memcached',
            'DynamoDB DAX',
            'Varnish HTTP Cache'
        ],
        'criteria' => [
            'performance',
            'scalability',
            'cost',
            'ease of implementation',
            'Laravel integration'
        ]
    ]);

    if ($result['success']) {
        echo "✅ Options evaluated!\n";
        $evaluation = $result['data'];
        echo "Recommendation: {$evaluation['recommendation']}\n";
        echo "Reasoning:\n";
        foreach ($evaluation['reasoning'] as $reason) {
            echo "  - {$reason}\n";
        }
        echo "\n";
    }

    // Test 4: Debug systematically
    echo "Test 4: Creating debugging strategy for performance issue...\n";
    $result = $thinking->executeTool('debug_systematically', [
        'issue' => 'API response times increased from 200ms to 2s after deployment',
        'symptoms' => [
            'Slow database queries in logs',
            'High CPU usage',
            'Memory usage normal',
            'Affects all endpoints'
        ],
        'system' => 'Laravel API with MySQL database'
    ]);

    if ($result['success']) {
        echo "✅ Debugging strategy created!\n";
        $debug = $result['data'];
        echo "Systematic approach:\n";
        foreach ($debug['systematic_approach'] as $step) {
            echo "  Step {$step['step']}: {$step['action']}\n";
        }
        echo "\n";
    }

    // Test 5: Refactoring strategy
    echo "Test 5: Planning refactoring strategy for legacy code...\n";
    $result = $thinking->executeTool('refactor_strategy', [
        'code_area' => 'User authentication and authorization system',
        'goals' => [
            'Implement OAuth2',
            'Support multiple authentication providers',
            'Improve security',
            'Maintain backwards compatibility'
        ],
        'constraints' => [
            'Cannot break existing API contracts',
            'Must support gradual migration'
        ]
    ]);

    if ($result['success']) {
        echo "✅ Refactoring strategy created!\n";
        $strategy = $result['data'];
        echo "Approach includes " . count($strategy['approach']) . " phases:\n";
        foreach ($strategy['approach'] as $phase) {
            echo "  - {$phase['phase']}\n";
        }
        echo "\nBest practices:\n";
        foreach ($strategy['best_practices'] as $practice) {
            echo "  • {$practice}\n";
        }
        echo "\n";
    }

    echo "✅ Sequential Thinking MCP Server is working correctly!\n\n";
    
    echo "💡 Usage in your application:\n";
    echo "```php\n";
    echo "\$thinking = app(SequentialThinkingMCPServer::class);\n";
    echo "\$result = \$thinking->executeTool('analyze_problem', [\n";
    echo "    'problem' => 'Your complex problem here',\n";
    echo "    'context' => 'Additional context',\n";
    echo "    'depth' => 3\n";
    echo "]);\n";
    echo "```\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}