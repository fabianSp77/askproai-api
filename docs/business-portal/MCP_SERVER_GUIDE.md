# MCP Server Integration Guide for Business Portal

## Overview

MCP (Model Context Protocol) servers provide a standardized way to integrate external services and perform complex operations in the Business Portal. They act as intelligent middleware that can understand natural language commands and execute appropriate actions.

## Architecture

```
┌─────────────────────────────────────────────────────────┐
│                  Business Portal                         │
│  ┌─────────────────────────────────────────────────┐   │
│  │              MCP Discovery Service               │   │
│  │  Finds the best MCP server for each task       │   │
│  └─────────────────────────────────────────────────┘   │
├─────────────────────────────────────────────────────────┤
│                    MCP Servers                          │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐           │
│  │Database  │  │ Retell   │  │ Cal.com  │  ...      │
│  │   MCP    │  │   MCP    │  │   MCP    │           │
│  └──────────┘  └──────────┘  └──────────┘           │
├─────────────────────────────────────────────────────────┤
│                 External Services                       │
└─────────────────────────────────────────────────────────┘
```

## Available MCP Servers

### Core Business MCP Servers

#### 1. DatabaseMCP
**Purpose**: Direct database operations with natural language
```php
// Example usage
$result = $this->executeMCPTask('find customers who haven\'t booked in 30 days');

// Available operations
- Complex queries across multiple tables
- Data aggregation and reporting
- Bulk updates with safety checks
- Schema exploration
```

#### 2. RetellMCP
**Purpose**: AI phone system integration
```php
// Example usage
$agent = $this->executeMCPTask('create new AI agent for dental practice');

// Available operations
- Agent configuration
- Call analytics
- Transcript analysis
- Voice customization
```

#### 3. CalcomMCP
**Purpose**: Calendar and scheduling operations
```php
// Example usage
$slots = $this->executeMCPTask('find available slots next week for Dr. Smith');

// Available operations
- Availability checking
- Appointment booking
- Schedule optimization
- Event type management
```

#### 4. CustomerMCP
**Purpose**: Advanced customer management
```php
// Example usage
$vips = $this->executeMCPTask('identify VIP customers based on booking frequency');

// Available operations
- Customer segmentation
- Journey tracking
- Duplicate detection
- Relationship mapping
```

### Goal & Analytics MCP Servers

#### 5. GoalMCP
**Purpose**: Strategic goal management
```php
// Example usage
$progress = $this->executeMCPTask('calculate Q1 revenue goal progress');

// Available operations
- Goal creation from templates
- KPI calculation
- Trend analysis
- Achievement tracking
```

#### 6. AnalyticsMCP
**Purpose**: Business intelligence and reporting
```php
// Example usage
$report = $this->executeMCPTask('generate monthly performance report');

// Available operations
- Custom report generation
- Predictive analytics
- Anomaly detection
- Data visualization
```

### Operational MCP Servers

#### 7. NotificationMCP
**Purpose**: Multi-channel notifications
```php
// Example usage
$this->executeMCPTask('notify customers about tomorrow\'s appointments');

// Available operations
- Email campaigns
- SMS notifications
- Push notifications
- WhatsApp messages (when available)
```

#### 8. AuditMCP
**Purpose**: Compliance and security logging
```php
// Example usage
$logs = $this->executeMCPTask('show all admin actions from last week');

// Available operations
- Audit trail queries
- Compliance reports
- Security analysis
- Activity monitoring
```

## Implementation in Business Portal

### Using MCP Servers in Services

```php
namespace App\Services\Portal;

use App\Traits\UsesMCPServers;

class PortalDashboardService
{
    use UsesMCPServers;
    
    public function getInsights($companyId)
    {
        // Use natural language to get complex data
        $insights = [];
        
        // Get customer insights
        $insights['at_risk'] = $this->executeMCPTask(
            'find customers at risk of churning',
            ['company_id' => $companyId]
        );
        
        // Get revenue trends
        $insights['revenue_trend'] = $this->executeMCPTask(
            'analyze revenue trend for last 6 months',
            ['company_id' => $companyId]
        );
        
        // Get staff performance
        $insights['top_performers'] = $this->executeMCPTask(
            'identify top performing staff by conversion rate',
            ['company_id' => $companyId, 'limit' => 5]
        );
        
        return $insights;
    }
}
```

### Using MCP in API Controllers

