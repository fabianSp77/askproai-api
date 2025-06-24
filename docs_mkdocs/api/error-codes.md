# Error Codes Reference

## Overview

AskProAI uses standardized error codes to help developers quickly identify and resolve issues. All error responses follow a consistent format.

## Error Response Format

```json
{
  "error": "Error Type",
  "message": "Human-readable error description",
  "code": "ERR_001",
  "details": {
    "field": "Additional context"
  },
  "timestamp": "2025-06-23T12:00:00Z",
  "request_id": "req_abc123"
}
```

## HTTP Status Codes

| Status | Meaning | Use Case |
|--------|---------|----------|
| 200 | OK | Successful request |
| 201 | Created | Resource successfully created |
| 204 | No Content | Successful request with no response body |
| 400 | Bad Request | Invalid request parameters |
| 401 | Unauthorized | Missing or invalid authentication |
| 403 | Forbidden | Insufficient permissions |
| 404 | Not Found | Resource not found |
| 409 | Conflict | Resource conflict (e.g., duplicate) |
| 422 | Unprocessable Entity | Validation errors |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Server error |
| 503 | Service Unavailable | Service temporarily unavailable |

## Error Code Categories

### Authentication Errors (AUTH_xxx)

| Code | Message | Description |
|------|---------|-------------|
| AUTH_001 | Invalid credentials | Username or password incorrect |
| AUTH_002 | Token expired | Authentication token has expired |
| AUTH_003 | Token invalid | Malformed or invalid token |
| AUTH_004 | Account disabled | User account has been disabled |
| AUTH_005 | Two-factor required | 2FA verification required |

### Validation Errors (VAL_xxx)

| Code | Message | Description |
|------|---------|-------------|
| VAL_001 | Required field missing | A required field was not provided |
| VAL_002 | Invalid format | Field format does not match requirements |
| VAL_003 | Value out of range | Numeric value outside allowed range |
| VAL_004 | Invalid date/time | Date/time format or value invalid |
| VAL_005 | Duplicate value | Value already exists (unique constraint) |

### Resource Errors (RES_xxx)

| Code | Message | Description |
|------|---------|-------------|
| RES_001 | Resource not found | Requested resource does not exist |
| RES_002 | Resource locked | Resource is temporarily locked |
| RES_003 | Resource deleted | Resource has been deleted |
| RES_004 | Parent not found | Parent resource does not exist |
| RES_005 | Child resources exist | Cannot delete due to dependencies |

### Booking Errors (BOOK_xxx)

| Code | Message | Description |
|------|---------|-------------|
| BOOK_001 | Time slot unavailable | Selected time slot is no longer available |
| BOOK_002 | Outside business hours | Booking time outside operating hours |
| BOOK_003 | Too far in advance | Booking exceeds advance booking limit |
| BOOK_004 | Past date | Cannot book in the past |
| BOOK_005 | Double booking | Time slot already booked |
| BOOK_006 | Service unavailable | Selected service not available |
| BOOK_007 | Staff unavailable | Selected staff member not available |

### Integration Errors (INT_xxx)

| Code | Message | Description |
|------|---------|-------------|
| INT_001 | Cal.com unavailable | Cal.com service is not responding |
| INT_002 | Retell.ai error | Retell.ai API returned an error |
| INT_003 | Stripe payment failed | Payment processing failed |
| INT_004 | Webhook verification failed | Webhook signature invalid |
| INT_005 | External service timeout | Third-party service timeout |

### Phone System Errors (PHONE_xxx)

| Code | Message | Description |
|------|---------|-------------|
| PHONE_001 | Invalid phone number | Phone number format invalid |
| PHONE_002 | Phone number not found | Phone number not registered |
| PHONE_003 | Call in progress | Another call is already active |
| PHONE_004 | Agent not configured | AI agent not properly configured |
| PHONE_005 | Recording failed | Call recording could not be saved |

### Rate Limiting Errors (RATE_xxx)

| Code | Message | Description |
|------|---------|-------------|
| RATE_001 | Minute limit exceeded | Too many requests per minute |
| RATE_002 | Hour limit exceeded | Too many requests per hour |
| RATE_003 | Daily limit exceeded | Daily request quota exceeded |
| RATE_004 | Concurrent limit | Too many concurrent requests |

### System Errors (SYS_xxx)

| Code | Message | Description |
|------|---------|-------------|
| SYS_001 | Database error | Database operation failed |
| SYS_002 | Cache unavailable | Cache service not responding |
| SYS_003 | Queue error | Job queue processing error |
| SYS_004 | File system error | File operation failed |
| SYS_005 | Configuration error | System misconfiguration |

## Error Handling Examples

### JavaScript/TypeScript

```typescript
try {
  const response = await api.post('/appointments', data);
  return response.data;
} catch (error) {
  if (error.response) {
    switch (error.response.data.code) {
      case 'BOOK_001':
        alert('This time slot is no longer available');
        refreshAvailableSlots();
        break;
      case 'AUTH_002':
        // Token expired, refresh and retry
        await refreshToken();
        return retryRequest();
      default:
        console.error('API Error:', error.response.data);
    }
  }
}
```

### PHP

```php
try {
    $response = $client->post('appointments', ['json' => $data]);
} catch (ClientException $e) {
    $error = json_decode($e->getResponse()->getBody(), true);
    
    switch ($error['code']) {
        case 'BOOK_001':
            throw new SlotUnavailableException($error['message']);
        case 'VAL_001':
            throw new ValidationException($error['details']);
        default:
            throw new ApiException($error['message'], $error['code']);
    }
}
```

## Webhook Error Responses

Webhook endpoints return specific error codes:

```json
{
  "success": false,
  "error": {
    "code": "WEBHOOK_001",
    "message": "Invalid signature",
    "expected_signature": "sha256=...",
    "received_signature": "sha256=..."
  }
}
```

### Webhook Error Codes

| Code | Message | Action Required |
|------|---------|----------------|
| WEBHOOK_001 | Invalid signature | Verify webhook secret |
| WEBHOOK_002 | Duplicate event | Event already processed |
| WEBHOOK_003 | Invalid payload | Check payload format |
| WEBHOOK_004 | Event type unknown | Update integration |

## Debugging Tips

1. **Check the request_id**: Use this to track requests in logs
2. **Review error details**: Additional context in the `details` field
3. **Monitor patterns**: Repeated errors may indicate configuration issues
4. **Use test endpoints**: `/api/test/*` endpoints for debugging
5. **Enable debug mode**: Add `?debug=true` for verbose errors (dev only)

## Recovery Strategies

### Automatic Retry

Implement exponential backoff for these errors:
- `INT_005` - External service timeout
- `SYS_002` - Cache unavailable
- `RATE_001` - Rate limit (wait for reset)

### User Intervention Required

These errors need user action:
- `BOOK_001` - Select different time slot
- `VAL_xxx` - Fix validation errors
- `AUTH_001` - Re-enter credentials

### Contact Support

These errors may need support:
- `SYS_xxx` - System errors
- `INT_001` - Integration failures
- Repeated `PHONE_xxx` errors