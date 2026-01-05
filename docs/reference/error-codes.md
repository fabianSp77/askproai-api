# Error Codes

Complete reference of error codes returned by the AskPro API Gateway.

## HTTP Status Codes

| Code | Meaning | Description |
|------|---------|-------------|
| 200 | OK | Request successful |
| 201 | Created | Resource created |
| 204 | No Content | Request successful, no content returned |
| 400 | Bad Request | Invalid request format |
| 401 | Unauthorized | Authentication required |
| 403 | Forbidden | Insufficient permissions |
| 404 | Not Found | Resource not found |
| 422 | Unprocessable Entity | Validation failed |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Server error |
| 502 | Bad Gateway | External service error |
| 503 | Service Unavailable | Service temporarily unavailable |

## Error Response Format

All errors follow this format:

```json
{
  "error": {
    "code": "error_code",
    "message": "Human-readable error message",
    "details": {
      "field": ["Specific field error"]
    }
  }
}
```

## Authentication Errors

### AUTH001 - Invalid Credentials

```json
{
  "error": {
    "code": "AUTH001",
    "message": "Invalid email or password"
  }
}
```

**Cause:** Login attempt with incorrect credentials.
**Solution:** Verify email and password.

### AUTH002 - Token Expired

```json
{
  "error": {
    "code": "AUTH002",
    "message": "Authentication token has expired"
  }
}
```

**Cause:** API token past expiration date.
**Solution:** Request a new token via `/api/v1/auth/token`.

### AUTH003 - Token Revoked

```json
{
  "error": {
    "code": "AUTH003",
    "message": "Authentication token has been revoked"
  }
}
```

**Cause:** Token was manually revoked.
**Solution:** Request a new token.

### AUTH004 - Insufficient Scope

```json
{
  "error": {
    "code": "AUTH004",
    "message": "Token lacks required scope: write:appointments"
  }
}
```

**Cause:** Token doesn't have permission for this action.
**Solution:** Request token with appropriate scopes.

## Validation Errors

### VAL001 - Required Field Missing

```json
{
  "error": {
    "code": "VAL001",
    "message": "Validation failed",
    "details": {
      "customer_name": ["The customer name field is required."]
    }
  }
}
```

### VAL002 - Invalid Format

```json
{
  "error": {
    "code": "VAL002",
    "message": "Validation failed",
    "details": {
      "email": ["The email must be a valid email address."],
      "phone": ["The phone format is invalid."]
    }
  }
}
```

### VAL003 - Invalid Date

```json
{
  "error": {
    "code": "VAL003",
    "message": "Validation failed",
    "details": {
      "start_time": ["The start time must be a date after today."]
    }
  }
}
```

## Appointment Errors

### APPT001 - Slot Unavailable

```json
{
  "error": {
    "code": "APPT001",
    "message": "The requested time slot is no longer available"
  }
}
```

**Cause:** Time slot was booked by someone else.
**Solution:** Refresh availability and select a different slot.

### APPT002 - Staff Not Available

```json
{
  "error": {
    "code": "APPT002",
    "message": "Staff member is not available at this time"
  }
}
```

**Cause:** Staff has a conflicting appointment or is off.
**Solution:** Choose a different time or staff member.

### APPT003 - Past Date

```json
{
  "error": {
    "code": "APPT003",
    "message": "Cannot book appointments in the past"
  }
}
```

### APPT004 - Outside Business Hours

```json
{
  "error": {
    "code": "APPT004",
    "message": "Requested time is outside business hours"
  }
}
```

### APPT005 - Cancellation Window Passed

```json
{
  "error": {
    "code": "APPT005",
    "message": "Cancellation window has passed (minimum 24 hours)"
  }
}
```

## Service Case Errors

### CASE001 - Category Not Found

```json
{
  "error": {
    "code": "CASE001",
    "message": "Service case category not found"
  }
}
```

### CASE002 - Invalid Status Transition

```json
{
  "error": {
    "code": "CASE002",
    "message": "Cannot transition from 'closed' to 'open'"
  }
}
```

