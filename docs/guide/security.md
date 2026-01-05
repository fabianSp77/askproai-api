# Security

AskPro API Gateway implements multiple layers of security to protect tenant data and system integrity.

## Authentication

### Session-Based (Admin Panel)

The Filament admin panel uses Laravel's session authentication:

```php
// config/filament.php
'auth' => [
    'guard' => 'web',
    'pages' => [
        'login' => \App\Filament\Pages\Auth\Login::class,
    ],
],
```

### API Authentication

API endpoints use Laravel Sanctum for token-based authentication:

```php
// Creating API tokens
$token = $user->createToken('api-access')->plainTextToken;

// Using tokens
curl -H "Authorization: Bearer {token}" \
     https://api.askproai.de/api/v1/appointments
```

### Webhook Authentication

Webhooks use HMAC signature verification:

```php
// Retell webhook signature verification
$signature = hash_hmac('sha256', $payload, $secret);
$isValid = hash_equals($signature, $request->header('X-Retell-Signature'));
```

## Authorization

### Role-Based Access Control

Using Spatie Laravel Permission:

| Role | Permissions |
|------|-------------|
| Super Admin | Full system access |
| Company Admin | Full tenant access |
| Staff | Limited to assigned branches |
| API User | API-only access |

### Policy-Based Authorization

```php
// app/Policies/AppointmentPolicy.php
public function view(User $user, Appointment $appointment): bool
{
    return $user->company_id === $appointment->company_id;
}
```

## Data Protection

### Multi-Tenant Isolation

All tenant data is isolated using CompanyScope:

```php
// Automatic query scoping
Appointment::all(); // Only returns current tenant's data

// Bypass for super admin
Appointment::withoutGlobalScope(CompanyScope::class)->get();
```

### Encryption

- **At Rest**: Database encryption for sensitive fields
- **In Transit**: TLS 1.3 for all connections
- **Secrets**: Environment variables, never in code

```php
// Encrypted model attributes
protected $casts = [
    'api_key' => 'encrypted',
    'webhook_secret' => 'encrypted',
];
```

## Input Validation

### Request Validation

```php
public function store(Request $request)
{
    $validated = $request->validate([
        'email' => 'required|email|max:255',
        'phone' => 'required|regex:/^[0-9+\-\s]+$/',
        'date' => 'required|date|after:today',
    ]);
}
```

### XSS Prevention

Blade templates auto-escape output:

```blade
{{-- Safe - HTML entities escaped --}}
{{ $user->name }}

{{-- Dangerous - only use for trusted HTML --}}
{!! $trustedHtml !!}
```

### SQL Injection Prevention

Always use Eloquent or parameterized queries:

```php
// Safe - parameterized
User::where('email', $email)->first();

// Safe - query builder
DB::table('users')->where('email', '=', $email)->first();

// NEVER do this
DB::select("SELECT * FROM users WHERE email = '$email'");
```

## CSRF Protection

All forms include CSRF tokens:

```blade
<form method="POST">
    @csrf
    <!-- form fields -->
</form>
```

Excluded endpoints (webhooks):

```php
// app/Http/Middleware/VerifyCsrfToken.php
protected $except = [
    'webhooks/retell/*',
    'webhooks/calcom/*',
];
```

## Rate Limiting

### API Rate Limits

```php
// routes/api.php
Route::middleware(['throttle:api'])->group(function () {
    // 60 requests per minute
});

// Custom rate limits
RateLimiter::for('appointments', function (Request $request) {
    return Limit::perMinute(30)->by($request->user()?->id);
});
```

### Login Throttling

Failed login attempts are throttled:

```php
// 5 attempts, then 60 second lockout
protected $maxAttempts = 5;
protected $decayMinutes = 1;
```

## Audit Logging

### Activity Log

All significant actions are logged:

```php
activity()
    ->performedOn($appointment)
    ->causedBy($user)
    ->withProperties(['old' => $old, 'new' => $new])
    ->log('updated');
```

### Security Events

| Event | Logged Data |
|-------|-------------|
| Login | IP, User Agent, Timestamp |
| Failed Login | IP, Email, Timestamp |
| Permission Change | User, Role, Admin |
| Data Export | User, Type, Count |

## Security Headers

```nginx
# Nginx security headers
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
add_header Content-Security-Policy "default-src 'self';" always;
```

## Vulnerability Management

### Dependencies

Regular security updates:

```bash
# Check for vulnerabilities
composer audit
npm audit

# Update dependencies
composer update --with-all-dependencies
npm update
```

### Security Checklist

- [ ] HTTPS enforced
- [ ] CSRF tokens on all forms
- [ ] Input validation on all endpoints
- [ ] Rate limiting configured
- [ ] Audit logging enabled
- [ ] Dependencies up to date
- [ ] Error messages sanitized
- [ ] Debug mode disabled in production

## Incident Response

1. **Detection**: Monitor logs and alerts
2. **Containment**: Isolate affected systems
3. **Investigation**: Analyze attack vectors
4. **Recovery**: Restore from backups if needed
5. **Post-Mortem**: Document and improve
