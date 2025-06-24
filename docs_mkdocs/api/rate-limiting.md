# Rate Limiting

## Overview

AskProAI implements intelligent rate limiting to ensure fair usage and platform stability. Rate limits are applied per API token and are automatically adjusted based on your subscription tier.

## Rate Limit Headers

All API responses include rate limit information:

```http
HTTP/1.1 200 OK
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 999
X-RateLimit-Reset: 1719151200
X-RateLimit-Reset-After: 3600
X-RateLimit-Resource: api
```

### Header Definitions

| Header | Description | Example |
|--------|-------------|---------|
| `X-RateLimit-Limit` | Maximum requests allowed in window | `1000` |
| `X-RateLimit-Remaining` | Requests remaining in current window | `999` |
| `X-RateLimit-Reset` | Unix timestamp when limit resets | `1719151200` |
| `X-RateLimit-Reset-After` | Seconds until limit resets | `3600` |
| `X-RateLimit-Resource` | Resource type being limited | `api` |

## Rate Limit Tiers

### Standard Tier (Free)
- **API Requests**: 1,000 per hour
- **Webhook Events**: 500 per hour
- **Concurrent Requests**: 10
- **Burst Limit**: 20 requests per minute

### Professional Tier
- **API Requests**: 5,000 per hour
- **Webhook Events**: 2,000 per hour
- **Concurrent Requests**: 25
- **Burst Limit**: 100 requests per minute

### Enterprise Tier
- **API Requests**: Custom (default 50,000 per hour)
- **Webhook Events**: Unlimited
- **Concurrent Requests**: 100
- **Burst Limit**: 1,000 requests per minute

## Endpoint-Specific Limits

Some endpoints have additional restrictions:

### High-Impact Endpoints

| Endpoint | Additional Limit | Reason |
|----------|-----------------|---------|
| `POST /appointments` | 100 per hour | Prevent spam bookings |
| `POST /api/mcp/database/query` | 500 per hour | Database protection |
| `POST /calls/import` | 10 per hour | Resource intensive |
| `POST /webhooks/reprocess` | 50 per hour | Processing overhead |

### Read vs Write Operations

- **Read operations**: Standard rate limits apply
- **Write operations**: 20% of read limit
- **Delete operations**: 10% of read limit

## Rate Limit Algorithms

### Sliding Window Algorithm

We use a sliding window algorithm for accurate rate limiting:

```
Request at 10:15:30 -> Check requests from 09:15:30 to 10:15:30
```

### Token Bucket for Bursts

Burst protection uses token bucket algorithm:
- Tokens replenish at steady rate
- Each request consumes one token
- Burst allowed up to bucket capacity

## Handling Rate Limits

### 429 Too Many Requests

When rate limited, you'll receive:

```json
{
  "error": "Too Many Requests",
  "message": "Rate limit exceeded",
  "code": "RATE_001",
  "retry_after": 3600,
  "limit": 1000,
  "remaining": 0,
  "reset": "2025-06-23T14:00:00Z"
}
```

### Retry Strategy

Implement exponential backoff:

```javascript
async function makeRequestWithRetry(url, options, maxRetries = 3) {
  for (let i = 0; i < maxRetries; i++) {
    try {
      const response = await fetch(url, options);
      
      if (response.status === 429) {
        const retryAfter = response.headers.get('Retry-After') || 60;
        const delay = parseInt(retryAfter) * 1000 * Math.pow(2, i);
        
        console.log(`Rate limited. Retrying after ${delay}ms`);
        await new Promise(resolve => setTimeout(resolve, delay));
        continue;
      }
      
      return response;
    } catch (error) {
      if (i === maxRetries - 1) throw error;
    }
  }
}
```

### PHP Example

```php
function makeRequestWithRetry($client, $method, $uri, $options = [], $maxRetries = 3) {
    $attempt = 0;
    
    while ($attempt < $maxRetries) {
        try {
            return $client->request($method, $uri, $options);
        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 429) {
                $retryAfter = $e->getResponse()->getHeader('Retry-After')[0] ?? 60;
                $delay = $retryAfter * pow(2, $attempt);
                
                sleep($delay);
                $attempt++;
                continue;
            }
            throw $e;
        }
    }
}
```

## Best Practices

### 1. Monitor Your Usage

Track your rate limit consumption:

```javascript
function logRateLimitHeaders(response) {
  const remaining = response.headers.get('X-RateLimit-Remaining');
  const limit = response.headers.get('X-RateLimit-Limit');
  const usage = ((limit - remaining) / limit) * 100;
  
  console.log(`Rate limit usage: ${usage.toFixed(2)}%`);
  
  if (usage > 80) {
    console.warn('Approaching rate limit!');
  }
}
```

### 2. Implement Caching

Reduce API calls by caching responses:

```php
$cacheKey = 'appointments_' . md5($request->getUri());
$cached = Cache::get($cacheKey);

if ($cached) {
    return $cached;
}

$response = $client->get('/appointments');
Cache::put($cacheKey, $response, 300); // Cache for 5 minutes
```

### 3. Use Webhooks

Instead of polling, use webhooks for real-time updates:

```json
{
  "url": "https://your-app.com/webhook",
  "events": ["appointment.created", "appointment.updated"]
}
```

### 4. Batch Operations

Use batch endpoints when available:

```http
POST /api/v2/appointments/batch
{
  "appointments": [
    { "service_id": 1, "start_time": "..." },
    { "service_id": 2, "start_time": "..." }
  ]
}
```

## Rate Limit Exceptions

### Whitelisted IPs

Enterprise customers can whitelist IPs for higher limits:

```bash
POST /api/admin/whitelist
{
  "ip_addresses": ["192.168.1.1", "10.0.0.1"],
  "rate_limit_multiplier": 2
}
```

### Development Mode

In development, use relaxed limits:

```bash
# Add to .env
RATE_LIMIT_ENABLED=false
RATE_LIMIT_TEST_MODE=true
```

## Monitoring Dashboard

View your rate limit usage:

1. Login to admin panel
2. Navigate to **API → Rate Limits**
3. View real-time usage graphs
4. Set up alerts for high usage

## Rate Limit Increases

To request a rate limit increase:

1. Contact support with your use case
2. Provide current usage statistics
3. Explain expected growth
4. Consider upgrading your plan

## Common Issues

### "Rate limit exceeded" but counter shows remaining

**Cause**: Burst limit reached
**Solution**: Spread requests over time

### Different rate limits than documented

**Cause**: Account-specific limits
**Solution**: Check `/api/account/limits`

### Rate limit resets not working

**Cause**: Clock synchronization
**Solution**: Ensure server time is correct

## Testing Rate Limits

Test endpoint to check your limits:

```http
GET /api/rate-limit/test
```

Response:
```json
{
  "tier": "professional",
  "limits": {
    "hourly": 5000,
    "remaining": 4521,
    "reset_at": "2025-06-23T14:00:00Z"
  },
  "usage": {
    "last_hour": 479,
    "last_24h": 8234,
    "last_7d": 45234
  }
}
```