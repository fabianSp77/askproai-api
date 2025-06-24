# MCP API Endpoints

!!! info "MCP Gateway"
    All MCP operations go through the unified gateway endpoint using JSON-RPC 2.0 protocol.

## 🚀 Gateway Endpoint

### Main Gateway
```
POST /api/mcp/gateway
Content-Type: application/json
Authorization: Bearer {token}
```

All MCP requests follow the JSON-RPC 2.0 specification:

```json
{
    "jsonrpc": "2.0",
    "method": "server.method",
    "params": {
        // Method-specific parameters
    },
    "id": "unique-request-id"
}
```

### Health Check
```
GET /api/mcp/health
```

Returns health status of all MCP servers:

```json
{
    "gateway": "healthy",
    "servers": {
        "retell_config": {
            "status": "healthy",
            "response_time_ms": 45
        },
        "retell_custom": {
            "status": "healthy",
            "response_time_ms": 12
        },
        "webhook": {
            "status": "healthy",
            "response_time_ms": 23
        }
    },
    "timestamp": "2025-06-23T14:30:00Z"
}
```

## 📡 Retell Configuration Methods

### getWebhook

Get current webhook configuration for a company.

**Request:**
```json
{
    "jsonrpc": "2.0",
    "method": "retell_config.getWebhook",
    "params": {
        "company_id": 1
    },
    "id": "req_001"
}
```

**Response:**
```json
{
    "jsonrpc": "2.0",
    "result": {
        "agents": [
            {
                "agent_id": "agent_abc123",
                "agent_name": "Main Reception",
                "webhook_url": "https://api.askproai.de/api/mcp/retell/custom-function",
                "last_updated_timestamp": 1719156000
            }
        ],
        "webhook_url": "https://api.askproai.de/api/mcp/retell/custom-function"
    },
    "id": "req_001"
}
```

### updateWebhook

Update webhook configuration for specified agents.

**Request:**
```json
{
    "jsonrpc": "2.0",
    "method": "retell_config.updateWebhook",
    "params": {
        "company_id": 1,
        "webhook_url": "https://api.askproai.de/api/mcp/retell/custom-function",
        "events": ["call_started", "call_ended", "call_analyzed"],
        "agent_ids": ["agent_abc123", "agent_xyz789"]
    },
    "id": "req_002"
}
```

**Response:**
```json
{
    "jsonrpc": "2.0",
    "result": {
        "success": true,
        "updated_count": 2,
        "details": [
            {
                "agent_id": "agent_abc123",
                "status": "updated"
            },
            {
                "agent_id": "agent_xyz789",
                "status": "updated"
            }
        ]
    },
    "id": "req_002"
}
```

### testWebhook

Send a test webhook to verify configuration.

**Request:**
```json
{
    "jsonrpc": "2.0",
    "method": "retell_config.testWebhook",
    "params": {
        "company_id": 1,
        "webhook_url": "https://api.askproai.de/api/mcp/retell/custom-function",
        "test_payload": {
            "event": "call_ended",
            "call": {
                "call_id": "test_123",
                "from_number": "+491234567890",
                "to_number": "+493083793369"
            }
        }
    },
    "id": "req_003"
}
```

**Response:**
```json
{
    "jsonrpc": "2.0",
    "result": {
        "success": true,
        "response": {
            "status_code": 200,
            "body": {
                "success": true,
                "message": "Webhook processed"
            },
            "headers": {
                "content-type": "application/json"
            },
            "duration_ms": 156
        }
    },
    "id": "req_003"
}
```

### deployCustomFunctions

Deploy custom functions to Retell agents.

**Request:**
```json
{
    "jsonrpc": "2.0",
    "method": "retell_config.deployCustomFunctions",
    "params": {
        "company_id": 1,
        "agent_ids": ["agent_abc123"],
        "functions": {
            "collect_appointment_information": {
                "description": "Collect appointment details",
                "parameters": {
                    "type": "object",
                    "properties": {
                        "name": {"type": "string"},
                        "date": {"type": "string"},
                        "time": {"type": "string"},
                        "service": {"type": "string"}
                    },
                    "required": ["name", "date", "time"]
                }
            }
        }
    },
    "id": "req_004"
}
```

**Response:**
```json
{
    "jsonrpc": "2.0",
    "result": {
        "deployed": [
            {
                "agent_id": "agent_abc123",
                "success": true,
                "functions": ["collect_appointment_information"]
            }
        ]
    },
    "id": "req_004"
}
```

### getAgentPromptTemplate

Get agent prompt template with company-specific variables.

**Request:**
```json
{
    "jsonrpc": "2.0",
    "method": "retell_config.getAgentPromptTemplate",
    "params": {
        "company_id": 1,
        "branch_id": 1,
        "language": "de"
    },
    "id": "req_005"
}
```

**Response:**
```json
{
    "jsonrpc": "2.0",
    "result": {
        "template": "Sie sind der KI-Assistent für {{company_name}}...",
        "variables": {
            "company_name": "AskProAI Demo",
            "branch_name": "Berlin Hauptfiliale",
            "services": ["Beratung", "Support", "Training"],
            "working_hours": "Mo-Fr 9:00-18:00"
        },
        "rendered": "Sie sind der KI-Assistent für AskProAI Demo..."
    },
    "id": "req_005"
}
```

## 📞 Custom Function Endpoints

### Retell Custom Function Handler
```
POST /api/mcp/retell/custom-function
Content-Type: application/json
X-Retell-Signature: {signature}
```

This endpoint handles custom function calls from Retell.ai:

