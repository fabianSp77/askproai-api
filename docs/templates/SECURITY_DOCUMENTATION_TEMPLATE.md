# Security Documentation Template

> 🔒 **Classification**: {CONFIDENTIAL/INTERNAL/PUBLIC}  
> 📅 **Last Security Review**: {DATE}  
> 👥 **Security Team**: {SECURITY_TEAM}  
> 🚨 **Security Contact**: security@askproai.de

## Security Overview

### Security Principles
1. **Defense in Depth**: Multiple layers of security controls
2. **Least Privilege**: Minimal access rights required
3. **Zero Trust**: Verify everything, trust nothing
4. **Security by Design**: Built-in, not bolted-on

### Compliance Standards
- [ ] GDPR (General Data Protection Regulation)
- [ ] ISO 27001 (Information Security Management)
- [ ] SOC 2 Type II (Service Organization Control)
- [ ] PCI DSS (If processing payments)

## Authentication & Authorization

### Authentication Methods
| Method | Use Case | Security Level | MFA Required |
|--------|----------|----------------|--------------|
| API Key | Service-to-service | High | No |
| JWT Token | User sessions | High | Yes |
| OAuth 2.0 | Third-party | High | Depends |
| Basic Auth | Legacy only | Medium | No |

### Password Requirements
```php
// Password validation rules
'password' => [
    'required',
    'string',
    'min:12',
    'regex:/[a-z]/',      // lowercase
    'regex:/[A-Z]/',      // uppercase
    'regex:/[0-9]/',      // number
    'regex:/[@$!%*?&]/',  // special character
    'not_compromised',    // Check against known breaches
]
```

### Multi-Factor Authentication (MFA)
```php
// MFA implementation
class MFAService
{
    public function generateSecret(): string
    {
        return Google2FA::generateSecretKey();
    }
    
    public function verifyToken(string $secret, string $token): bool
    {
        return Google2FA::verifyKey($secret, $token);
    }
}
```

### Session Management
- Session timeout: 30 minutes (configurable)
- Concurrent sessions: Limited to 3
- Session invalidation on password change
- Secure session cookies (HttpOnly, Secure, SameSite)

## Data Protection

### Encryption at Rest
```php
// Database encryption
class EncryptedModel extends Model
{
    protected $encrypted = [
        'api_key',
        'personal_data',
        'sensitive_config',
    ];
    
    protected function encryptAttribute($value): string
    {
        return Crypt::encryptString($value);
    }
}
```

### Encryption in Transit
- All connections use TLS 1.2+
- HSTS enabled with 1-year max-age
- Certificate pinning for mobile apps
- Perfect Forward Secrecy enabled

### Data Classification
| Level | Description | Examples | Protection Required |
|-------|-------------|----------|-------------------|
| Critical | Business critical | API keys, passwords | Encryption, access logs |
| Sensitive | PII/confidential | Names, emails, phones | Encryption, limited access |
| Internal | Internal use | Configs, logs | Access control |
| Public | Public info | Marketing content | None |

### Personal Data Handling (GDPR)
```php
// Data anonymization
class GDPRService
{
    public function anonymizeUser(User $user): void
    {
        $user->update([
            'name' => 'Deleted User',
            'email' => 'deleted-' . Str::random(10) . '@example.com',
            'phone' => null,
            'address' => null,
        ]);
    }
    
    public function exportUserData(User $user): array
    {
        return [
            'personal_data' => $user->only(['name', 'email', 'phone']),
            'appointments' => $user->appointments->toArray(),
            'activity_logs' => $user->activityLogs->toArray(),
        ];
    }
}
```

## Access Control

### Role-Based Access Control (RBAC)
```php
// Permission structure
class Permissions
{
    const ROLES = [
        'super_admin' => ['*'],
        'admin' => ['users.*', 'settings.*', 'reports.*'],
        'manager' => ['users.view', 'reports.view', 'appointments.*'],
        'user' => ['own.profile.*', 'own.appointments.*'],
    ];
}
```

