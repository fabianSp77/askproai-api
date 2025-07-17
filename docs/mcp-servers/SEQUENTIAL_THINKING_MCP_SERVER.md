# Sequential Thinking MCP Server Documentation

## Overview

The Sequential Thinking MCP Server enhances AskProAI with structured problem-solving capabilities. It breaks down complex tasks into logical steps, making it ideal for architecture design, large-scale refactoring, debugging, and strategic planning.

## Installation Status

✅ **INSTALLED** - The Sequential Thinking MCP Server has been successfully installed and configured.

## Key Features

- **Structured Reasoning**: Guides methodical problem-solving approaches
- **Step-by-Step Analysis**: Breaks complex problems into manageable components
- **Problem Decomposition**: Identifies sub-problems and dependencies
- **Logical Planning**: Creates detailed action plans with timelines
- **Complexity Management**: Handles multi-phase projects with clear milestones
- **Decision Trees**: Evaluates options systematically with scoring

## Why It's Essential

1. **Complex Task Management**: Tackles multi-phase planning for system design or debugging
2. **Scalability**: Supports large codebases with clear, step-by-step logic
3. **Risk Mitigation**: Identifies potential issues before implementation
4. **Team Alignment**: Creates clear documentation for collaborative work

## Available Tools

### 1. analyze_problem
Break down a complex problem into manageable steps.

```php
$result = $thinking->executeTool('analyze_problem', [
    'problem' => 'Migrate monolithic application to microservices',
    'context' => 'Application has 200+ models, 10k requests/minute',
    'depth' => 4  // 1-5, higher = more detail
]);
```

**Output includes:**
- Problem breakdown with numbered steps
- Key challenges identification
- Prerequisites list
- Success criteria

### 2. create_action_plan
Generate a detailed action plan with sequential steps.

```php
$result = $thinking->executeTool('create_action_plan', [
    'goal' => 'Implement comprehensive CI/CD pipeline',
    'constraints' => [
        'Zero-downtime deployments required',
        'Must integrate with GitHub'
    ],
    'timeline' => '4 weeks'
]);
```

**Output includes:**
- Phased approach with durations
- Specific tasks per phase
- Milestone schedule
- Dependencies mapping

### 3. evaluate_options
Systematically evaluate multiple options or solutions.

```php
$result = $thinking->executeTool('evaluate_options', [
    'question' => 'Which caching solution for Laravel API?',
    'options' => ['Redis', 'Memcached', 'Varnish', 'DynamoDB'],
    'criteria' => ['performance', 'scalability', 'cost', 'complexity']
]);
```

**Output includes:**
- Scored evaluation matrix
- Pros/cons for each option
- Final recommendation
- Reasoning explanation

### 4. debug_systematically
Create a systematic debugging approach for an issue.

```php
$result = $thinking->executeTool('debug_systematically', [
    'issue' => 'API response times increased 10x after deployment',
    'symptoms' => [
        'Slow database queries',
        'High CPU usage',
        'All endpoints affected'
    ],
    'system' => 'Laravel API with MySQL'
]);
```

**Output includes:**
- Step-by-step debugging plan
- Tools needed for each step
- Isolation techniques
- Prevention strategies

### 5. refactor_strategy
Plan a systematic refactoring approach.

```php
$result = $thinking->executeTool('refactor_strategy', [
    'code_area' => 'Authentication system',
    'goals' => [
        'Implement OAuth2',
        'Support multiple providers',
        'Maintain backwards compatibility'
    ],
    'constraints' => ['Cannot break existing APIs']
]);
```

**Output includes:**
- Phased refactoring approach
- Best practices checklist
- Risk mitigation strategies
- Testing requirements

## Real-World Use Cases

### Use Case 1: Microservices Migration

When planning to break down a monolithic Laravel application:

```php
$thinking = app(SequentialThinkingMCPServer::class);

// 1. Analyze the migration challenge
$analysis = $thinking->executeTool('analyze_problem', [
    'problem' => 'Extract user management from monolith to microservice',
    'context' => 'Current system handles authentication, profiles, permissions, and audit logs',
    'depth' => 5
]);

// 2. Create implementation plan
$plan = $thinking->executeTool('create_action_plan', [
    'goal' => 'Deploy user microservice with zero downtime',
    'constraints' => [
        'Maintain session compatibility',
        'Gradual migration required',
        'Must support rollback'
    ],
    'timeline' => '6 weeks'
]);

// Use the outputs to guide your team
foreach ($plan['data']['phases'] as $phase) {
    echo "Phase {$phase['phase']}: {$phase['name']}\n";
    // Create Jira tickets for each task
    foreach ($phase['tasks'] as $task) {
        createJiraTicket($task, $phase['duration']);
    }
}
```

