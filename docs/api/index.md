# API Reference

The AskPro API Gateway provides RESTful endpoints for integrations with external systems.

## Base URL

| Environment | URL |
|-------------|-----|
| Production | `https://api.askproai.de/api` |
| Staging | `https://staging.askproai.de/api` |

## Interactive Documentation

For a fully interactive API experience with "Try It" functionality, visit:

**[Interactive API Docs →](/docs/api)**

This documentation is auto-generated from our codebase using [Scramble](https://scramble.dedoc.co/).

## Authentication

### Bearer Token

```bash
curl -H "Authorization: Bearer YOUR_API_TOKEN" \
     https://api.askproai.de/api/endpoint
```

### Webhook Signatures

For webhook endpoints, requests are validated using HMAC signatures:

```
X-Signature: sha256=abc123...
```

## Rate Limiting

| Endpoint Type | Limit |
|---------------|-------|
| Standard API | 60 requests/minute |
| Webhook Endpoints | 100 requests/minute |
| Service Gateway | 20 operations/call/minute |

Rate limit headers are included in responses:

```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1704067200
```

## Response Format

All responses follow a consistent JSON structure:

### Success Response
```json
{
  "success": true,
  "data": { ... },
  "meta": {
    "timestamp": "2026-01-05T12:00:00Z"
  }
}
```

### Error Response
```json
{
  "success": false,
  "error": "Error message",
  "code": "ERROR_CODE",
  "details": { ... }
}
```

## HTTP Status Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not Found |
| 429 | Rate Limited |
| 500 | Server Error |

## API Categories

### Retell.ai Integration
- Webhook handling for voice call events
- Function call processing
- Session management

[View Retell Webhooks →](/api/retell-webhooks)

### Cal.com Integration
- Booking webhooks
- Availability queries
- Calendar sync

[View Cal.com Webhooks →](/api/calcom-webhooks)

### Service Gateway
- Case creation and management
- Output configuration
- SLA tracking

[View Service Gateway →](/api/service-gateway)

## OpenAPI Specification

Download the full OpenAPI 3.0 specification:

```bash
curl https://api.askproai.de/docs/api.json -o openapi.json
```