**Valid transitions:**
- `open` → `in_progress`, `resolved`, `closed`
- `in_progress` → `open`, `resolved`, `closed`
- `resolved` → `closed`, `open` (reopen)
- `closed` → None (final state)

### CASE003 - SLA Violation

```json
{
  "error": {
    "code": "CASE003",
    "message": "Action would violate SLA requirements",
    "details": {
      "response_due": "2024-01-10T18:30:00Z",
      "current_time": "2024-01-10T19:00:00Z"
    }
  }
}
```

## Integration Errors

### INT001 - Cal.com API Error

```json
{
  "error": {
    "code": "INT001",
    "message": "Cal.com API returned an error",
    "details": {
      "calcom_error": "User not found",
      "calcom_code": 404
    }
  }
}
```

### INT002 - Retell API Error

```json
{
  "error": {
    "code": "INT002",
    "message": "Retell API returned an error",
    "details": {
      "retell_error": "Agent not found",
      "retell_code": "agent_not_found"
    }
  }
}
```

### INT003 - Webhook Delivery Failed

```json
{
  "error": {
    "code": "INT003",
    "message": "Failed to deliver webhook after 5 attempts",
    "details": {
      "last_status": 503,
      "last_error": "Connection refused",
      "webhook_url": "https://..."
    }
  }
}
```

## Rate Limit Errors

### RATE001 - Rate Limit Exceeded

```json
{
  "error": {
    "code": "RATE001",
    "message": "Rate limit exceeded. Please retry after 45 seconds.",
    "details": {
      "limit": 60,
      "remaining": 0,
      "reset_at": "2024-01-10T14:31:00Z",
      "retry_after": 45
    }
  }
}
```

## Multi-Tenant Errors

### TENANT001 - Cross-Tenant Access

```json
{
  "error": {
    "code": "TENANT001",
    "message": "Resource belongs to a different tenant"
  }
}
```

**Cause:** Attempting to access data from another company.
**Solution:** Verify you're using the correct company context.

### TENANT002 - Tenant Not Found

```json
{
  "error": {
    "code": "TENANT002",
    "message": "Company not found or inactive"
  }
}
```

## Server Errors

### SRV001 - Internal Error

```json
{
  "error": {
    "code": "SRV001",
    "message": "An unexpected error occurred",
    "reference": "err_abc123"
  }
}
```

**Action:** Contact support with the reference ID.

### SRV002 - Service Unavailable

```json
{
  "error": {
    "code": "SRV002",
    "message": "Service temporarily unavailable. Please retry."
  }
}
```

**Cause:** System maintenance or high load.
**Solution:** Retry after a few seconds.

### SRV003 - External Service Down

```json
{
  "error": {
    "code": "SRV003",
    "message": "External service is unavailable",
    "details": {
      "service": "cal.com",
      "status": "down"
    }
  }
}
```

## Error Handling Best Practices

### 1. Check Error Code First

```javascript
if (response.error.code === 'APPT001') {
  // Refresh availability and show new slots
  refreshAvailability();
} else if (response.error.code === 'AUTH002') {
  // Redirect to login
  redirectToLogin();
}
```

### 2. Display User-Friendly Messages

```javascript
const userMessages = {
  'APPT001': 'Dieser Termin ist leider nicht mehr verfügbar.',
  'AUTH001': 'E-Mail oder Passwort ist falsch.',
  'VAL001': 'Bitte füllen Sie alle Pflichtfelder aus.',
};

showError(userMessages[error.code] || error.message);
```

### 3. Log for Debugging

```javascript
console.error('API Error', {
  code: error.code,
  message: error.message,
  details: error.details,
  reference: error.reference,
});
```

### 4. Implement Retry Logic

```javascript
const retryableCodes = ['RATE001', 'SRV002', 'SRV003'];

if (retryableCodes.includes(error.code)) {
  await sleep(error.details?.retry_after || 5000);
  return retry();
}
```
