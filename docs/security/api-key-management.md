# API Key Security Management

## Overview
This document describes the secure API key management system implemented for AskProAI, including hashed storage, authentication methods, and security best practices.

## 1. Secure Storage Architecture

### 1.1 Hash-Based Storage
API keys are never stored in plain text. The system uses Laravel's Hash facade with bcrypt:

```php
// Generation
$plainApiKey = 'ask_' . Str::random(32);
$hashedKey = Hash::make($plainApiKey);

// Verification
if (Hash::check($plainApiKey, $tenant->api_key_hash)) {
    // Valid key
}
```

### 1.2 Database Schema
```sql
-- Old (insecure)
api_key VARCHAR(255) -- Plain text storage ❌

-- New (secure)
api_key_hash VARCHAR(255) -- Hashed storage ✅
api_key_generated_at TIMESTAMP
api_key_revoked_at TIMESTAMP
api_key_revoke_reason TEXT
```

### 1.3 Key Format
```
Format: ask_[32-character-random-string]
Example: ask_8x7v2p9k1m3n4b5c6v7a8s9d0f1g2h3j4k5l
Length: 36 characters total
Character set: alphanumeric (a-zA-Z0-9)
```

## 2. Authentication Methods

### 2.1 Recommended: Bearer Token
```http
Authorization: Bearer ask_8x7v2p9k1m3n4b5c6v7a8s9d0f1g2h3j4k5l
```

### 2.2 Deprecated: X-API-Key Header
```http
X-API-Key: ask_8x7v2p9k1m3n4b5c6v7a8s9d0f1g2h3j4k5l
```

### 2.3 Discouraged: Query Parameter
```http
GET /api/endpoint?api_key=ask_8x7v2p9k1m3n4b5c6v7a8s9d0f1g2h3j4k5l
```
⚠️ **Warning**: Query parameters are logged in server logs and may be cached.

## 3. Security Features

### 3.1 Rate Limiting
Protection against brute force attacks:
- **Limit**: 10 authentication attempts per IP per 5 minutes
- **Penalty**: 5-minute cooldown after limit exceeded
- **Logging**: All failed attempts are logged with IP and user agent

```php
// Rate limiting implementation
$rateLimitKey = 'api_auth:' . $request->ip();
if (RateLimiter::tooManyAttempts($rateLimitKey, 10)) {
    return response()->json(['error' => 'Too many attempts'], 429);
}
```

### 3.2 Audit Logging
All API key events are logged:
- Generation and rotation
- Authentication attempts (success/failure)
- Revocation events
- Suspicious patterns

```php
Log::info('API key authenticated', [
    'tenant_id' => $tenant->id,
    'ip' => $request->ip(),
    'user_agent' => $request->userAgent()
]);
```

### 3.3 Secure Key Lookup
```php
// Secure lookup method (verifies hash)
public static function findByApiKey(string $plainKey): ?self
{
    foreach (self::all() as $tenant) {
        if ($tenant->verifyApiKey($plainKey)) {
            return $tenant;
        }
    }
    return null;
}
```

## 4. Key Rotation System

### 4.1 Automatic Rotation Recommendations
- **90 days**: Medium priority recommendation
- **180 days**: High priority recommendation
- **Security event**: Immediate rotation required

### 4.2 Manual Rotation
```php
$apiKeyService = new ApiKeyService();
$newKeyData = $apiKeyService->rotateApiKey($tenant, 'Security incident');
```

### 4.3 Rotation Response
```json
{
  "api_key": "ask_new8x7v2p9k1m3n4b5c6v7a8s9d0f1g2h3j4",
  "key_id": "ask_new8...h3j4",
  "generated_at": "2025-08-14T10:30:00Z",
  "tenant_id": "uuid-tenant-id"
}
```

## 5. Migration from Plain Text

### 5.1 Migration Process
The system automatically migrates existing plain text keys:

```php
// Migration logic
Tenant::whereNotNull('api_key')->chunk(100, function ($tenants) {
    foreach ($tenants as $tenant) {
        if ($tenant->api_key && empty($tenant->api_key_hash)) {
            $tenant->api_key_hash = Hash::make($tenant->api_key);
            $tenant->save();
        }
    }
});
```