```php
namespace App\Http\Controllers\Api\V2\Portal;

use App\Traits\UsesMCPServers;

class SmartSearchController extends Controller
{
    use UsesMCPServers;
    
    public function search(Request $request)
    {
        $query = $request->input('q');
        $context = $request->input('context', 'all');
        
        // MCP automatically determines the best server and action
        $results = $this->executeMCPTask($query, [
            'company_id' => auth()->user()->company_id,
            'context' => $context,
            'limit' => 20
        ]);
        
        return response()->json([
            'results' => $results,
            'query' => $query,
            'mcp_server_used' => $this->getLastUsedMCPServer()
        ]);
    }
}
```

### Frontend Integration

```javascript
// React component using MCP-powered search
import { useState } from 'react';
import { useApi } from '../hooks/useApi';

function SmartSearch() {
    const [query, setQuery] = useState('');
    const [results, setResults] = useState(null);
    const api = useApi();
    
    const handleSearch = async () => {
        try {
            const response = await api.post('/portal/smart-search', {
                q: query,
                context: 'business_insights'
            });
            setResults(response.data.results);
        } catch (error) {
            console.error('Search failed:', error);
        }
    };
    
    return (
        <div className="smart-search">
            <input
                type="text"
                value={query}
                onChange={(e) => setQuery(e.target.value)}
                placeholder="Ask anything about your business..."
                onKeyPress={(e) => e.key === 'Enter' && handleSearch()}
            />
            
            {results && (
                <div className="search-results">
                    {/* Render results based on type */}
                </div>
            )}
        </div>
    );
}
```

## MCP Discovery Service

The MCP Discovery Service automatically finds the best MCP server for a given task:

```php
use App\Services\MCPAutoDiscoveryService;

class BusinessInsightsService
{
    protected $discovery;
    
    public function __construct(MCPAutoDiscoveryService $discovery)
    {
        $this->discovery = $discovery;
    }
    
    public function getInsight($question)
    {
        // Discovery service analyzes the question and routes to appropriate MCP
        return $this->discovery->executeTask($question, [
            'user_id' => auth()->id(),
            'company_id' => auth()->user()->company_id
        ]);
    }
}
```

### How Discovery Works

1. **Natural Language Processing**: Analyzes the intent of the request
2. **Context Analysis**: Considers user permissions and data scope
3. **Server Selection**: Chooses the most appropriate MCP server
4. **Execution**: Runs the task with proper parameters
5. **Result Formatting**: Returns standardized response

## Creating Custom MCP Servers

### Basic Structure

```php
namespace App\Services\MCP;

use App\Services\MCP\Base\BaseMCPServer;

class CustomBusinessMCP extends BaseMCPServer
{
    protected string $name = 'custom_business';
    protected string $description = 'Custom business logic operations';
    
    public function getTools(): array
    {
        return [
            [
                'name' => 'calculate_custom_metric',
                'description' => 'Calculate custom business metrics',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'metric_type' => ['type' => 'string'],
                        'date_range' => ['type' => 'object'],
                        'filters' => ['type' => 'object']
                    ],
                    'required' => ['metric_type']
                ]
            ]
        ];
    }
    
    public function executeTool(string $toolName, array $args): array
    {
        return match($toolName) {
            'calculate_custom_metric' => $this->calculateMetric($args),
            default => ['error' => 'Unknown tool']
        };
    }
    
    protected function calculateMetric(array $args): array
    {
        // Implementation
        return [
            'metric' => $args['metric_type'],
            'value' => 42,
            'trend' => 'increasing'
        ];
    }
}
```

### Registering Custom MCP

```php
// In AppServiceProvider
public function register()
{
    $this->app->singleton('mcp.custom_business', function ($app) {
        return new CustomBusinessMCP();
    });
}

// In config/mcp.php
'servers' => [
    // ... existing servers
    'custom_business' => [
        'class' => \App\Services\MCP\CustomBusinessMCP::class,
        'enabled' => env('MCP_CUSTOM_BUSINESS_ENABLED', true),
    ]
]
```

## Best Practices

### 1. Natural Language Queries

Write queries as if explaining to a colleague:
```php
// Good
$this->executeMCPTask('find customers who spent over €1000 last month');

// Less effective
$this->executeMCPTask('customers > 1000 EUR previous month');
```

### 2. Provide Context

Always include relevant context:
```php
$result = $this->executeMCPTask('analyze appointment patterns', [
    'branch_id' => $branchId,
    'service_type' => 'consultation',
    'period' => 'last_quarter'
]);
```

### 3. Error Handling

