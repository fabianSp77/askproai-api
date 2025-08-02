# MCP Gateway API Documentation

## Overview

The MCP Gateway provides a unified API endpoint for accessing all MCP (Model Context Protocol) servers in the AskProAI system. This allows for dynamic routing of requests to the appropriate service without needing to know the specific endpoint.

## Base URL

```
https://api.askproai.de/api/v2/mcp
```

## Authentication

All endpoints require authentication using Laravel Sanctum. Include your bearer token in the Authorization header:

```
Authorization: Bearer YOUR_TOKEN_HERE
```

## Available Endpoints

### 1. Execute Direct MCP Call

Execute a specific method on a known MCP server.

**Endpoint:** `POST /execute`

**Request Body:**
```json
{
    "server": "billing",
    "method": "getBillingOverview",
    "params": {
        "company_id": 123
    }
}
```

**Response:**
```json
{
    "success": true,
    "server": "billing",
    "method": "getBillingOverview",
    "result": {
        "balance": 150.50,
        "transactions": [...],
        "usage": {...}
    }
}
```

### 2. Auto-Discover and Execute

Let the system automatically discover the best MCP server for your task.

**Endpoint:** `POST /discover`

**Request Body:**
```json
{
    "task": "get customer billing information for last month",
    "params": {
        "customer_id": 456
    },
    "context": {
        "preferred_format": "detailed"
    }
}
```

**Response:**
```json
{
    "success": true,
    "task": "get customer billing information for last month",
    "discovered_server": "billing",
    "confidence": 0.95,
    "result": {
        "customer": {...},
        "billing_summary": {...}
    }
}
```

### 3. Batch Execute

Execute multiple MCP calls in a single request.

**Endpoint:** `POST /batch`

**Request Body:**
```json
{
    "requests": [
        {
            "server": "customer",
            "method": "getCustomerDetails",
            "params": {"customer_id": 123}
        },
        {
            "server": "appointment",
            "method": "getAppointmentHistory",
            "params": {"customer_id": 123}
        },
        {
            "server": "billing",
            "method": "getCustomerBalance",
            "params": {"customer_id": 123}
        }
    ]
}
```

**Response:**
```json
{
    "batch_id": "550e8400-e29b-41d4-a716-446655440000",
    "total": 3,
    "successful": 3,
    "failed": 0,
    "results": [
        {
            "index": 0,
            "success": true,
            "server": "customer",
            "method": "getCustomerDetails",
            "result": {...}
        },
        // ... more results
    ]
}
```

### 4. List Available Servers

Get a list of all available MCP servers.

**Endpoint:** `GET /servers`

**Response:**
```json
{
    "servers": [
        {
            "name": "billing",
            "description": "Billing and payment management",
            "status": "active"
        },
        {
            "name": "customer",
            "description": "Customer data and management",
            "status": "active"
        },
        // ... more servers
    ],
    "total": 15
}
```

### 5. Get Server Information

Get detailed information about a specific MCP server including available methods.

**Endpoint:** `GET /servers/{server}`

**Example:** `GET /servers/billing`

**Response:**
```json
{
    "server": "billing",
    "description": "Billing and payment management",
    "methods": [
        {
            "name": "getBillingOverview",
            "description": "Get billing overview including balance, usage, and limits",
            "parameters": {...}
        },
        {
            "name": "topupBalance",
            "description": "Add funds to company balance",
            "parameters": {...}
        },
        // ... more methods
    ],
    "status": "active"
}
```

## Available MCP Servers

| Server | Description |
|--------|-------------|
| `billing` | Billing and payment management |
| `customer` | Customer data and management |
| `appointment` | Appointment scheduling and management |
| `team` | Team member management |
| `call` | Call tracking and management |
| `analytics` | Analytics and reporting |
| `calcom` | Calendar integration |
| `retell` | AI phone service integration |
| `stripe` | Payment processing |
| `webhook` | Webhook management |
| `queue` | Job queue management |
| `knowledge` | Knowledge base |
| `company` | Company management |
| `branch` | Branch/location management |
| `sentry` | Error tracking |

## Common Parameters

The gateway automatically injects these parameters if not provided:

- `correlation_id`: Unique ID for request tracking
- `company_id`: Current company context
- `user_id`: Current authenticated user

## Error Handling

All endpoints return consistent error responses:

```json
{
    "success": false,
    "error": "Error message here",
    "server": "billing",
    "method": "invalidMethod"
}
```

Common HTTP status codes:
- `401`: Unauthorized - Invalid or missing authentication
- `404`: Not Found - Server or method not found
- `422`: Validation Error - Invalid parameters
- `500`: Server Error - Internal error during execution

## Rate Limiting

The API is rate-limited to prevent abuse:
- Standard: 60 requests per minute
- Batch endpoint: 20 requests per minute

## Examples

### Example: Complete Customer Overview

```bash
curl -X POST https://api.askproai.de/api/v2/mcp/batch \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "requests": [
        {
            "server": "customer",
            "method": "getCustomerDetails",
            "params": {"customer_id": 123}
        },
        {
            "server": "appointment",
            "method": "searchAppointments",
            "params": {
                "filters": {"customer_id": 123},
                "per_page": 10
            }
        },
        {
            "server": "call",
            "method": "listCalls",
            "params": {
                "filters": {"customer_id": 123},
                "per_page": 10
            }
        },
        {
            "server": "billing",
            "method": "listTransactions",
            "params": {
                "filters": {"customer_id": 123},
                "per_page": 10
            }
        }
    ]
}'
```

### Example: Natural Language Task

```bash
curl -X POST https://api.askproai.de/api/v2/mcp/discover \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "task": "find all customers who haven'\''t had an appointment in the last 3 months",
    "params": {
        "include_contact_info": true
    }
}'
```

## Best Practices

1. **Use Batch Requests**: When you need data from multiple servers, use the batch endpoint to reduce API calls
2. **Leverage Auto-Discovery**: For complex queries, let the system discover the best approach
3. **Include Correlation IDs**: For debugging, include your own correlation_id in params
4. **Cache Server Info**: The server list and methods don't change often, cache them locally
5. **Handle Errors Gracefully**: Always check the `success` field before processing results

## Migration from Direct Endpoints

If you're currently using direct endpoints like `/api/v2/customers` or `/api/v2/appointments`, you can migrate to the MCP Gateway:

**Old way:**
```
GET /api/v2/customers/123
```

**New way:**
```json
POST /api/v2/mcp/execute
{
    "server": "customer",
    "method": "getCustomerDetails",
    "params": {"customer_id": 123}
}
```

The MCP Gateway provides more flexibility and features while maintaining backward compatibility through the unified interface.