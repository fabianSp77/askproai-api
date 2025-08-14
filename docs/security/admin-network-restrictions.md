# Admin Network Access Restrictions

## Overview
This document describes the network-level security restrictions implemented for admin panels and sensitive management interfaces in the AskProAI system.

## 🔒 Protected Endpoints

### Administrative Interfaces
| Endpoint | Purpose | Restriction Level |
|----------|---------|------------------|
| `/admin` | Filament Admin Panel | **Internal Only** |
| `/admin-v2` | AdminV2 Portal | **Internal Only** |
| `/horizon` | Laravel Horizon Dashboard | **Internal + Auth** |

### Internal Network Definition
Access is restricted to these IP ranges:

```php
// Private IPv4 ranges (RFC 1918)
'10.0.0.0/8',           // Class A: 10.0.0.0 - 10.255.255.255
'172.16.0.0/12',        // Class B: 172.16.0.0 - 172.31.255.255
'192.168.0.0/16',       // Class C: 192.168.0.0 - 192.168.255.255

// Loopback addresses
'127.0.0.0/8',          // IPv4 loopback
'::1/128',              // IPv6 loopback

// Link-local addresses  
'169.254.0.0/16',       // IPv4 link-local
'fe80::/10',            // IPv6 link-local
```

## 🛡️ Security Implementation

### Middleware Protection
```php
// RestrictToInternalNetwork middleware
class RestrictToInternalNetwork
{
    public function handle(Request $request, Closure $next): Response
    {
        $clientIp = $this->getClientIp($request);
        
        if (!$this->isAllowedIp($clientIp)) {
            // Return 404 to hide admin panel existence
            return response()->view('errors.404', [], 404);
        }
        
        return $next($request);
    }
}
```

### Applied Protection
- **Filament Admin**: `middleware(['web', 'restrict.internal'])`
- **AdminV2 Routes**: `middleware(['web', 'restrict.internal'])`
- **Laravel Horizon**: Custom auth with network check

## 🌐 Proxy and Load Balancer Support

### Trusted Proxy Headers
The middleware correctly handles proxy scenarios by checking headers in order:

```php
$headers = [
    'HTTP_CF_CONNECTING_IP',     // Cloudflare
    'HTTP_X_REAL_IP',           // Nginx proxy  
    'HTTP_X_FORWARDED_FOR',     // Standard proxy
    'HTTP_X_CLUSTER_CLIENT_IP', // Kubernetes
    'REMOTE_ADDR'               // Direct connection
];
```

### Configuration for Production
```php
// config/trustedproxy.php
'proxies' => [
    '10.0.0.0/8',      // Internal load balancers
    '172.16.0.0/12',   // Docker networks
],

'headers' => [
    Request::HEADER_X_FORWARDED_FOR,
    Request::HEADER_X_FORWARDED_HOST,
    Request::HEADER_X_FORWARDED_PROTO,
],
```

## 🚨 Emergency Access

### Emergency Override Token
For critical situations, emergency access can be granted:

```bash
# Set emergency token in .env
APP_EMERGENCY_ADMIN_TOKEN=emergency_$(openssl rand -hex 16)
```

Usage:
```http
GET /admin?emergency_override=emergency_abc123def456
# OR
X-Emergency-Override: emergency_abc123def456
```

**⚠️ Warning**: Emergency access is logged as critical events.

### Development Environment
In local/development environments, restrictions are more permissive:
```php
if (app()->environment(['local', 'development'])) {
    // Allow broader access for development
}
```

## 📊 Monitoring and Logging

### Access Attempt Logging
```php
// Successful access
Log::info('Admin access allowed', [
    'ip' => $clientIp,
    'path' => $request->path(),
    'user_agent' => $request->userAgent()
]);

// Blocked access  
Log::warning('Admin access blocked - external IP', [
    'ip' => $clientIp,
    'path' => $request->path(),
    'user_agent' => $request->userAgent(),
    'referer' => $request->header('Referer')
]);
```

### Security Alerts
Automated alerts are triggered for:
- Multiple blocked attempts from same IP
- Emergency override usage
- Suspicious user agent patterns
- Repeated 404 responses to admin paths

## 🔧 Configuration Management

### Environment Variables
```env
# Admin access configuration
ADMIN_ALLOWED_IPS=10.0.0.1,192.168.1.100
ADMIN_NETWORK_RESTRICTIONS_ENABLED=true
APP_EMERGENCY_ADMIN_TOKEN=emergency_token_here

# Horizon dashboard
HORIZON_DASHBOARD_ENABLED=false
```

