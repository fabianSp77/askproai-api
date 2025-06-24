# Security Configuration

## Overview

This guide covers all security-related configurations in AskProAI, including authentication, authorization, encryption, and security best practices.

## Authentication Configuration

### Multi-Factor Authentication (MFA)
```php
// config/auth.php
return [
    'mfa' => [
        'enabled' => env('MFA_ENABLED', true),
        'issuer' => env('MFA_ISSUER', 'AskProAI'),
        'enforced' => env('MFA_ENFORCED', false),
        'recovery_codes' => 8,
        'qr_size' => 200,
    ],
    
    'password' => [
        'min_length' => 12,
        'require_uppercase' => true,
        'require_numeric' => true,
        'require_special' => true,
        'history' => 5, // Prevent reuse of last 5 passwords
        'expires_days' => 90,
    ],
    
    'session' => [
        'lifetime' => env('SESSION_LIFETIME', 120),
        'expire_on_close' => false,
        'encrypt' => true,
        'secure' => env('SESSION_SECURE_COOKIE', true),
        'same_site' => 'lax',
    ],
];
```

### API Authentication
```php
// config/sanctum.php
return [
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'localhost,127.0.0.1')),
    'guard' => ['web'],
    'expiration' => 525600, // 1 year
    
    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', 'askproai_'),
    
    'middleware' => [
        'verify_csrf_token' => App\Http\Middleware\VerifyCsrfToken::class,
        'encrypt_cookies' => App\Http\Middleware\EncryptCookies::class,
    ],
];
```

## Encryption Configuration

### Application Encryption
```php
// config/app.php
'cipher' => 'AES-256-CBC',

// config/encryption.php
return [
    'enabled' => env('ENCRYPT_SENSITIVE_DATA', true),
    
    'fields' => [
        'customers' => ['phone', 'email', 'date_of_birth'],
        'companies' => ['api_keys', 'webhook_secrets'],
        'call_recordings' => ['url', 'transcript'],
        'payment_methods' => ['card_number', 'cvv'],
    ],
    
    'key_rotation' => [
        'enabled' => env('ENCRYPTION_KEY_ROTATION', true),
        'schedule' => 'quarterly',
        'grace_period' => 30, // days
    ],
];
```

### Database Encryption
```php
// app/Traits/EncryptsAttributes.php
trait EncryptsAttributes
{
    protected function encryptAttribute($value)
    {
        if (!config('encryption.enabled')) {
            return $value;
        }
        
        return Crypt::encryptString($value);
    }
    
    protected function decryptAttribute($value)
    {
        if (!config('encryption.enabled') || empty($value)) {
            return $value;
        }
        
        try {
            return Crypt::decryptString($value);
        } catch (DecryptException $e) {
            Log::error('Decryption failed', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
```

## CORS Configuration

```php
// config/cors.php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    
    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
    
    'allowed_origins' => [
        env('FRONTEND_URL', 'https://app.askproai.de'),
        'https://admin.askproai.de',
    ],
    
    'allowed_origins_patterns' => [
        '#^https://.*\.askproai\.de$#',
    ],
    
    'allowed_headers' => [
        'Content-Type',
        'X-Requested-With',
        'Authorization',
        'X-CSRF-TOKEN',
        'X-Company-ID',
    ],
    
    'exposed_headers' => [
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
    ],
    
    'max_age' => 86400,
    
    'supports_credentials' => true,
];
```

## Security Headers

### Middleware Configuration
```php
// app/Http/Middleware/SecurityHeaders.php
class SecurityHeaders
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        
        // Content Security Policy
        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://js.stripe.com; " .
               "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
               "font-src 'self' https://fonts.gstatic.com; " .
               "img-src 'self' data: https:; " .
               "connect-src 'self' https://api.stripe.com https://api.retellai.com";
        
        $response->headers->set('Content-Security-Policy', $csp);
        
        // HSTS
        if (config('app.env') === 'production') {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }
        
        return $response;
    }
}
```

## Rate Limiting

