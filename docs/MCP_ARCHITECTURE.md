# MCP Architecture Documentation

## Overview

The Model Context Protocol (MCP) provides a standardized way to interact with various services and integrations in the AskProAI platform.

## Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────────┐
│                           User Interface                             │
├─────────────────────────┬───────────────────┬──────────────────────┤
│   Filament Dashboard    │   CLI Commands    │   API Endpoints      │
│   - MCP Server Page     │   - mcp shortcuts │   - /api/mcp/*      │
│   - Quick Actions       │   - dev assistant │                      │
└───────────┬─────────────┴──────────┬────────┴──────────────────────┘
            │                        │
            ▼                        ▼
┌───────────────────────────────────────────────────────────────────┐
│                        MCP Orchestrator                            │
│  ┌─────────────┐  ┌──────────────┐  ┌─────────────────────────┐  │
│  │   Router    │  │   Registry   │  │   Health Monitor        │  │
│  │             │  │              │  │                         │  │
│  └─────────────┘  └──────────────┘  └─────────────────────────┘  │
└────────────────────────────┬──────────────────────────────────────┘
                             │
        ┌────────────────────┴────────────────────┐
        ▼                                         ▼
┌───────────────────────┐            ┌───────────────────────┐
│   Internal Servers    │            │   External Servers    │
├───────────────────────┤            ├───────────────────────┤
│ • CalcomMCPServer     │            │ • Sequential Thinking │
│ • RetellMCPServer     │            │ • Memory Bank        │
│ • DatabaseMCPServer   │            │ • Notion             │
│ • AppointmentMCPServer│            │ • Figma              │
│ • CustomerMCPServer   │            │ • GitHub (external)  │
│ • StripeMCPServer     │            └───────────────────────┘
│ • QueueMCPServer      │
│ • WebhookMCPServer    │
│ • GitHubMCPServer     │
│ • NotionMCPServer     │
│ • MemoryBankMCPServer │
└───────────────────────┘
        │
        ▼
┌───────────────────────────────────────────────────────────────────┐
│                      Supporting Services                           │
├─────────────────┬──────────────────┬──────────────────────────────┤
│  Auto Discovery │  Memory Bank     │  Developer Assistant       │
│  Service        │  Automation      │  Service                   │
└─────────────────┴──────────────────┴──────────────────────────────┘
```

## Component Details

### 1. MCP Orchestrator

The central hub that manages all MCP servers.

**Responsibilities:**
- Route requests to appropriate servers
- Manage server lifecycle
- Monitor health status
- Collect metrics
- Handle errors and retries

**Key Classes:**
- `MCPOrchestrator` - Main orchestration logic
- `MCPServiceRegistry` - Server registration
- `MCPHealthCheckService` - Health monitoring
- `MCPMetricsCollector` - Performance metrics

### 2. Internal MCP Servers

Built-in servers that integrate with core platform features.

#### Base Class: `BaseMCPServer`

```php
abstract class BaseMCPServer
{
    abstract public function getTools(): array;
    abstract public function executeTool(string $tool, array $params): array;
    
    public function healthCheck(): array
    {
        return [
            'healthy' => true,
            'status' => 'operational'
        ];
    }
}
```

#### Server Categories

**Business Logic Servers:**
- `AppointmentMCPServer` - Appointment booking and management
- `CustomerMCPServer` - Customer data and history
- `CompanyMCPServer` - Multi-tenant management
- `BranchMCPServer` - Location management

**Integration Servers:**
- `CalcomMCPServer` - Cal.com calendar integration
- `RetellMCPServer` - Retell.ai phone AI integration
- `StripeMCPServer` - Payment processing
- `GitHubMCPServer` - Repository management
- `NotionMCPServer` - Documentation management

**Infrastructure Servers:**
- `DatabaseMCPServer` - Safe database queries
- `QueueMCPServer` - Background job management
- `WebhookMCPServer` - Webhook processing
- `MemoryBankMCPServer` - Persistent context storage

### 3. External MCP Servers

NPM-based servers that extend functionality.

**Management:**
- Started on-demand via `ExternalMCPManager`
- Communicate via stdio protocol
- Health checks via process monitoring

**Available Servers:**
- `sequential_thinking` - Step-by-step problem solving
- `memory_bank` - Enhanced memory operations
- `notion` - Advanced Notion features
- `figma` - Design system integration

### 4. Supporting Services

#### MCPAutoDiscoveryService

Automatically finds the best MCP server for a task.

```php
$discovery = app(MCPAutoDiscoveryService::class);
$result = $discovery->discoverBestServer("book appointment tomorrow");
// Returns: ['server' => 'appointment', 'tool' => 'create_appointment', ...]
```

#### MemoryBankAutomationService

Manages persistent context and session data.

```php
$memory = app(MemoryBankAutomationService::class);
$memory->remember('session_key', $data, 'category', ['tags']);
$results = $memory->search('query', 'category');
```

#### DeveloperAssistantService

AI-powered code generation and analysis.

```php
$assistant = app(DeveloperAssistantService::class);
$code = $assistant->generateCode("create payment service");
$analysis = $assistant->analyzeCode($filePath);
```

## Request Flow

### 1. CLI Command Flow

```
User → Artisan Command → MCPShortcutCommand → MCPOrchestrator → MCP Server → Response
```

### 2. Dashboard Flow

```
User → Filament Page → Livewire Component → MCPOrchestrator → MCP Server → UI Update
```

### 3. API Flow

```
External App → API Endpoint → Controller → MCPOrchestrator → MCP Server → JSON Response
```

## Tool Definition Standard

Every MCP server must define its tools with this structure:

```php
public function getTools(): array
{
    return [
        [
            'name' => 'tool_name',
            'description' => 'What this tool does',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'param1' => [
                        'type' => 'string',
                        'description' => 'Parameter description',
                        'required' => true
                    ]
                ],
                'required' => ['param1']
            ]
        ]
    ];
}
```

## Error Handling

### Error Response Format

```php
[
    'success' => false,
    'error' => 'Error message',
    'code' => 'ERROR_CODE',
    'details' => [...] // Optional
]
```

### Error Codes

- `INVALID_PARAMS` - Missing or invalid parameters
- `NOT_FOUND` - Resource not found
- `UNAUTHORIZED` - Permission denied
- `RATE_LIMITED` - Too many requests
- `SERVER_ERROR` - Internal server error

## Performance Considerations

### Caching Strategy

```php
// In MCP Server
protected function getCachedData(string $key, callable $callback, int $ttl = 300)
{
    return Cache::remember("mcp_{$this->name}_{$key}", $ttl, $callback);
}
```

### Async Operations

For long-running operations:

```php
public function executeTool(string $tool, array $params): array
{
    if ($tool === 'long_operation') {
        $jobId = Str::uuid();
        LongOperationJob::dispatch($params)->onQueue('mcp');
        
        return [
            'success' => true,
            'job_id' => $jobId,
            'status' => 'queued'
        ];
    }
}
```

## Security

### Parameter Validation

Always validate input parameters:

```php
protected function validateParams(array $params, array $rules): array
{
    $validator = Validator::make($params, $rules);
    
    if ($validator->fails()) {
        throw new MCPValidationException($validator->errors());
    }
    
    return $validator->validated();
}
```

### Permission Checks

```php
protected function checkPermission(string $action): void
{
    if (!auth()->user()->can("mcp.{$this->name}.{$action}")) {
        throw new MCPUnauthorizedException();
    }
}
```

## Testing MCP Servers

### Unit Test Example

```php
class RetellMCPServerTest extends TestCase
{
    protected RetellMCPServer $server;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->server = app(RetellMCPServer::class);
    }
    
    public function test_fetch_calls_returns_data()
    {
        $result = $this->server->executeTool('fetch_calls', [
            'limit' => 10
        ]);
        
        $this->assertTrue($result['success']);
        $this->assertIsArray($result['data']);
    }
}
```

### Integration Test Example

```php
public function test_mcp_orchestrator_routes_correctly()
{
    $orchestrator = app(MCPOrchestrator::class);
    
    $result = $orchestrator->execute('retell', 'fetch_calls', [
        'limit' => 5
    ]);
    
    $this->assertTrue($result['success']);
}
```

## Monitoring & Metrics

### Key Metrics

- **Request Rate**: Requests per minute per server
- **Error Rate**: Failed requests percentage
- **Response Time**: Average execution time
- **Queue Length**: Pending async operations

### Health Check Endpoint

```
GET /api/mcp/health

Response:
{
    "status": "healthy",
    "servers": {
        "calcom": {"healthy": true, "response_time": 45},
        "retell": {"healthy": true, "response_time": 123},
        ...
    }
}
```

## Best Practices

1. **Keep Tools Focused**: One tool = one specific action
2. **Use Descriptive Names**: Tool names should be self-explanatory
3. **Validate Early**: Check parameters before processing
4. **Handle Errors Gracefully**: Always return structured errors
5. **Cache Wisely**: Cache read operations, not writes
6. **Log Important Events**: Use structured logging
7. **Monitor Performance**: Track execution times
8. **Document Tools**: Include examples in descriptions
9. **Version Your API**: Use versioned tool names if needed
10. **Test Thoroughly**: Unit and integration tests for all tools

## Future Enhancements

- GraphQL support for complex queries
- WebSocket support for real-time updates
- Plugin system for third-party MCP servers
- Visual flow builder for complex operations
- Enhanced security with OAuth2
- Distributed tracing for debugging