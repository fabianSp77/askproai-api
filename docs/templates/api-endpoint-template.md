# API Endpoint: [Endpoint Name]

## 📋 Overview
**Endpoint**: `[METHOD] /api/v1/[path]`  
**Version**: v1  
**Status**: 🟢 Active | 🟡 Beta | 🔴 Deprecated  
**Rate Limit**: [X requests/minute]  

### Purpose
[What this endpoint does]

## 🔐 Authentication
```http
Authorization: Bearer {token}
```

**Required Scopes**: 
- `scope.read`
- `scope.write`

## 📥 Request

### Headers
```http
Content-Type: application/json
Accept: application/json
X-Company-ID: {company_id} (optional)
```

### Parameters

#### Path Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | Resource ID |

#### Query Parameters
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | integer | 1 | Page number |
| `per_page` | integer | 20 | Items per page |
| `sort` | string | created_at | Sort field |
| `order` | string | desc | Sort order (asc/desc) |

#### Request Body
```json
{
    "field1": "string",
    "field2": 123,
    "field3": {
        "nested": "object"
    },
    "field4": ["array", "of", "values"]
}
```

### Validation Rules
```php
[
    'field1' => 'required|string|max:255',
    'field2' => 'required|integer|min:1',
    'field3' => 'required|array',
    'field3.nested' => 'required|string',
    'field4' => 'array',
    'field4.*' => 'string',
]
```

## 📤 Response

### Success Response (200 OK)
```json
{
    "success": true,
    "data": {
        "id": 123,
        "field1": "value",
        "field2": 456,
        "created_at": "2025-01-10T10:00:00Z",
        "updated_at": "2025-01-10T10:00:00Z"
    },
    "meta": {
        "version": "1.0",
        "request_id": "uuid"
    }
}
```

### Paginated Response
```json
{
    "success": true,
    "data": [...],
    "links": {
        "first": "https://api.askproai.de/api/v1/resource?page=1",
        "last": "https://api.askproai.de/api/v1/resource?page=10",
        "prev": null,
        "next": "https://api.askproai.de/api/v1/resource?page=2"
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 10,
        "path": "https://api.askproai.de/api/v1/resource",
        "per_page": 20,
        "to": 20,
        "total": 200
    }
}
```

## ❌ Error Responses

### 400 Bad Request
```json
{
    "success": false,
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "The given data was invalid.",
        "errors": {
            "field1": ["The field1 field is required."]
        }
    }
}
```

### 401 Unauthorized
```json
{
    "success": false,
    "error": {
        "code": "UNAUTHORIZED",
        "message": "Unauthenticated."
    }
}
```

### 403 Forbidden
```json
{
    "success": false,
    "error": {
        "code": "FORBIDDEN",
        "message": "You do not have permission to access this resource."
    }
}
```

### 404 Not Found
```json
{
    "success": false,
    "error": {
        "code": "NOT_FOUND",
        "message": "Resource not found."
    }
}
```

### 429 Too Many Requests
```json
{
    "success": false,
    "error": {
        "code": "RATE_LIMIT_EXCEEDED",
        "message": "Too many requests."
    },
    "meta": {
        "retry_after": 60
    }
}
```

### 500 Internal Server Error
```json
{
    "success": false,
    "error": {
        "code": "INTERNAL_ERROR",
        "message": "An unexpected error occurred.",
        "request_id": "uuid"
    }
}
```

## 🔄 Examples

### cURL
```bash
curl -X POST https://api.askproai.de/api/v1/resource \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "field1": "value",
    "field2": 123
  }'
```

### PHP
```php
$response = Http::withToken($token)
    ->post('https://api.askproai.de/api/v1/resource', [
        'field1' => 'value',
        'field2' => 123,
    ]);
```

### JavaScript
```javascript
const response = await fetch('https://api.askproai.de/api/v1/resource', {
    method: 'POST',
    headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        field1: 'value',
        field2: 123,
    }),
});

const data = await response.json();
```

### Python
```python
import requests

response = requests.post(
    'https://api.askproai.de/api/v1/resource',
    headers={
        'Authorization': f'Bearer {token}',
        'Content-Type': 'application/json',
    },
    json={
        'field1': 'value',
        'field2': 123,
    }
)

data = response.json()
```

## 🧪 Testing

### Postman Collection
Import the collection: [Download Postman Collection](./postman/endpoint-name.json)

### Test Scenarios
1. **Happy Path**: Valid request with all required fields
2. **Validation Error**: Missing required fields
3. **Authentication Error**: Invalid or missing token
4. **Not Found**: Non-existent resource ID
5. **Rate Limit**: Exceed rate limit

## 🔗 Webhooks

This endpoint may trigger the following webhooks:
- `resource.created` - When a new resource is created
- `resource.updated` - When a resource is updated
- `resource.deleted` - When a resource is deleted

## 📊 Performance

### Response Times
- **Average**: 50ms
- **P95**: 100ms
- **P99**: 200ms

### Tips for Optimization
1. Use field filtering: `?fields=id,name`
2. Implement pagination for large datasets
3. Cache responses when possible

## 🔄 Changelog

### v1.0.0 (2025-01-10)
- Initial release

### v0.9.0 (2025-01-01)
- Beta version
- Added field validation

## 🤝 Related Endpoints
- `GET /api/v1/resource` - List resources
- `GET /api/v1/resource/{id}` - Get single resource
- `PUT /api/v1/resource/{id}` - Update resource
- `DELETE /api/v1/resource/{id}` - Delete resource

## 📚 Additional Resources
- [API Authentication Guide](./authentication.md)
- [Rate Limiting Documentation](./rate-limiting.md)
- [Webhook Documentation](./webhooks.md)
- [Error Codes Reference](./error-codes.md)