### API Security
```php
// API rate limiting
Route::middleware(['auth:api', 'throttle:60,1'])->group(function () {
    Route::apiResource('appointments', AppointmentController::class);
});

// API key validation
class ValidateApiKey
{
    public function handle($request, Closure $next)
    {
        $apiKey = $request->header('X-API-Key');
        
        if (!$apiKey || !$this->isValidApiKey($apiKey)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        return $next($request);
    }
}
```

### Network Security
```nginx
# Nginx security headers
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline';" always;
```

## Vulnerability Management

### Security Scanning Schedule
| Scan Type | Frequency | Tool | Action Threshold |
|-----------|-----------|------|------------------|
| Dependency scan | Daily | Snyk/Dependabot | Critical/High |
| SAST | On commit | SonarQube | Medium+ |
| DAST | Weekly | OWASP ZAP | Medium+ |
| Penetration test | Quarterly | External vendor | All findings |

### Common Vulnerabilities Prevention

#### SQL Injection Prevention
```php
// ❌ Vulnerable
$users = DB::select("SELECT * FROM users WHERE email = '$email'");

// ✅ Secure
$users = DB::select("SELECT * FROM users WHERE email = ?", [$email]);
// or
$users = User::where('email', $email)->get();
```

#### XSS Prevention
```php
// ❌ Vulnerable
echo "<h1>Welcome $userName</h1>";

// ✅ Secure
echo "<h1>Welcome " . e($userName) . "</h1>";
// or in Blade
<h1>Welcome {{ $userName }}</h1>
```

#### CSRF Protection
```php
// Automatically included in forms
@csrf

// Manual verification
if (!$request->hasValidSignature()) {
    abort(401);
}
```

## Incident Response

### Security Incident Classification
| Severity | Description | Response Time | Examples |
|----------|-------------|---------------|----------|
| P1 - Critical | Active breach | 15 minutes | Data breach, system compromise |
| P2 - High | Potential breach | 1 hour | Suspicious activity, vulnerability |
| P3 - Medium | Security weakness | 4 hours | Failed scans, policy violation |
| P4 - Low | Minor issue | 24 hours | Best practice deviation |

### Incident Response Playbook

#### 1. Detection & Analysis
```bash
# Check for intrusion signs
grep -i "failed password\|unauthorized" /var/log/auth.log
tail -f storage/logs/security.log

# Analyze suspicious activity
php artisan security:analyze --last-hour
```

#### 2. Containment
```bash
# Block suspicious IP
iptables -A INPUT -s SUSPICIOUS_IP -j DROP

# Disable compromised account
php artisan user:disable user@example.com

# Revoke all sessions
php artisan session:flush
```

#### 3. Eradication & Recovery
```bash
# Rotate all secrets
./security/rotate-all-secrets.sh

# Force password reset
php artisan users:force-password-reset --all

# Restore from clean backup
./security/restore-clean-state.sh
```

#### 4. Post-Incident
- Document timeline
- Identify root cause
- Update security measures
- Notify affected parties
- Conduct lessons learned

## Security Monitoring

### Log Collection
```php
// Security event logging
class SecurityLogger
{
    public function logAuthFailure(string $email, string $ip): void
    {
        Log::channel('security')->warning('Authentication failure', [
            'email' => $email,
            'ip' => $ip,
            'user_agent' => request()->userAgent(),
            'timestamp' => now(),
        ]);
    }
    
    public function logSuspiciousActivity(string $activity, array $context): void
    {
        Log::channel('security')->alert('Suspicious activity detected', [
            'activity' => $activity,
            'context' => $context,
            'stack_trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS),
        ]);
    }
}
```

### Security Metrics
| Metric | Target | Alert Threshold | Dashboard |
|--------|--------|-----------------|-----------|
| Failed logins | < 10/hour | > 50/hour | Security dashboard |
| API errors | < 1% | > 5% | API dashboard |
| Blocked IPs | < 100/day | > 500/day | WAF dashboard |
| Security scans | 100% pass | Any failure | CI/CD pipeline |

