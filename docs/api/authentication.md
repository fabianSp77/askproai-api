# API Authentication

AskPro API Gateway supports multiple authentication methods for different use cases.

## Authentication Methods

| Method | Use Case | Header |
|--------|----------|--------|
| Bearer Token | API integrations | `Authorization: Bearer {token}` |
| API Key | Service-to-service | `X-API-Key: {key}` |
| Session | Admin panel (web) | Cookie-based |

## Bearer Token Authentication

### Obtaining a Token

```bash
POST /api/v1/auth/token
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "your_password"
}
```

**Response:**

```json
{
  "access_token": "1|abcdef123456...",
  "token_type": "Bearer",
  "expires_at": "2024-02-15T12:00:00Z"
}
```

### Using the Token

Include the token in the Authorization header:

```bash
curl -X GET "https://api.askproai.de/api/v1/appointments" \
  -H "Authorization: Bearer 1|abcdef123456..." \
  -H "Accept: application/json"
```

### Token Scopes

Tokens can be created with specific scopes:

| Scope | Permissions |
|-------|-------------|
| `read:appointments` | View appointments |
| `write:appointments` | Create/update appointments |
| `read:customers` | View customer data |
| `write:customers` | Create/update customers |
| `admin` | Full administrative access |

```bash
POST /api/v1/auth/token
{
  "email": "user@example.com",
  "password": "your_password",
  "scopes": ["read:appointments", "read:customers"]
}
```

### Token Expiration

- Default expiration: 30 days
- Custom expiration: Set `expires_in` (seconds)
- Maximum: 365 days

```bash
POST /api/v1/auth/token
{
  "email": "user@example.com",
  "password": "your_password",
  "expires_in": 86400  // 24 hours
}
```

### Revoking Tokens

```bash
DELETE /api/v1/auth/token
Authorization: Bearer {token}
```

## API Key Authentication

### Creating an API Key

API keys are created in the admin panel:

1. Navigate to Settings → API Keys
2. Click "Create API Key"
3. Set name and permissions
4. Copy the key (shown only once)

### Using API Keys

```bash
curl -X GET "https://api.askproai.de/api/v1/appointments" \
  -H "X-API-Key: your_api_key_here" \
  -H "Accept: application/json"
```

### API Key vs Bearer Token

| Feature | API Key | Bearer Token |
|---------|---------|--------------|
| Expiration | Never (until revoked) | Configurable |
| User context | Service account | User account |
| Scopes | Pre-configured | Per-request |
| Best for | Server-to-server | User applications |

## Webhook Authentication

### HMAC Signature Verification

Incoming webhooks include a signature for verification:

```php
// Retell webhooks
$signature = $request->header('X-Retell-Signature');
$expected = hash_hmac('sha256', $payload, $webhookSecret);
$isValid = hash_equals($expected, $signature);

// Cal.com webhooks
$signature = $request->header('X-Cal-Signature-256');
$expected = hash_hmac('sha256', $payload, $webhookSecret);
$isValid = hash_equals($expected, $signature);
```

## Error Responses

### 401 Unauthorized

```json
{
  "error": "Unauthorized",
  "message": "Invalid or missing authentication token"
}
```

Causes:
- Missing Authorization header
- Invalid token
- Expired token
- Revoked token

### 403 Forbidden

```json
{
  "error": "Forbidden",
  "message": "Insufficient permissions for this action"
}
```

Causes:
- Token lacks required scope
- User role doesn't permit action
- Resource belongs to different tenant

## Multi-Tenant Context

All API requests are scoped to the authenticated user's company:

```php
// Automatic scoping
GET /api/v1/appointments
// Returns only appointments for the user's company

// No cross-tenant access possible
GET /api/v1/appointments?company_id=other_company
// company_id parameter is ignored - always uses auth context
```

## Security Best Practices

### Token Storage

- **Never** store tokens in client-side code
- Use secure HTTP-only cookies for web apps
- Use secure storage (Keychain/Keystore) for mobile apps
- Encrypt tokens at rest in server applications

### Token Rotation

```bash
# Create new token before old one expires
POST /api/v1/auth/token/refresh
Authorization: Bearer {current_token}
```

### Minimum Scopes

Request only the scopes you need:

```bash
# Bad - requesting all permissions
POST /api/v1/auth/token
{ "scopes": ["admin"] }

# Good - requesting minimal permissions
POST /api/v1/auth/token
{ "scopes": ["read:appointments"] }
```

## Code Examples

### PHP (Laravel)

```php
use Illuminate\Support\Facades\Http;

$response = Http::withToken($apiToken)
    ->get('https://api.askproai.de/api/v1/appointments');

$appointments = $response->json();
```

### JavaScript (Node.js)

```javascript
const response = await fetch('https://api.askproai.de/api/v1/appointments', {
  headers: {
    'Authorization': `Bearer ${apiToken}`,
    'Accept': 'application/json',
  },
});

const appointments = await response.json();
```

### Python

```python
import requests

headers = {
    'Authorization': f'Bearer {api_token}',
    'Accept': 'application/json',
}

response = requests.get(
    'https://api.askproai.de/api/v1/appointments',
    headers=headers
)

appointments = response.json()
```

### cURL

```bash
curl -X GET "https://api.askproai.de/api/v1/appointments" \
  -H "Authorization: Bearer your_token_here" \
  -H "Accept: application/json"
```
