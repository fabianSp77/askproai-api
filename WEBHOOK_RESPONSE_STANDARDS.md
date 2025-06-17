# Webhook Response Standards

## Overview
This document defines the standardized response formats for all webhook endpoints in the AskProAI platform.

## General Principles

1. **Consistency**: All webhooks should return consistent response formats
2. **Provider Compatibility**: Respect provider-specific requirements
3. **Error Handling**: Graceful error handling without causing retries unless necessary
4. **Correlation Tracking**: Include correlation IDs for debugging

## Standard Response Formats

### Success Response

```json
{
    "status": "success|accepted|duplicate",
    "message": "Human-readable message",
    "correlation_id": "uuid-v4",
    "data": {} // Optional, provider-specific data
}
```

### Error Response

```json
{
    "error": "error_code",
    "message": "Human-readable error message",
    "correlation_id": "uuid-v4",
    "details": {} // Optional, only in development
}
```

## Provider-Specific Requirements

### Cal.com
- **Success**: 200 OK with JSON response
- **Signature Error**: 401 Unauthorized
- **Other Errors**: 500 Internal Server Error

```json
// Success
{
    "status": "accepted",
    "correlation_id": "uuid-v4"
}

// Error
{
    "error": "Invalid signature",
    "message": "Webhook signature verification failed"
}
```

### Retell.ai
- **Success**: 200 OK for most events, 204 No Content for some
- **All Errors**: Still return success to prevent retries
- **Inbound Calls**: Must return agent configuration

```json
// Standard webhook response
{
    "success": true,
    "message": "Webhook processed successfully",
    "correlation_id": "uuid-v4"
}

// Inbound call response
{
    "response": {
        "agent_id": "agent_xxx",
        "dynamic_variables": {
            "company_name": "AskProAI",
            "caller_number": "+49xxx"
        }
    }
}
```

### Stripe
- **Success**: Simple text response "OK" or empty 200
- **Signature Error**: 400 Bad Request
- **Processing Error**: Still return 200 to prevent retries

```text
// Success
OK

// Signature Error
Invalid
```

### Billing (Legacy Stripe)
- Same as Stripe requirements
- Simple text responses

## HTTP Status Codes

| Status | Usage |
|--------|-------|
| 200 | Success, webhook processed |
| 204 | Success, no content needed |
| 400 | Bad request, invalid payload |
| 401 | Unauthorized, signature verification failed |
| 422 | Unprocessable entity, validation failed |
| 500 | Internal server error (use sparingly) |

## Implementation Guidelines

1. **Use WebhookProcessor**: All webhook controllers should use the centralized WebhookProcessor service
2. **Signature Verification**: Always verify signatures through WebhookProcessor
3. **Deduplication**: WebhookProcessor handles deduplication automatically
4. **Error Logging**: Log errors but return success to prevent unwanted retries
5. **Correlation IDs**: Always include correlation IDs for tracing

## Example Implementation

```php
public function handle(Request $request)
{
    $correlationId = $request->input('correlation_id') ?? app('correlation_id');
    $payload = $request->all();
    $headers = $request->headers->all();
    
    try {
        $result = $this->webhookProcessor->process(
            WebhookEvent::PROVIDER_EXAMPLE,
            $payload,
            $headers,
            $correlationId
        );
        
        if ($result['duplicate']) {
            return response()->json([
                'status' => 'duplicate',
                'message' => 'Webhook already processed',
                'correlation_id' => $correlationId
            ], 200);
        }
        
        return response()->json([
            'status' => 'accepted',
            'correlation_id' => $correlationId
        ], 200);
        
    } catch (\App\Exceptions\WebhookSignatureException $e) {
        return response()->json([
            'error' => 'Invalid signature',
            'message' => $e->getMessage(),
            'correlation_id' => $correlationId
        ], 401);
    }
}
```

## Migration Checklist

When migrating a webhook controller to use WebhookProcessor:

- [ ] Remove manual signature verification code
- [ ] Add WebhookProcessor dependency injection
- [ ] Update response format to match standards
- [ ] Remove duplicate webhook detection code
- [ ] Update error handling to use try-catch
- [ ] Test with actual webhook payloads
- [ ] Update routes to remove signature middleware
- [ ] Document any provider-specific requirements