**Request from Retell:**
```json
{
    "function_name": "collect_appointment_information",
    "call_id": "call_abc123",
    "parameters": {
        "name": "John Doe",
        "date": "2025-07-15",
        "time": "14:00",
        "service": "Consultation"
    }
}
```

**Response to Retell:**
```json
{
    "success": true,
    "message": "Appointment information collected successfully",
    "data": {
        "confirmation_number": "APT-2025-07-15-001"
    }
}
```

## 🗓️ Appointment Management Methods

### findAppointments

Find appointments by phone number.

**Request:**
```json
{
    "jsonrpc": "2.0",
    "method": "appointment_mgmt.findAppointments",
    "params": {
        "phone": "+491234567890",
        "upcoming_only": true
    },
    "id": "req_006"
}
```

**Response:**
```json
{
    "jsonrpc": "2.0",
    "result": {
        "appointments": [
            {
                "id": 12345,
                "date": "2025-07-15",
                "time": "14:00",
                "service": "Consultation",
                "duration": 60,
                "branch": "Berlin Office",
                "staff": "Dr. Smith"
            }
        ],
        "customer_name": "John Doe"
    },
    "id": "req_006"
}
```

### rescheduleAppointment

Reschedule an existing appointment.

**Request:**
```json
{
    "jsonrpc": "2.0",
    "method": "appointment_mgmt.rescheduleAppointment",
    "params": {
        "appointment_id": 12345,
        "phone": "+491234567890",
        "new_date": "2025-07-16",
        "new_time": "15:00"
    },
    "id": "req_007"
}
```

**Response:**
```json
{
    "jsonrpc": "2.0",
    "result": {
        "success": true,
        "appointment": {
            "id": 12345,
            "old_datetime": "2025-07-15 14:00",
            "new_datetime": "2025-07-16 15:00",
            "confirmation_sent": true
        }
    },
    "id": "req_007"
}
```

### cancelAppointment

Cancel an appointment.

**Request:**
```json
{
    "jsonrpc": "2.0",
    "method": "appointment_mgmt.cancelAppointment",
    "params": {
        "appointment_id": 12345,
        "phone": "+491234567890",
        "reason": "Schedule conflict"
    },
    "id": "req_008"
}
```

**Response:**
```json
{
    "jsonrpc": "2.0",
    "result": {
        "success": true,
        "message": "Appointment cancelled successfully",
        "refund_applicable": false
    },
    "id": "req_008"
}
```

## 🔧 Webhook Processing

### processRetellWebhook

Process incoming Retell webhook (internal use).

**Request:**
```json
{
    "jsonrpc": "2.0",
    "method": "webhook.processRetellWebhook",
    "params": {
        "event": "call_ended",
        "call": {
            "call_id": "call_123",
            "from_number": "+491234567890",
            "to_number": "+493083793369",
            "duration": 180,
            "variables": {
                "name": "John Doe",
                "appointment_date": "2025-07-15",
                "appointment_time": "14:00"
            }
        }
    },
    "id": "webhook_001"
}
```

## 🔐 Authentication

All MCP gateway requests require authentication:

```bash
curl -X POST https://api.askproai.de/api/mcp/gateway \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -d '{
    "jsonrpc": "2.0",
    "method": "retell_config.getWebhook",
    "params": {"company_id": 1},
    "id": "test_001"
  }'
```

## ⚠️ Error Responses

MCP follows JSON-RPC 2.0 error codes:

```json
{
    "jsonrpc": "2.0",
    "error": {
        "code": -32600,
        "message": "Invalid Request",
        "data": {
            "details": "Missing required parameter: company_id"
        }
    },
    "id": "req_001"
}
```

### Standard Error Codes

| Code | Message | Description |
|------|---------|-------------|
| -32700 | Parse error | Invalid JSON |
| -32600 | Invalid Request | Invalid request format |
| -32601 | Method not found | Unknown method |
| -32602 | Invalid params | Invalid parameters |
| -32603 | Internal error | Server error |
| -32000 | Server error | Application-specific error |

## 📊 Rate Limiting

MCP endpoints are rate-limited per company:

- **Default**: 100 requests per minute
- **Webhook endpoints**: 1000 requests per minute
- **Test endpoints**: 10 requests per minute

Rate limit headers:
```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1719156000
```

## 🧪 Testing

### Using cURL

```bash
# Test webhook configuration
curl -X POST https://api.askproai.de/api/mcp/gateway \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "jsonrpc": "2.0",
    "method": "retell_config.testWebhook",
    "params": {
        "company_id": 1,
        "webhook_url": "https://api.askproai.de/api/mcp/retell/custom-function"
    },
    "id": "test_001"
  }'
```

### Using Postman

1. Set request type to POST
2. Set URL to `https://api.askproai.de/api/mcp/gateway`
3. Add Authorization header
4. Set body to raw JSON
5. Send request

### PHP Example

```php
$client = new \GuzzleHttp\Client();

$response = $client->post('https://api.askproai.de/api/mcp/gateway', [
    'headers' => [
        'Authorization' => 'Bearer ' . $apiToken,
        'Content-Type' => 'application/json',
    ],
    'json' => [
        'jsonrpc' => '2.0',
        'method' => 'retell_config.getWebhook',
        'params' => [
            'company_id' => 1
        ],
        'id' => uniqid('req_')
    ]
]);

$result = json_decode($response->getBody(), true);
```

---

!!! tip "Best Practice"
    Always include a unique ID in your requests to match responses, especially when making concurrent requests.