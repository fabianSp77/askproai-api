# MCP-First Architecture Visualization

## System Architecture Diagram

```mermaid
graph TB
    subgraph "User Interface Layer"
        UI[Filament UI Components]
        API[REST API Endpoints]
    end
    
    subgraph "MCP Gateway Layer"
        GW[MCP Gateway Controller<br/>JSON-RPC 2.0]
        SD[Service Discovery]
        AUTH[MCP Auth Middleware]
    end
    
    subgraph "MCP Servers - Configuration"
        RC[RetellConfigurationMCPServer<br/>retell.config.*]
        RF[RetellCustomFunctionMCPServer<br/>retell.function.*]
        AM[AppointmentManagementMCPServer<br/>appointments.*]
    end
    
    subgraph "MCP Servers - Core"
        W[WebhookMCPServer<br/>webhook.*]
        R[RetellMCPServer<br/>retell.*]
        C[CalcomMCPServer<br/>calcom.*]
        D[DatabaseMCPServer<br/>database.*]
        Q[QueueMCPServer<br/>queue.*]
    end
    
    subgraph "External Services"
        RAPI[Retell.ai API]
        CAPI[Cal.com API v2]
    end
    
    subgraph "Infrastructure"
        DB[(MySQL Database)]
        REDIS[(Redis Cache)]
        HZ[Laravel Horizon]
    end
    
    %% UI Layer connections
    UI -->|MCP Request| GW
    API -->|MCP Request| GW
    
    %% Gateway routing
    GW --> AUTH
    AUTH --> SD
    SD --> RC
    SD --> RF
    SD --> AM
    SD --> W
    SD --> R
    SD --> C
    SD --> D
    SD --> Q
    
    %% MCP Server interactions
    W --> R
    W --> C
    W --> D
    W --> Q
    
    RC --> R
    RF --> C
    AM --> D
    AM --> C
    
    %% External API calls (ONLY from MCP servers)
    R -.->|API Calls| RAPI
    C -.->|API Calls| CAPI
    RC -.->|Config Updates| RAPI
    
    %% Infrastructure connections
    D --> DB
    C --> REDIS
    R --> REDIS
    Q --> HZ
    W --> REDIS
    
    %% Styling
    classDef ui fill:#e8f5e9,stroke:#4caf50,stroke-width:2px
    classDef gateway fill:#fff3e0,stroke:#ff9800,stroke-width:3px
    classDef mcp fill:#e3f2fd,stroke:#2196f3,stroke-width:2px
    classDef external fill:#ffebee,stroke:#f44336,stroke-width:2px
    classDef infra fill:#f3e5f5,stroke:#9c27b0,stroke-width:2px
    
    class UI,API ui
    class GW,SD,AUTH gateway
    class RC,RF,AM,W,R,C,D,Q mcp
    class RAPI,CAPI external
    class DB,REDIS,HZ infra
```

## Data Flow Example: Retell Configuration Update

```mermaid
sequenceDiagram
    participant UI as Filament UI
    participant GW as MCP Gateway
    participant RC as RetellConfigMCP
    participant R as RetellMCP
    participant RAPI as Retell.ai API
    participant DB as Database
    
    UI->>GW: Update webhook settings
    Note over GW: JSON-RPC Request
    GW->>RC: retell.config.updateWebhookSettings
    RC->>DB: Load current config
    RC->>RC: Validate settings
    RC->>R: Get API client
    R->>RAPI: Update webhook via API
    RAPI-->>R: Success response
    R-->>RC: Updated
    RC->>DB: Save new config
    RC-->>GW: Success result
    GW-->>UI: JSON-RPC Response
    Note over UI: Show success message
```

## Key Architecture Principles

### 1. No Direct External API Calls from UI
```
❌ WRONG:
UI --> Retell.ai API

✅ CORRECT:
UI --> MCP Gateway --> RetellMCPServer --> Retell.ai API
```

### 2. Unified Communication Protocol
All MCP communication uses JSON-RPC 2.0:
- Standardized request/response format
- Built-in error handling
- Request ID for tracking
- Batch request support

### 3. Service Discovery & Health Checks
```php
// Automatic service registration
MCPServiceRegistry::register('retell.config', RetellConfigurationMCPServer::class);

// Health check endpoint
GET /api/mcp/health
{
  "services": {
    "retell.config": { "status": "healthy", "latency": 12 },
    "calcom": { "status": "healthy", "latency": 45 },
    "webhook": { "status": "healthy", "latency": 8 }
  }
}
```

### 4. Circuit Breaker Protection
Each external service has circuit breaker protection:
- **Closed**: Normal operation
- **Open**: Service unavailable, fail fast
- **Half-Open**: Testing if service recovered

### 5. Caching Strategy
```
UI Request --> MCP Gateway --> MCP Server
                                    |
                                    v
                              Check Cache (Redis)
                                    |
                            [Cache Hit] [Cache Miss]
                                |            |
                            Return Data   External API
                                            |
                                      Update Cache
```

## Benefits of MCP-First Architecture

1. **Abstraction**: UI doesn't know about external services
2. **Consistency**: All services follow same patterns
3. **Reliability**: Circuit breakers, retries, caching
4. **Security**: Single point for authentication
5. **Monitoring**: All external calls tracked
6. **Testing**: Easy to mock MCP servers
7. **Maintenance**: Changes isolated to MCP layer

## MCP Server Naming Convention

```
Format: {service}.{resource}.{action}

Examples:
- retell.config.getWebhookConfiguration
- retell.function.execute
- calcom.booking.create
- appointments.find
- database.customer.create
```

## Error Handling Flow

```mermaid
graph LR
    A[External API Error] --> B{Circuit Breaker}
    B -->|Open| C[Return Cached/Default]
    B -->|Closed| D[Retry Logic]
    D -->|Success| E[Update Cache]
    D -->|Fail| F[Log Error]
    F --> G[Return Error Response]
    
    style A fill:#ff5252
    style C fill:#ffc107
    style E fill:#4caf50
    style G fill:#ff5252
```

---

This architecture ensures complete separation of concerns, with the UI layer completely abstracted from external service details. All complexity is handled by specialized MCP servers that can be independently developed, tested, and deployed.