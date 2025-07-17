# API Documentation Template

> 📋 **Version**: 1.0  
> 📅 **Last Updated**: {DATE}  
> 👥 **Maintained By**: {TEAM/PERSON}  
> 🔗 **Related Docs**: [Integration Guide](../integration-guide.md) | [Security](../security.md)

## Overview

Brief description of the API's purpose and main functionality.

### Key Features
- [ ] Feature 1
- [ ] Feature 2
- [ ] Feature 3

### Use Cases
1. **Use Case 1**: Description
2. **Use Case 2**: Description
3. **Use Case 3**: Description

## Authentication

### API Key Authentication
```bash
curl -X GET https://api.askproai.de/api/v1/resource \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "X-Company-ID: YOUR_COMPANY_ID"
```

### Rate Limiting
- **Default Limit**: 60 requests per minute
- **Burst Limit**: 100 requests
- **Headers**: `X-RateLimit-Limit`, `X-RateLimit-Remaining`

## Base URL

```
Production: https://api.askproai.de/api/v1
Staging: https://staging-api.askproai.de/api/v1
```

## Endpoints

### 1. List Resources
**GET** `/resources`

Retrieves a paginated list of resources.

#### Request Parameters
| Parameter | Type | Required | Description | Example |
|-----------|------|----------|-------------|---------|
| page | integer | No | Page number (default: 1) | 2 |
| per_page | integer | No | Items per page (default: 15, max: 100) | 50 |
| sort | string | No | Sort field | created_at |
| order | string | No | Sort order (asc/desc) | desc |
| filter[status] | string | No | Filter by status | active |

#### Request Example
```bash
curl -X GET "https://api.askproai.de/api/v1/resources?page=1&per_page=20&filter[status]=active" \
  -H "Authorization: Bearer YOUR_API_KEY"
```

#### Response Example
```json
{
  "data": [
    {
      "id": "res_123",
      "type": "resource",
      "attributes": {
        "name": "Example Resource",
        "status": "active",
        "created_at": "2025-01-10T10:00:00Z",
        "updated_at": "2025-01-10T10:00:00Z"
      },
      "relationships": {
        "owner": {
          "data": {
            "type": "user",
            "id": "usr_456"
          }
        }
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "per_page": 20,
    "to": 20,
    "total": 100
  },
  "links": {
    "first": "https://api.askproai.de/api/v1/resources?page=1",
    "last": "https://api.askproai.de/api/v1/resources?page=5",
    "next": "https://api.askproai.de/api/v1/resources?page=2"
  }
}
```

### 2. Get Single Resource
**GET** `/resources/{id}`

Retrieves a single resource by ID.

#### Path Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| id | string | Yes | Resource ID |

#### Response Codes
| Code | Description |
|------|-------------|
| 200 | Success |
| 404 | Resource not found |
| 401 | Unauthorized |
| 403 | Forbidden |

### 3. Create Resource
**POST** `/resources`

Creates a new resource.

#### Request Body
```json
{
  "data": {
    "type": "resource",
    "attributes": {
      "name": "New Resource",
      "description": "Resource description",
      "settings": {
        "key": "value"
      }
    }
  }
}
```

#### Validation Rules
| Field | Rules | Example |
|-------|-------|---------|
| name | required, string, max:255 | "My Resource" |
| description | optional, string, max:1000 | "Detailed description" |
| settings | optional, json | {"key": "value"} |

### 4. Update Resource
**PATCH** `/resources/{id}`

Updates an existing resource.

### 5. Delete Resource
**DELETE** `/resources/{id}`

Deletes a resource.

## Error Handling

### Error Response Format
```json
{
  "errors": [
    {
      "status": "422",
      "title": "Validation Error",
      "detail": "The name field is required.",
      "source": {
        "pointer": "/data/attributes/name"
      },
      "meta": {
        "field": "name",
        "rule": "required"
      }
    }
  ]
}
```

### Common Error Codes
| Code | Title | Description | Action |
|------|-------|-------------|--------|
| 400 | Bad Request | Invalid request format | Check request syntax |
| 401 | Unauthorized | Missing or invalid API key | Verify API key |
| 403 | Forbidden | Insufficient permissions | Check user permissions |
| 404 | Not Found | Resource doesn't exist | Verify resource ID |
| 422 | Unprocessable Entity | Validation failed | Check validation errors |
| 429 | Too Many Requests | Rate limit exceeded | Wait and retry |
| 500 | Internal Server Error | Server error | Contact support |

## Webhooks

### Webhook Events
- `resource.created`
- `resource.updated`
- `resource.deleted`

### Webhook Payload
```json
{
  "id": "evt_123",
  "type": "resource.created",
  "created": "2025-01-10T10:00:00Z",
  "data": {
    "object": {
      "id": "res_123",
      "type": "resource"
    }
  }
}
```

### Webhook Security
Verify webhook signatures using HMAC-SHA256:
```php
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'];
$expected = hash_hmac('sha256', $payload, $webhook_secret);
$valid = hash_equals($expected, $signature);
```

## SDK Examples

### PHP
```php
use AskProAI\Client;

$client = new Client('YOUR_API_KEY');
$resources = $client->resources()->list([
    'page' => 1,
    'per_page' => 20
]);
```

### JavaScript
```javascript
const client = new AskProAIClient('YOUR_API_KEY');
const resources = await client.resources.list({
    page: 1,
    perPage: 20
});
```

### Python
```python
from askproai import Client

client = Client('YOUR_API_KEY')
resources = client.resources.list(page=1, per_page=20)
```

## Testing

### Test Environment
```
Base URL: https://sandbox-api.askproai.de/api/v1
Test API Key: test_key_...
```

### Postman Collection
[Download Postman Collection](./postman/askproai-api.json)

### Example Test Cases
1. **Authentication Test**
2. **CRUD Operations**
3. **Error Handling**
4. **Rate Limiting**
5. **Webhook Verification**

## Migration Guide

### From v0 to v1
1. Update base URL from `/api/` to `/api/v1/`
2. Change authentication header from `X-API-Key` to `Authorization: Bearer`
3. Update response parsing for JSON:API format

## Changelog

### v1.0.0 (2025-01-10)
- Initial API release
- Added resource endpoints
- Implemented authentication

## Support

- **Documentation**: https://docs.askproai.de
- **Status Page**: https://status.askproai.de
- **Support Email**: api-support@askproai.de
- **Developer Forum**: https://forum.askproai.de

---

> 🔄 **Auto-Updated**: This documentation is automatically checked for updates. Last verification: {TIMESTAMP}