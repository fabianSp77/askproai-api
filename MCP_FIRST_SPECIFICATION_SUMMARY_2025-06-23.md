# MCP-First Technical Specification Summary

## 📋 Executive Summary

This document provides a comprehensive technical specification for implementing a **MCP-First architecture** in AskProAI. The approach ensures that all external integrations (Retell.ai, Cal.com) are abstracted through MCP servers, with the UI never making direct API calls.

## 🎯 Core Objectives

1. **Complete Abstraction**: UI components only communicate with MCP servers
2. **Unified Protocol**: All communication uses JSON-RPC 2.0
3. **Enhanced Reliability**: Built-in circuit breakers, retries, and caching
4. **Service Discovery**: Automatic MCP server registration and health checks
5. **Better Testing**: Mock MCP servers for comprehensive testing

## 🏗️ Architecture Overview

### Existing MCP Servers (Already Implemented)
- **WebhookMCPServer**: Main orchestrator for webhook processing
- **RetellMCPServer**: Phone AI management (agents, calls, stats)
- **CalcomMCPServer**: Calendar integration with circuit breaker
- **DatabaseMCPServer**: Data operations with transaction support
- **QueueMCPServer**: Job management and dispatching

### New MCP Servers to Implement

#### 1. RetellConfigurationMCPServer
Manages all Retell.ai configuration through MCP, eliminating direct API calls from UI.

**Key Methods:**
- `retell.config.getWebhookConfiguration` - Get webhook URL and settings
- `retell.config.updateWebhookSettings` - Update webhook events
- `retell.config.getCustomFunctions` - List all custom functions
- `retell.config.updateCustomFunction` - Modify function settings
- `retell.config.testWebhook` - Test webhook connectivity

#### 2. RetellCustomFunctionMCPServer
Handles custom function execution during Retell calls via MCP gateway.

**Built-in Functions:**
- `collect_appointment_data` - Gather appointment details
- `check_availability` - Check calendar availability
- `find_next_slot` - Find alternative time slots
- `calculate_duration` - Calculate service duration

#### 3. AppointmentManagementMCPServer
Enables appointment modifications and cancellations via phone.

**Key Methods:**
- `appointments.find` - Find appointments by phone number
- `appointments.change` - Reschedule appointment
- `appointments.cancel` - Cancel appointment with reason

## 💻 UI Components

### Retell Configuration Page
```typescript
interface RetellConfigurationPage {
  components: {
    WebhookSettings: {
      webhookUrl: string; // read-only
      webhookSecret: string; // masked
      events: string[]; // selectable
    };
    CustomFunctions: {
      list: CustomFunction[];
      editor: FunctionEditor;
      testRunner: TestInterface;
    };
    AgentVersionManager: {
      versions: AgentVersion[];
      phoneAssignments: PhoneAgentMapping[];
    };
  };
}
```

### Key Features
- No direct API calls to external services
- Everything abstracted through MCP
- Live webhook testing
- Agent version management
- Custom function editor with schema validation

## 🗄️ Database Schema