### Adaptive Rate Limiting
```php
// app/Http/Middleware/AdaptiveRateLimit.php
class AdaptiveRateLimit
{
    private RateLimiter $limiter;
    
    public function handle($request, Closure $next, $maxAttempts = 60)
    {
        $key = $this->resolveRequestSignature($request);
        $maxAttempts = $this->getAdaptiveLimit($request, $maxAttempts);
        
        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            return $this->buildResponse($key, $maxAttempts);
        }
        
        $this->limiter->hit($key, 60);
        
        $response = $next($request);
        
        return $this->addHeaders(
            $response,
            $maxAttempts,
            $this->limiter->remaining($key, $maxAttempts)
        );
    }
    
    protected function getAdaptiveLimit($request, $default)
    {
        $user = $request->user();
        
        if (!$user) {
            return $default;
        }
        
        return match($user->company->subscription_plan) {
            'enterprise' => 1000,
            'professional' => 300,
            'starter' => 100,
            default => $default,
        };
    }
}
```

## Input Validation & Sanitization

### Global Input Sanitization
```php
// app/Http/Middleware/SanitizeInput.php
class SanitizeInput
{
    protected array $except = [
        'password',
        'password_confirmation',
    ];
    
    public function handle($request, Closure $next)
    {
        $input = $request->all();
        
        array_walk_recursive($input, function (&$value, $key) {
            if (!in_array($key, $this->except) && is_string($value)) {
                $value = $this->sanitize($value);
            }
        });
        
        $request->merge($input);
        
        return $next($request);
    }
    
    protected function sanitize($value)
    {
        // Remove null bytes
        $value = str_replace(chr(0), '', $value);
        
        // Strip tags but preserve content
        $value = strip_tags($value);
        
        // Normalize whitespace
        $value = preg_replace('/\s+/', ' ', trim($value));
        
        return $value;
    }
}
```

### SQL Injection Prevention
```php
// app/Services/Security/QuerySanitizer.php
class QuerySanitizer
{
    protected array $dangerousPatterns = [
        '/union\s+select/i',
        '/insert\s+into/i',
        '/drop\s+table/i',
        '/update\s+.*\s+set/i',
        '/delete\s+from/i',
        '/script\s*>/i',
        '/or\s+1\s*=\s*1/i',
        '/\'\s*or\s+\'/i',
    ];
    
    public function sanitize(string $input): string
    {
        foreach ($this->dangerousPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                throw new MaliciousInputException('Potentially dangerous input detected');
            }
        }
        
        return $input;
    }
    
    public function sanitizeForLike(string $input): string
    {
        return str_replace(['%', '_', '[', ']'], ['\%', '\_', '\[', '\]'], $input);
    }
}
```

## File Upload Security

### Upload Configuration
```php
// config/upload.php
return [
    'allowed_extensions' => [
        'images' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'documents' => ['pdf', 'doc', 'docx', 'xls', 'xlsx'],
        'audio' => ['mp3', 'wav', 'm4a'],
    ],
    
    'max_size' => env('UPLOAD_MAX_SIZE', 10240), // 10MB in KB
    
    'scan_for_viruses' => env('UPLOAD_VIRUS_SCAN', true),
    
    'storage' => [
        'disk' => env('UPLOAD_DISK', 'local'),
        'path' => 'uploads/{year}/{month}/{day}',
        'visibility' => 'private',
    ],
    
    'image_processing' => [
        'strip_metadata' => true,
        'max_dimensions' => [
            'width' => 4096,
            'height' => 4096,
        ],
    ],
];
```

### File Upload Validator
```php
// app/Services/Security/FileUploadValidator.php
class FileUploadValidator
{
    public function validate(UploadedFile $file, string $type): void
    {
        // Check file extension
        $extension = strtolower($file->getClientOriginalExtension());
        $allowedExtensions = config("upload.allowed_extensions.{$type}", []);
        
        if (!in_array($extension, $allowedExtensions)) {
            throw new InvalidFileTypeException("File type {$extension} is not allowed");
        }
        
        // Check MIME type
        $mimeType = $file->getMimeType();
        if (!$this->isValidMimeType($mimeType, $type)) {
            throw new InvalidFileTypeException("MIME type {$mimeType} is not allowed");
        }
        
        // Check file size
        $maxSize = config('upload.max_size') * 1024; // Convert to bytes
        if ($file->getSize() > $maxSize) {
            throw new FileSizeException("File exceeds maximum size limit");
        }
        
        // Scan for viruses if enabled
        if (config('upload.scan_for_viruses')) {
            $this->scanForViruses($file);
        }
    }
    
    protected function scanForViruses(UploadedFile $file): void
    {
        $scanner = app(VirusScanner::class);
        
        if (!$scanner->scan($file->getPathname())) {
            throw new MaliciousFileException("File contains malicious content");
        }
    }
}
```