### Runtime Configuration
```php
// config/admin.php
return [
    'network_restrictions' => [
        'enabled' => env('ADMIN_NETWORK_RESTRICTIONS_ENABLED', true),
        'allowed_ips' => explode(',', env('ADMIN_ALLOWED_IPS', '')),
        'allowed_ranges' => [
            '10.0.0.0/8',
            '172.16.0.0/12', 
            '192.168.0.0/16',
        ],
    ],
];
```

## 🧪 Testing Access Restrictions

### Test Internal Access
```bash
# From internal network (should work)
curl -I http://localhost/admin
# Expected: 302 redirect to login

# Test AdminV2 access
curl -I http://localhost/admin-v2/portal  
# Expected: 200 OK
```

### Test External Blocking
```bash
# Simulate external IP (should be blocked)
curl -H "X-Forwarded-For: 203.0.113.42" -I http://localhost/admin
# Expected: 404 Not Found

# Test Horizon blocking
curl -H "X-Forwarded-For: 203.0.113.42" -I http://localhost/horizon
# Expected: 403 Forbidden or 404
```

### Automated Tests
```php
// tests/Feature/AdminAccessTest.php
public function test_admin_panel_blocks_external_access(): void
{
    $response = $this->withServerVariables([
        'REMOTE_ADDR' => '203.0.113.42' // External IP
    ])->get('/admin');
    
    $response->assertStatus(404);
}

public function test_admin_panel_allows_internal_access(): void  
{
    $response = $this->withServerVariables([
        'REMOTE_ADDR' => '192.168.1.100' // Internal IP
    ])->get('/admin');
    
    $response->assertRedirect('/admin/login');
}
```

## 🏢 Office Network Configuration

### VPN Setup
For remote admin access, configure VPN to provide internal IP addresses:

```bash
# OpenVPN server config
server 10.8.0.0 255.255.255.0
push "route 192.168.1.0 255.255.255.0"
```

### Office Network Example
```
Internet ─── Firewall ─── Switch ─── Admin Workstations
                │              │
                │              └─ 192.168.1.0/24  
                │
                └─ DMZ ─── Web Servers (10.0.1.0/24)
```

## 🚦 Bypass Scenarios

### Legitimate External Access
For situations requiring external admin access:

1. **VPN Connection**: Recommended approach
2. **IP Whitelisting**: Add specific IPs to allowed list
3. **Emergency Override**: For critical situations only
4. **Temporary Access**: Time-limited IP additions

### Implementation
```php
// Add temporary external IP
public function addTemporaryAccess(string $ip, int $hours = 24): void
{
    Cache::put("temp_admin_ip:{$ip}", true, now()->addHours($hours));
    
    Log::info('Temporary admin access granted', [
        'ip' => $ip,
        'expires_at' => now()->addHours($hours),
    ]);
}
```

## 📋 Maintenance Procedures

### Adding New Admin IPs
1. Update environment configuration
2. Clear application cache
3. Test access from new IP
4. Document change in security log

### Regular Security Reviews
- **Weekly**: Review blocked access attempts
- **Monthly**: Audit allowed IP ranges
- **Quarterly**: Review and update security policies

### Incident Response
1. **Suspicious Activity**: Block IP immediately
2. **Breach Detected**: Revoke emergency tokens
3. **False Positives**: Add legitimate IPs to whitelist

## 🔍 Troubleshooting

### Common Issues

#### "404 Not Found" on Admin Panel
**Cause**: External IP attempting access
**Solution**: Connect via VPN or add IP to whitelist

#### Admin Panel Not Loading
**Cause**: Proxy configuration issues
**Solution**: Check trusted proxy settings

#### Emergency Override Not Working  
**Cause**: Token mismatch or expiration
**Solution**: Regenerate emergency token

### Debug Mode
```php
// Enable detailed logging for troubleshooting
Log::debug('IP check details', [
    'client_ip' => $clientIp,
    'detected_method' => $this->getIpDetectionMethod($request),
    'allowed_ranges' => $this->allowedRanges,
    'is_allowed' => $this->isAllowedIp($clientIp),
]);
```

## 📚 Related Documentation
- [Security Overview](./overview.md)
- [Nginx Hardening](./nginx-hardening.md)
- [API Key Management](./api-key-management.md)
- [DSGVO Compliance](../compliance/dsgvo-compliance.md)

---

**Security Level**: 🔴 Critical  
**Last Updated**: August 14, 2025  
**Review Schedule**: Monthly