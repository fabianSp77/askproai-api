# Sequential Thinking MCP Server Documentation

## Overview
The Sequential Thinking MCP server provides structured problem-solving capabilities through dynamic and reflective thinking processes. It breaks down complex problems into manageable steps, allows for revision and refinement, and generates alternative solutions.

## Installation
```bash
# Already installed in:
/var/www/api-gateway/mcp-external/sequential-thinking/

# NPM Package:
@modelcontextprotocol/server-sequential-thinking
```

## Configuration
Located in `/var/www/api-gateway/config/mcp-servers.php`:
```php
'sequential_thinking' => [
    'enabled' => true,
    'type' => 'external',
    'path' => base_path('mcp-external/sequential-thinking'),
    'description' => 'Dynamic and reflective problem-solving'
]
```

## Service Usage

### 1. Problem Solving
```php
use App\Services\MCP\SequentialThinkingMCPService;

$service = app(SequentialThinkingMCPService::class);

$result = $service->solveComplexProblem(
    problem: "How to optimize appointment scheduling?",
    context: "Hair salon with 3 stylists, 50 customers/day",
    maxSteps: 10
);

// Result structure:
// [
//     'success' => true,
//     'solution' => "Detailed solution...",
//     'steps' => [...],
//     'alternatives' => [...],
//     'confidence' => 85
// ]
```

### 2. Task Planning
```php
$plan = $service->planTask(
    task: "Implement new payment system",
    constraints: ['Must support Stripe', 'German regulations'],
    resources: ['Laravel', 'Existing API']
);
```

### 3. System Analysis
```php
$analysis = $service->analyzeSystem(
    systemDescription: "Multi-tenant appointment booking platform",
    focusAreas: ['Performance', 'Security', 'Scalability']
);
```

### 4. Hypothesis Generation
```php
$hypotheses = $service->generateHypotheses(
    scenario: "Why customers prefer phone bookings",
    maxHypotheses: 5
);
```

## Integration Examples

### With Retell.ai Webhook Processing
```php
// In webhook handler
$problem = "Customer wants appointment but all slots seem full";
$context = "Salon schedule: " . json_encode($schedule);

$solution = $service->solveComplexProblem($problem, $context);

// Use solution to guide AI response
$retellResponse = $this->formatSolutionForRetell($solution);
```

### With Admin Panel Decision Support
```php
// In Filament Resource
use App\Services\MCP\SequentialThinkingMCPService;

public function optimizeSchedule()
{
    $service = app(SequentialThinkingMCPService::class);
    
    $optimization = $service->planTask(
        "Optimize staff schedule for next week",
        ["Minimum 2 stylists always present", "Respect working hours"],
        ["Historical booking data", "Staff preferences"]
    );
    
    return $optimization['solution'];
}
```

## Capabilities

1. **Problem Decomposition**: Breaks complex problems into manageable components
2. **Thought Revision**: Dynamically revises and refines solution approaches
3. **Alternative Reasoning**: Generates multiple solution paths
4. **Hypothesis Generation**: Creates and validates hypotheses for scenarios

## Testing
```bash
# Run test script
php test-sequential-thinking-mcp.php

# Expected output:
# ✅ Connected successfully
# ✅ Problem solved successfully
# ✅ Task planning completed
# ✅ Hypotheses generated
```

## Use Cases in AskProAI

1. **Appointment Optimization**: Finding best scheduling strategies
2. **Customer Service**: Generating response strategies for complex requests
3. **System Architecture**: Planning new feature implementations
4. **Business Analysis**: Analyzing patterns and generating insights
5. **Debugging**: Systematic approach to solving technical issues

## Troubleshooting

### Service Not Available
```php
// Check if service is registered
dd(app()->bound(SequentialThinkingMCPService::class));

// Clear cache
php artisan optimize:clear
```

### No Results Returned
- Check logs: `storage/logs/laravel.log`
- Verify input format is correct
- Ensure context is properly formatted

## Future Enhancements
- [ ] Integrate with actual MCP stdio protocol
- [ ] Add persistent memory for learning
- [ ] Connect with other MCP servers for enhanced capabilities
- [ ] Add webhook for async processing of complex problems

## Related Documentation
- [MCP Servers Configuration](../config/MCP_SERVERS.md)
- [Problem Solving Patterns](../patterns/PROBLEM_SOLVING.md)
- [AI Integration Guide](../integrations/AI_INTEGRATION.md)