```php
try {
    $result = $this->executeMCPTask($query);
} catch (MCPServerException $e) {
    // Handle MCP-specific errors
    Log::error('MCP execution failed', [
        'server' => $e->getServer(),
        'query' => $query,
        'error' => $e->getMessage()
    ]);
} catch (\Exception $e) {
    // Handle general errors
}
```

### 4. Performance Considerations

```php
// Cache MCP results when appropriate
$cacheKey = 'mcp_result_' . md5($query . serialize($params));
$result = Cache::remember($cacheKey, 300, function () use ($query, $params) {
    return $this->executeMCPTask($query, $params);
});
```

### 5. Security

```php
// Always validate and sanitize inputs
$query = Str::limit($request->input('query'), 500);
$params = $request->validate([
    'company_id' => 'required|exists:companies,id',
    'date_from' => 'date',
    'date_to' => 'date|after:date_from'
]);

$result = $this->executeMCPTask($query, $params);
```

## Debugging MCP Operations

### Enable Debug Mode

```env
MCP_DEBUG=true
MCP_LOG_QUERIES=true
```

### Debug Commands

```bash
# Test MCP server
php artisan mcp:test DatabaseMCP "find all appointments today"

# Debug discovery
php artisan mcp:discover "calculate monthly revenue" --debug

# View MCP logs
tail -f storage/logs/mcp-debug.log
```

### Monitoring

```php
// In your monitoring dashboard
$mcpStats = [
    'total_executions' => MCPExecution::count(),
    'success_rate' => MCPExecution::successful()->percentage(),
    'avg_execution_time' => MCPExecution::average('duration_ms'),
    'popular_servers' => MCPExecution::popularServers(5)
];
```

## Common Use Cases

### 1. Smart Dashboard Widgets

```php
// Dashboard widget that adapts based on business type
public function getSmartWidgets($company)
{
    $widgets = [];
    
    // Let MCP determine relevant metrics
    $widgets['key_metric'] = $this->executeMCPTask(
        'what is the most important metric for this business?',
        ['company_id' => $company->id]
    );
    
    $widgets['action_items'] = $this->executeMCPTask(
        'what actions should be taken to improve performance?',
        ['company_id' => $company->id]
    );
    
    return $widgets;
}
```

### 2. Automated Reporting

```php
// Generate reports using natural language
public function generateReport($type, $period)
{
    $report = $this->executeMCPTask(
        "generate {$type} report for {$period}",
        [
            'include_charts' => true,
            'format' => 'pdf',
            'language' => auth()->user()->preferred_language
        ]
    );
    
    return $report;
}
```

### 3. Predictive Analytics

```php
// Predict future trends
public function predictTrends($metric)
{
    return $this->executeMCPTask(
        "predict {$metric} for next 3 months based on historical data",
        [
            'confidence_level' => 0.95,
            'include_factors' => true
        ]
    );
}
```

## Troubleshooting

### Common Issues

1. **MCP Server Not Found**
   ```bash
   php artisan mcp:list
   php artisan mcp:health
   ```

2. **Timeout Errors**
   ```php
   // Increase timeout for complex operations
   $this->executeMCPTask($query, $params, ['timeout' => 30]);
   ```

3. **Permission Errors**
   ```php
   // Ensure user has permission
   if (!auth()->user()->can('use-mcp')) {
       throw new UnauthorizedException();
   }
   ```

### Performance Optimization

1. **Batch Operations**
   ```php
   $results = $this->executeMCPBatch([
       'query1' => 'find top customers',
       'query2' => 'calculate churn rate',
       'query3' => 'predict next month revenue'
   ]);
   ```

2. **Async Execution**
   ```php
   MCPJob::dispatch($query, $params)->onQueue('mcp');
   ```

## Future Enhancements

### Planned Features

1. **Visual MCP Builder** - Drag-and-drop interface for creating MCP workflows
2. **MCP Marketplace** - Share and discover custom MCP servers
3. **AI Training** - Train MCP servers on your business data
4. **Multi-language Support** - Query in any language
5. **Voice Integration** - Use voice commands to interact with MCP

### Upcoming MCP Servers

- **MarketingMCP** - Campaign management and automation
- **InventoryMCP** - Stock and resource management
- **HRMCP** - Human resources and scheduling
- **ComplianceMCP** - Regulatory compliance checking
- **IntegrationMCP** - Third-party service integration

---

*For more information, see the [main documentation](./BUSINESS_PORTAL_COMPLETE_DOCUMENTATION.md)*