## Webhook Security

### Signature Verification
```php
// app/Http/Middleware/VerifyWebhookSignature.php
abstract class VerifyWebhookSignature
{
    abstract protected function getSecret(Request $request): string;
    abstract protected function getSignatureHeader(): string;
    abstract protected function calculateSignature(string $payload, string $secret): string;
    
    public function handle(Request $request, Closure $next)
    {
        $signature = $request->header($this->getSignatureHeader());
        
        if (!$signature) {
            abort(401, 'Missing signature header');
        }
        
        $payload = $request->getContent();
        $secret = $this->getSecret($request);
        $expected = $this->calculateSignature($payload, $secret);
        
        if (!hash_equals($expected, $signature)) {
            Log::warning('Invalid webhook signature', [
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
            ]);
            
            abort(401, 'Invalid signature');
        }
        
        // Add timestamp validation
        $this->validateTimestamp($request);
        
        return $next($request);
    }
    
    protected function validateTimestamp(Request $request): void
    {
        $timestamp = $request->header('X-Timestamp');
        
        if (!$timestamp) {
            return;
        }
        
        $tolerance = config('webhooks.timestamp_tolerance', 300); // 5 minutes
        
        if (abs(time() - intval($timestamp)) > $tolerance) {
            abort(401, 'Request timestamp too old');
        }
    }
}
```

## API Security

### API Key Management
```php
// app/Services/Security/ApiKeyManager.php
class ApiKeyManager
{
    public function generate(): string
    {
        return 'askproai_' . bin2hex(random_bytes(32));
    }
    
    public function rotate(Company $company): string
    {
        $oldKey = $company->api_key;
        $newKey = $this->generate();
        
        // Store old key for grace period
        $company->api_keys()->create([
            'key' => $oldKey,
            'expires_at' => now()->addDays(7),
            'status' => 'rotating',
        ]);
        
        // Update to new key
        $company->update(['api_key' => $newKey]);
        
        // Notify about key rotation
        $company->notify(new ApiKeyRotated($oldKey, $newKey));
        
        return $newKey;
    }
    
    public function validate(string $key): bool
    {
        // Check primary keys
        if (Company::where('api_key', hash('sha256', $key))->exists()) {
            return true;
        }
        
        // Check rotating keys
        return ApiKey::where('key', hash('sha256', $key))
            ->where('expires_at', '>', now())
            ->where('status', 'active')
            ->exists();
    }
}
```

## Security Monitoring

### Intrusion Detection
```php
// app/Services/Security/IntrusionDetector.php
class IntrusionDetector
{
    protected array $suspiciousPatterns = [
        'sql_injection' => [
            '/union.*select/i',
            '/select.*from.*information_schema/i',
            '/into\s+outfile/i',
        ],
        'xss_attempt' => [
            '/<script[^>]*>.*?<\/script>/si',
            '/javascript:/i',
            '/on\w+\s*=/i',
        ],
        'path_traversal' => [
            '/\.\.\//',
            '/\.\.\\\\/',
            '/%2e%2e%2f/i',
        ],
        'command_injection' => [
            '/;\s*cat\s+/i',
            '/\|\s*nc\s+/i',
            '/`.*`/',
        ],
    ];
    
    public function detect(Request $request): ?SecurityThreat
    {
        $data = array_merge(
            $request->all(),
            ['url' => $request->fullUrl()],
            $request->headers->all()
        );
        
        foreach ($data as $key => $value) {
            if (!is_string($value)) continue;
            
            foreach ($this->suspiciousPatterns as $type => $patterns) {
                foreach ($patterns as $pattern) {
                    if (preg_match($pattern, $value)) {
                        return new SecurityThreat($type, $pattern, $value, $request);
                    }
                }
            }
        }
        
        return null;
    }
}
```