```sql
-- Store Retell configuration per company
CREATE TABLE retell_configurations (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    company_id BIGINT NOT NULL UNIQUE,
    webhook_url VARCHAR(255),
    webhook_secret VARCHAR(255),
    webhook_events JSON,
    custom_functions JSON,
    agent_settings JSON,
    last_tested_at TIMESTAMP NULL,
    test_status ENUM('success', 'failed', 'pending'),
    FOREIGN KEY (company_id) REFERENCES companies(id)
);

-- Custom functions registry
CREATE TABLE retell_custom_functions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    company_id BIGINT NULL,
    name VARCHAR(100) NOT NULL,
    type ENUM('external_api', 'data_collection'),
    description TEXT,
    parameter_schema JSON NOT NULL,
    response_schema JSON,
    is_global BOOLEAN DEFAULT FALSE,
    is_enabled BOOLEAN DEFAULT TRUE,
    last_used_at TIMESTAMP NULL,
    usage_count INT DEFAULT 0
);

-- Function execution logs
CREATE TABLE retell_custom_function_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    call_id VARCHAR(255),
    function_name VARCHAR(100),
    request_data JSON,
    response_data JSON,
    status ENUM('success', 'failed'),
    duration_ms INT,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## 📡 MCP Protocol

### Request Format
```json
{
  "jsonrpc": "2.0",
  "id": "unique-request-id",
  "method": "server.method.action",
  "params": {
    "company_id": 123,
    "data": {}
  }
}
```

### Response Format
```json
{
  "jsonrpc": "2.0",
  "id": "unique-request-id",
  "result": {
    "success": true,
    "data": {},
    "metadata": {
      "cached": false,
      "processing_time_ms": 145
    }
  }
}
```

### Error Format
```json
{
  "jsonrpc": "2.0",
  "id": "unique-request-id",
  "error": {
    "code": -32603,
    "message": "Internal error",
    "data": {
      "type": "ServiceUnavailable",
      "retry_after": 60
    }
  }
}
```

## 🚀 Implementation Plan

### Phase 1: Infrastructure (Week 1)
- [ ] Create MCP Gateway Controller
- [ ] Implement Service Discovery
- [ ] Setup Health Check System
- [ ] Add Authentication Middleware
- [ ] Create Base MCP Server Class

### Phase 2: Retell Configuration (Week 2)
- [ ] Implement RetellConfigurationMCPServer
- [ ] Create database migrations
- [ ] Build Filament UI components
- [ ] Add webhook testing endpoints
- [ ] Implement configuration caching

### Phase 3: Custom Functions (Week 3)
- [ ] Implement RetellCustomFunctionMCPServer
- [ ] Create gateway endpoint
- [ ] Build function editor UI
- [ ] Add built-in functions
- [ ] Implement function validation

### Phase 4: Appointment Management (Week 4)
- [ ] Implement AppointmentManagementMCPServer
- [ ] Add phone-based lookup
- [ ] Test modification flows
- [ ] Implement security checks
- [ ] Add audit logging

### Phase 5: Documentation & Testing (Week 5)
- [ ] Update mkdocs documentation
- [ ] Write integration tests
- [ ] Performance testing
- [ ] Security audit
- [ ] Create deployment guide

## 🛡️ Security Considerations

### Authentication
- MCP Token-based authentication
- Company ID validation
- Rate limiting per endpoint
- Request signing for sensitive operations

### Input Validation
- JSON Schema validation for all inputs
- Phone number validation with libphonenumber
- Date/time validation with timezone handling
- SQL injection prevention

## 📊 Monitoring & Observability

### Metrics
- Request count by server and method
- Response times (p50, p95, p99)
- Error rates by error code
- Circuit breaker states
- Cache hit rates

### Logging
- Structured logging with correlation IDs
- Request/response logging (sanitized)
- Error context with stack traces
- Performance metrics per operation

## ✅ Benefits

1. **Simplified UI Development**: UI developers don't need to know external API details
2. **Centralized Error Handling**: All errors handled consistently
3. **Better Testing**: Mock servers for all external dependencies
4. **Enhanced Security**: Single point for authentication and validation
5. **Improved Monitoring**: All external calls go through MCP
6. **Easier Maintenance**: Changes to external APIs only affect MCP servers

## 📝 Documentation Location

Full technical specification available at:
`/var/www/api-gateway/ASKPROAI_MCP_FIRST_TECHNICAL_SPECIFICATION_2025-06-23.md`

This document contains:
- Complete method signatures
- Detailed implementation examples
- Error handling strategies
- Testing approaches
- Deployment configuration
- Performance optimization techniques

## 🎯 Next Steps

1. **Review**: Technical review of the specification
2. **Approval**: Get stakeholder approval
3. **Start Phase 1**: Begin with infrastructure implementation
4. **Weekly Reviews**: Track progress against milestones
5. **Iterative Testing**: Test each phase thoroughly before moving to next

---

**Created**: 2025-06-23  
**Status**: Ready for Review  
**Author**: Claude Code