### Use Case 2: Performance Optimization

When dealing with performance issues:

```php
// 1. Systematic debugging
$debug = $thinking->executeTool('debug_systematically', [
    'issue' => 'Homepage load time increased from 500ms to 5s',
    'symptoms' => [
        'Database queries increased from 10 to 150',
        'Memory usage spiked',
        'Cache hit rate dropped to 10%'
    ],
    'system' => 'Laravel with Redis cache and MySQL'
]);

// 2. Evaluate optimization options
$options = $thinking->executeTool('evaluate_options', [
    'question' => 'How to optimize database query performance?',
    'options' => [
        'Implement query result caching',
        'Add database read replicas',
        'Optimize queries with indexes',
        'Implement GraphQL with DataLoader'
    ],
    'criteria' => ['implementation_time', 'performance_gain', 'maintenance', 'cost']
]);
```

### Use Case 3: Architecture Decisions

For major architectural decisions:

```php
// Evaluate different architectural patterns
$evaluation = $thinking->executeTool('evaluate_options', [
    'question' => 'Which event-driven architecture for our system?',
    'options' => [
        'Laravel Events with Redis',
        'RabbitMQ with dedicated workers',
        'AWS EventBridge with Lambda',
        'Apache Kafka'
    ],
    'criteria' => [
        'scalability',
        'reliability',
        'operational_complexity',
        'cost',
        'team_expertise'
    ]
]);

// Get detailed implementation strategy for chosen option
$strategy = $thinking->executeTool('create_action_plan', [
    'goal' => 'Implement ' . $evaluation['data']['recommendation'],
    'constraints' => ['Gradual rollout required', 'Must monitor performance'],
    'timeline' => '8 weeks'
]);
```

### Use Case 4: Legacy Code Refactoring

For refactoring legacy systems:

```php
// Plan authentication system overhaul
$refactor = $thinking->executeTool('refactor_strategy', [
    'code_area' => 'Legacy authentication system (10+ years old)',
    'goals' => [
        'Implement modern security standards',
        'Add multi-factor authentication',
        'Support SSO providers',
        'Improve performance'
    ],
    'constraints' => [
        'Cannot break existing sessions',
        '50k active users must migrate seamlessly',
        'Regulatory compliance required'
    ]
]);

// Break down the refactoring into safe steps
foreach ($refactor['data']['approach'] as $phase) {
    echo "\n{$phase['phase']}:\n";
    foreach ($phase['steps'] as $step) {
        echo "- {$step}\n";
        // Create feature flags for gradual rollout
        createFeatureFlag("refactor_{$phase['phase']}", $step);
    }
}
```

## Integration with AskProAI Workflow

### Combine with Other MCP Servers

```php
// 1. Use Sequential Thinking to plan
$plan = $thinking->executeTool('analyze_problem', [
    'problem' => 'Integrate new payment provider API'
]);

// 2. Use Apidog to fetch API specs
$apidog = app(ApidogMCPServer::class);
$spec = $apidog->executeTool('fetch_api_spec', [
    'source' => 'https://api.paymentprovider.com/openapi.json'
]);

// 3. Use GitHub to track implementation
$github = app(GitHubMCPServer::class);
foreach ($plan['data']['breakdown'] as $step) {
    $github->executeTool('create_issue', [
        'owner' => 'your-org',
        'repo' => 'your-repo',
        'title' => $step['title'],
        'body' => "## Step {$step['step']}\n\n{$step['description']}\n\n### Tasks:\n" . 
                  implode("\n", array_map(fn($t) => "- [ ] {$t}", $step['subtasks']))
    ]);
}
```

## Best Practices

1. **Start with High-Level Analysis**: Use depth 3-4 for initial problem analysis
2. **Iterate on Plans**: Refine action plans based on team feedback
3. **Document Decisions**: Save evaluation results for future reference
4. **Track Progress**: Use generated milestones for project management
5. **Combine Tools**: Use multiple analysis tools for comprehensive planning

## Performance Considerations

- The Sequential Thinking server runs on-demand via npx
- No persistent process required
- Minimal resource usage
- Results can be cached for repeated analysis

## Testing

Run the test script to verify functionality:

```bash
php test-sequential-thinking-mcp.php
```

## Troubleshooting

### Server Not Responding
- Check npm installation: `npm list -g @modelcontextprotocol/server-sequential-thinking`
- Verify Node.js version: `node --version` (should be 18+)

### Empty Results
- Ensure input parameters are properly formatted
- Check that required fields are provided
- Review logs: `tail -f storage/logs/laravel.log`

## Future Enhancements

- Integration with project management tools (Jira, Trello)
- Machine learning for pattern recognition
- Historical analysis comparison
- Team collaboration features
- Real-time progress tracking