### Security Event Logging
```php
// app/Services/Security/SecurityLogger.php
class SecurityLogger
{
    public function logSecurityEvent(string $type, array $context): void
    {
        $event = SecurityEvent::create([
            'type' => $type,
            'severity' => $this->getSeverity($type),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id' => auth()->id(),
            'context' => $context,
            'occurred_at' => now(),
        ]);
        
        // Alert if critical
        if ($event->severity === 'critical') {
            $this->alertSecurityTeam($event);
        }
        
        // Log to separate security log
        Log::channel('security')->warning("Security event: {$type}", $context);
    }
    
    protected function getSeverity(string $type): string
    {
        return match($type) {
            'sql_injection', 'command_injection' => 'critical',
            'xss_attempt', 'path_traversal' => 'high',
            'invalid_signature', 'rate_limit_exceeded' => 'medium',
            default => 'low',
        };
    }
}
```

## Compliance & Auditing

### Audit Trail
```php
// app/Traits/Auditable.php
trait Auditable
{
    public static function bootAuditable()
    {
        static::created(function ($model) {
            AuditLog::create([
                'user_id' => auth()->id(),
                'company_id' => $model->company_id ?? auth()->user()?->company_id,
                'model_type' => get_class($model),
                'model_id' => $model->id,
                'event' => 'created',
                'old_values' => null,
                'new_values' => $model->getAttributes(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });
        
        static::updated(function ($model) {
            AuditLog::create([
                'user_id' => auth()->id(),
                'company_id' => $model->company_id ?? auth()->user()?->company_id,
                'model_type' => get_class($model),
                'model_id' => $model->id,
                'event' => 'updated',
                'old_values' => $model->getOriginal(),
                'new_values' => $model->getChanges(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });
    }
}
```

### GDPR Compliance
```php
// config/gdpr.php
return [
    'data_retention' => [
        'audit_logs' => 365 * 2, // 2 years
        'security_events' => 365, // 1 year
        'api_logs' => 90, // 90 days
        'temporary_files' => 7, // 7 days
    ],
    
    'anonymization' => [
        'fields' => [
            'name' => 'Anonymous User',
            'email' => 'deleted@example.com',
            'phone' => '+00000000000',
            'address' => 'Deleted',
        ],
    ],
    
    'export_formats' => ['json', 'csv', 'pdf'],
    
    'consent_types' => [
        'marketing',
        'analytics',
        'necessary',
    ],
];
```

## Security Best Practices

### Password Policy
```php
// app/Rules/StrongPassword.php
class StrongPassword implements Rule
{
    public function passes($attribute, $value)
    {
        // Minimum length
        if (strlen($value) < config('auth.password.min_length')) {
            return false;
        }
        
        // Require uppercase
        if (config('auth.password.require_uppercase') && !preg_match('/[A-Z]/', $value)) {
            return false;
        }
        
        // Require numeric
        if (config('auth.password.require_numeric') && !preg_match('/[0-9]/', $value)) {
            return false;
        }
        
        // Require special character
        if (config('auth.password.require_special') && !preg_match('/[^A-Za-z0-9]/', $value)) {
            return false;
        }
        
        // Check against common passwords
        if ($this->isCommonPassword($value)) {
            return false;
        }
        
        return true;
    }
}
```

### Session Security
```php
// app/Http/Middleware/SessionSecurity.php
class SessionSecurity
{
    public function handle($request, Closure $next)
    {
        // Regenerate session ID on login
        if ($request->session()->get('just_logged_in')) {
            $request->session()->regenerate();
            $request->session()->forget('just_logged_in');
        }
        
        // Check for session hijacking
        $currentIp = $request->ip();
        $sessionIp = $request->session()->get('ip_address');
        
        if ($sessionIp && $sessionIp !== $currentIp) {
            auth()->logout();
            $request->session()->invalidate();
            
            return redirect('/login')
                ->with('error', 'Session security violation detected');
        }
        
        // Store current IP
        $request->session()->put('ip_address', $currentIp);
        
        return $next($request);
    }
}
```

## Related Documentation
- [Environment Configuration](environment.md)
- [API Authentication](../api/authentication.md)
- [GDPR Compliance](../features/gdpr.md)
- [Monitoring](../operations/monitoring.md)