### Alerting Rules
```yaml
# security-alerts.yaml
alerts:
  - name: HighFailedLogins
    condition: failed_logins > 100
    window: 5m
    severity: high
    notify: security-team
    
  - name: SQLInjectionAttempt
    pattern: "union.*select|sleep\(|benchmark\("
    severity: critical
    notify: oncall-security
    
  - name: BruteForceAttempt
    condition: failed_logins_per_ip > 20
    window: 1m
    severity: high
    action: block_ip
```

## Security Testing

### Automated Security Tests
```php
class SecurityTest extends TestCase
{
    public function test_sql_injection_prevention()
    {
        $response = $this->get("/api/users?id=1' OR '1'='1");
        $response->assertStatus(400);
        $this->assertDatabaseMissing('security_logs', [
            'type' => 'sql_injection_success'
        ]);
    }
    
    public function test_xss_prevention()
    {
        $payload = '<script>alert("XSS")</script>';
        $response = $this->post('/api/comments', ['content' => $payload]);
        
        $this->assertDatabaseHas('comments', [
            'content' => '&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;'
        ]);
    }
}
```

### Manual Security Checklist
- [ ] Review access logs for anomalies
- [ ] Verify all endpoints require authentication
- [ ] Check for exposed sensitive data
- [ ] Validate input sanitization
- [ ] Test rate limiting
- [ ] Verify error messages don't leak info
- [ ] Check HTTPS enforcement
- [ ] Review permission boundaries

## Compliance Requirements

### GDPR Compliance
```php
// GDPR compliance features
class GDPRController extends Controller
{
    // Right to access
    public function exportData(Request $request)
    {
        $user = $request->user();
        $data = $this->gdprService->collectUserData($user);
        
        return response()->json($data);
    }
    
    // Right to erasure
    public function deleteAccount(Request $request)
    {
        $user = $request->user();
        $this->gdprService->anonymizeUser($user);
        
        return response()->json(['message' => 'Account deleted']);
    }
    
    // Consent management
    public function updateConsent(Request $request)
    {
        $request->user()->consents()->create([
            'type' => $request->consent_type,
            'granted' => $request->granted,
            'ip_address' => $request->ip(),
        ]);
    }
}
```

### Audit Logging
```php
// Comprehensive audit logging
class AuditLogger
{
    public function log(string $action, Model $model, ?User $user = null): void
    {
        AuditLog::create([
            'user_id' => $user?->id ?? auth()->id(),
            'action' => $action,
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'changes' => $model->getChanges(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now(),
        ]);
    }
}
```

## Security Contacts

### Internal Contacts
| Role | Name | Contact | Availability |
|------|------|---------|--------------|
| Security Lead | {Name} | security@askproai.de | Business hours |
| Security Oncall | Rotation | security-oncall@askproai.de | 24/7 |
| DPO | {Name} | dpo@askproai.de | Business hours |

### External Contacts
| Service | Contact | Purpose |
|---------|---------|---------|
| Incident Response | vendor@example.com | Major incidents |
| Penetration Testing | pentest@example.com | Quarterly tests |
| Legal | legal@lawfirm.com | Breach notification |

## Security Training

### Required Training
- [ ] Security awareness (all staff) - Annual
- [ ] GDPR training (all staff) - Annual
- [ ] Secure coding (developers) - Quarterly
- [ ] Incident response (ops team) - Bi-annual

### Security Resources
- [OWASP Top 10](https://owasp.org/Top10/)
- [Security Best Practices](./security-best-practices.md)
- [Incident Response Guide](./incident-response.md)
- [Secure Development](./secure-development.md)

---

> 🔄 **Auto-Updated**: This documentation is automatically checked for updates. Last verification: {TIMESTAMP}  
> ⚠️ **Reminder**: Security documentation must be reviewed quarterly