### 5.2 Zero-Downtime Migration
1. Add `api_key_hash` column
2. Populate hashes from existing keys
3. Update authentication to use hashes
4. Remove plain text `api_key` column

## 6. API Endpoints for Key Management

### 6.1 Generate New Key
```http
POST /api/admin/tenants/{tenant}/api-key
Content-Type: application/json
Authorization: Bearer admin_token

Response:
{
  "api_key": "ask_8x7v2p9k1m3n4b5c6v7a8s9d0f1g2h3j4k5l",
  "key_id": "ask_8x7v...h3j4k5l",
  "generated_at": "2025-08-14T10:30:00Z"
}
```

### 6.2 Rotate Existing Key
```http
PUT /api/admin/tenants/{tenant}/api-key/rotate
Content-Type: application/json
Authorization: Bearer admin_token

{
  "reason": "Scheduled rotation"
}
```

### 6.3 Revoke Key
```http
DELETE /api/admin/tenants/{tenant}/api-key
Content-Type: application/json
Authorization: Bearer admin_token

{
  "reason": "Security incident"
}
```

### 6.4 Key Statistics
```http
GET /api/admin/tenants/{tenant}/api-key/stats
Authorization: Bearer admin_token

Response:
{
  "key_age_days": 45,
  "should_rotate": false,
  "last_used_at": "2025-08-14T09:15:00Z",
  "request_count_30d": 1250
}
```

## 7. Security Best Practices

### 7.1 For Developers
- **Never log** complete API keys
- **Use HTTPS** for all API communications
- **Rotate keys** regularly (every 90 days)
- **Revoke immediately** if compromised

### 7.2 For API Consumers
- **Store securely** in environment variables
- **Use Bearer token** authentication method
- **Monitor usage** for unusual patterns
- **Implement retry logic** for rate limits

### 7.3 For Administrators
- **Monitor audit logs** for suspicious activity
- **Set up alerts** for failed authentication attempts
- **Review key age** monthly
- **Maintain incident response** procedures

## 8. Incident Response

### 8.1 Suspected Key Compromise
1. **Immediate**: Revoke compromised key
2. **Generate**: New API key for tenant
3. **Notify**: Tenant of security incident
4. **Investigate**: Source of compromise
5. **Document**: Incident and response

### 8.2 Brute Force Detection
```php
// Alert trigger
if (RateLimiter::attempts($rateLimitKey) >= 5) {
    Alert::create([
        'type' => 'security',
        'severity' => 'high',
        'message' => 'Potential brute force attack detected',
        'ip_address' => $request->ip()
    ]);
}
```

## 9. Compliance and Auditing

### 9.1 DSGVO/GDPR Compliance
- API keys are pseudonymous identifiers
- Access logs are retained for 90 days
- Key generation events are auditable
- Tenant consent required for key generation

### 9.2 Audit Requirements
- **Key lifecycle**: Generation, rotation, revocation
- **Authentication events**: Success and failure
- **Usage patterns**: Frequency, endpoints accessed
- **Security events**: Rate limiting, suspicious activity

## 10. Performance Considerations

### 10.1 Hash Verification Optimization
Current implementation requires checking all tenant hashes for unknown keys. For large-scale deployments, consider:

- **Key prefixes** with tenant ID hints
- **Caching layers** for recently verified keys
- **Database indexing** strategies
- **Horizontal scaling** of authentication service

### 10.2 Monitoring Metrics
- Authentication request volume
- Hash verification time
- Rate limiting trigger frequency
- Key rotation compliance rate

## 11. Future Enhancements

### 11.1 Planned Features
- **JWT-based tokens** for stateless authentication
- **Key scoping** for granular permissions
- **Automatic rotation** based on usage patterns
- **Multi-key support** per tenant

### 11.2 Integration Improvements
- **Hardware Security Modules** (HSM) for key storage
- **OAuth 2.0** for third-party integrations
- **API key versioning** for smooth migrations
- **Real-time key validation** service