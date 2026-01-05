# Rate Limiting

AskPro API Gateway implements rate limiting to ensure fair usage and system stability.

## Default Limits

| Endpoint Type | Limit | Window |
|---------------|-------|--------|
| General API | 60 requests | 1 minute |
| Authentication | 5 requests | 1 minute |
| Webhooks | 100 requests | 1 minute |
| Bulk Operations | 10 requests | 1 minute |

## Rate Limit Headers

All API responses include rate limit information:

```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1699900000
```

| Header | Description |
|--------|-------------|
| `X-RateLimit-Limit` | Maximum requests per window |
| `X-RateLimit-Remaining` | Requests remaining in current window |
| `X-RateLimit-Reset` | Unix timestamp when limit resets |

## Rate Limit Exceeded

When rate limit is exceeded, the API returns:

```
HTTP/1.1 429 Too Many Requests
Retry-After: 45
Content-Type: application/json

{
  "error": "Too Many Requests",
  "message": "Rate limit exceeded. Please retry after 45 seconds.",
  "retry_after": 45
}
```

## Endpoint-Specific Limits

### Appointments

| Endpoint | Method | Limit |
|----------|--------|-------|
| `/api/v1/appointments` | GET | 60/min |
| `/api/v1/appointments` | POST | 30/min |
| `/api/v1/appointments/{id}` | PUT | 30/min |
| `/api/v1/appointments/{id}` | DELETE | 10/min |

### Availability

| Endpoint | Method | Limit |
|----------|--------|-------|
| `/api/v1/availability` | GET | 120/min |
| `/api/v1/availability/slots` | GET | 60/min |

### Service Cases

| Endpoint | Method | Limit |
|----------|--------|-------|
| `/api/v1/service-cases` | GET | 60/min |
| `/api/v1/service-cases` | POST | 20/min |

## Rate Limiting by Key

Rate limits are applied per:

1. **API Token** - Each token has independent limits
2. **IP Address** - For unauthenticated requests
3. **User Account** - Combined across all tokens

```
# Token A: 60 requests/min
# Token B: 60 requests/min (separate limit)
# Same user, both tokens: 120 total available
```

## Handling Rate Limits

### Retry Logic

Implement exponential backoff:

```javascript
async function apiCall(url, options, retries = 3) {
  for (let i = 0; i < retries; i++) {
    const response = await fetch(url, options);

    if (response.status === 429) {
      const retryAfter = response.headers.get('Retry-After') || 60;
      await sleep(retryAfter * 1000 * Math.pow(2, i));
      continue;
    }

    return response;
  }
  throw new Error('Rate limit exceeded after retries');
}
```

### PHP Example

```php
use Illuminate\Support\Facades\Http;

function makeApiCall(string $url, int $retries = 3): array
{
    for ($i = 0; $i < $retries; $i++) {
        $response = Http::withToken($token)->get($url);

        if ($response->status() === 429) {
            $retryAfter = $response->header('Retry-After') ?? 60;
            sleep($retryAfter * pow(2, $i));
            continue;
        }

        return $response->json();
    }

    throw new RateLimitException('Rate limit exceeded');
}
```

### Python Example

```python
import time
import requests

def api_call(url, headers, retries=3):
    for i in range(retries):
        response = requests.get(url, headers=headers)

        if response.status_code == 429:
            retry_after = int(response.headers.get('Retry-After', 60))
            time.sleep(retry_after * (2 ** i))
            continue

        return response.json()

    raise Exception('Rate limit exceeded after retries')
```

## Best Practices

### 1. Monitor Rate Limit Headers

Check remaining requests before making calls:

```javascript
const remaining = response.headers.get('X-RateLimit-Remaining');
if (remaining < 10) {
  // Slow down requests
  await sleep(1000);
}
```

### 2. Use Caching

Cache responses to reduce API calls:

```php
$cacheKey = "appointments:{$date}";
$appointments = Cache::remember($cacheKey, 300, function () use ($date) {
    return $this->api->getAppointments($date);
});
```

### 3. Batch Requests

Use bulk endpoints when available:

```bash
# Instead of 10 individual requests:
GET /api/v1/appointments/1
GET /api/v1/appointments/2
# ...

# Use one bulk request:
GET /api/v1/appointments?ids=1,2,3,4,5,6,7,8,9,10
```

### 4. Implement Queuing

Queue requests during high load:

```php
// Queue API calls
dispatch(new SyncAppointmentJob($appointment))->onQueue('api-sync');

// Process queue at controlled rate
php artisan queue:work --sleep=1 --max-jobs=60
```

## Enterprise Limits

Contact sales for increased rate limits:

| Plan | Requests/min | Support |
|------|--------------|---------|
| Standard | 60 | Email |
| Professional | 300 | Priority |
| Enterprise | Custom | Dedicated |

## Webhook Rate Limits

Outgoing webhooks have separate limits:

| Destination | Limit |
|-------------|-------|
| Per URL | 100/min |
| Per Company | 500/min |
| System Total | 5000/min |

Failed webhooks are retried with backoff:

```
Attempt 1: Immediate
Attempt 2: After 60s
Attempt 3: After 120s
Attempt 4: After 300s
Attempt 5: After 600s
```

## Monitoring

### Dashboard Metrics

The admin dashboard shows:
- Current rate limit usage
- Historical request patterns
- Rate limit violations

### Alerts

Configure alerts for:
- Rate limit approaching (>80%)
- Rate limit exceeded
- Unusual traffic patterns
