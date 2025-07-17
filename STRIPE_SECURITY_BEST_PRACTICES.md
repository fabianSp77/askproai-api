# 🔐 Stripe Payment Security Best Practices

## 🛡️ Security Overview

This document outlines security best practices for the Stripe payment integration in AskProAI, covering PCI compliance, data protection, and operational security.

---

## 🔑 API Key Management

### Storage & Access

#### ✅ DO:
```bash
# Store in environment variables
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...

# Restrict file permissions
chmod 600 .env
chown www-data:www-data .env

# Use separate keys for environments
.env.production  # Live keys
.env.staging     # Test keys
.env.local       # Test keys
```

#### ❌ DON'T:
```php
// Never hardcode keys
$stripe = new StripeClient('sk_live_...'); // WRONG!

// Never commit keys
git add .env  // WRONG!

// Never log keys
Log::info('Stripe key: ' . config('services.stripe.secret')); // WRONG!
```

### Key Rotation

#### Rotation Schedule:
- **Every 90 days**: Routine rotation
- **Immediately**: After security incident
- **Within 24 hours**: After employee departure

#### Rotation Procedure:
```bash
# 1. Generate new keys in Stripe Dashboard

# 2. Update staging first
ssh staging-server
cd /var/www/api-gateway
cp .env .env.backup
nano .env  # Update keys
php artisan config:clear

# 3. Test in staging
php artisan stripe:test-connection

# 4. Update production
ssh production-server
# Repeat steps above

# 5. Revoke old keys in Stripe Dashboard
```

---

## 🔒 Webhook Security

### Signature Verification

#### Implementation:
```php
// Always verify webhook signatures
public function handleWebhook(Request $request)
{
    $payload = $request->getContent();
    $sig_header = $request->header('Stripe-Signature');
    $endpoint_secret = config('services.stripe.webhook_secret');

    try {
        $event = \Stripe\Webhook::constructEvent(
            $payload,
            $sig_header,
            $endpoint_secret
        );
    } catch(\UnexpectedValueException $e) {
        // Invalid payload
        Log::warning('Invalid webhook payload', [
            'ip' => $request->ip()
        ]);
        return response('Invalid payload', 400);
    } catch(\Stripe\Exception\SignatureVerificationException $e) {
        // Invalid signature
        Log::warning('Invalid webhook signature', [
            'ip' => $request->ip()
        ]);
        return response('Invalid signature', 400);
    }

    // Process verified webhook
}
```

### Webhook Endpoint Security:
```nginx
# Nginx configuration
location /api/stripe/webhook {
    # Only allow Stripe IPs (optional but recommended)
    allow 3.18.12.63;
    allow 3.130.192.231;
    allow 13.235.14.237;
    allow 13.235.122.149;
    allow 18.211.135.69;
    allow 35.154.171.200;
    allow 52.15.183.38;
    allow 54.88.130.119;
    allow 54.88.130.237;
    allow 54.187.174.169;
    allow 54.187.205.235;
    allow 54.187.216.72;
    deny all;
    
    # Pass to Laravel
    try_files $uri $uri/ /index.php?$query_string;
}
```

### Idempotency:
```php
// Prevent duplicate processing
$eventId = $event->id;
$existingEvent = WebhookEvent::where('stripe_event_id', $eventId)->first();

if ($existingEvent) {
    Log::info('Duplicate webhook event', ['event_id' => $eventId]);
    return response('Already processed', 200);
}

// Process and record
WebhookEvent::create([
    'stripe_event_id' => $eventId,
    'type' => $event->type,
    'processed_at' => now()
]);
```

---

## 💳 Payment Data Security

### PCI Compliance

#### Never Store:
- ❌ Full card numbers
- ❌ CVV/CVC codes
- ❌ Card expiration dates
- ❌ Any sensitive authentication data

#### Safe to Store:
- ✅ Stripe Payment Method IDs
- ✅ Stripe Customer IDs
- ✅ Last 4 digits of card
- ✅ Card brand (Visa, Mastercard, etc.)

### Secure Payment Method Handling:
```php
// Good: Use Payment Method IDs
$paymentMethodId = 'pm_1234567890';
$company->prepaidBalance->update([
    'stripe_payment_method_id' => $paymentMethodId
]);

// Bad: Never store card details
$company->update([
    'card_number' => $request->card_number, // NEVER DO THIS!
    'cvv' => $request->cvv // NEVER DO THIS!
]);
```

### Tokenization:
```javascript
// Frontend: Always use Stripe Elements or Checkout
const stripe = Stripe('pk_live_...');

// Create payment method without touching card data
const {error, paymentMethod} = await stripe.createPaymentMethod({
    type: 'card',
    card: cardElement, // Stripe Element
});

// Send only the payment method ID to backend
fetch('/api/save-payment-method', {
    method: 'POST',
    body: JSON.stringify({
        payment_method_id: paymentMethod.id
    })
});
```

---

## 🛡️ Transaction Security

### Amount Validation:
```php
// Always validate amounts
public function createTopup(Request $request)
{
    $request->validate([
        'amount' => 'required|numeric|min:1|max:10000'
    ]);

    $amount = round($request->amount, 2);
    
    // Additional business logic validation
    if ($amount < $company->minimum_topup_amount) {
        throw new ValidationException('Amount below minimum');
    }
}
```

### Currency Handling:
```php
// Always specify and validate currency
$allowedCurrencies = ['eur', 'usd', 'gbp'];
$currency = strtolower($request->currency);

if (!in_array($currency, $allowedCurrencies)) {
    throw new ValidationException('Invalid currency');
}

// Store amounts in smallest currency unit
$amountInCents = (int)($amount * 100);
```

### Refund Security:
```php
// Implement refund limits
class RefundService
{
    const MAX_REFUND_PERCENTAGE = 100;
    const REFUND_TIME_LIMIT_DAYS = 180;

    public function refundCharge(CallCharge $charge, float $amount, string $reason)
    {
        // Check time limit
        if ($charge->charged_at->lt(now()->subDays(self::REFUND_TIME_LIMIT_DAYS))) {
            throw new RefundException('Refund period expired');
        }

        // Check amount
        if ($amount > $charge->amount_charged) {
            throw new RefundException('Refund amount exceeds charge');
        }

        // Check if already refunded
        if ($charge->refund_status !== 'none') {
            throw new RefundException('Charge already refunded');
        }

        // Log refund attempt
        Log::channel('refunds')->info('Refund initiated', [
            'charge_id' => $charge->id,
            'amount' => $amount,
            'initiated_by' => auth()->id(),
            'reason' => $reason
        ]);

        // Process refund...
    }
}
```

---

## 🔍 Monitoring & Auditing

### Transaction Logging:
```php
// Log all financial transactions
class AuditableBalanceTransaction extends Model
{
    protected static function booted()
    {
        static::creating(function ($transaction) {
            Log::channel('transactions')->info('Transaction created', [
                'company_id' => $transaction->company_id,
                'type' => $transaction->type,
                'amount' => $transaction->amount,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'user_id' => auth()->id()
            ]);
        });
    }
}
```

### Suspicious Activity Detection:
```php
// Monitor for suspicious patterns
class SuspiciousActivityMonitor
{
    public function checkTopupPattern(Company $company)
    {
        $recentTopups = BalanceTopup::where('company_id', $company->id)
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        if ($recentTopups > 10) {
            // Alert administrators
            Log::channel('security')->warning('Suspicious topup pattern', [
                'company_id' => $company->id,
                'topup_count' => $recentTopups
            ]);
            
            // Optional: Temporarily restrict
            $company->update(['topup_restricted' => true]);
        }
    }
}
```

### Audit Trail:
```sql
-- Create audit table
CREATE TABLE payment_audit_log (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(50) NOT NULL,
    company_id BIGINT,
    user_id BIGINT,
    amount DECIMAL(15,2),
    currency VARCHAR(3),
    stripe_object_id VARCHAR(255),
    ip_address VARCHAR(45),
    user_agent TEXT,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_company_created (company_id, created_at),
    INDEX idx_event_type (event_type)
);
```

---

## 🚫 Access Control

### Role-Based Permissions:
```php
// Define payment permissions
class PaymentPolicy
{
    public function viewBalance(User $user, Company $company)
    {
        return $user->company_id === $company->id 
            && $user->hasPermission('billing.view');
    }

    public function createTopup(User $user, Company $company)
    {
        return $user->company_id === $company->id 
            && $user->hasPermission('billing.pay')
            && $company->is_active;
    }

    public function configureAutoTopup(User $user, Company $company)
    {
        return $user->company_id === $company->id 
            && $user->hasPermission('billing.manage')
            && $user->is_verified;
    }
}
```

### Admin Access Restrictions:
```php
// Separate admin operations
class AdminPaymentController
{
    public function __construct()
    {
        $this->middleware(['auth:admin', 'can:manage-payments']);
    }

    public function adjustBalance(Request $request, Company $company)
    {
        // Log admin action
        AdminActionLog::create([
            'admin_id' => auth()->id(),
            'action' => 'balance_adjustment',
            'target_type' => Company::class,
            'target_id' => $company->id,
            'data' => $request->all(),
            'ip' => $request->ip()
        ]);

        // Perform adjustment...
    }
}
```

---

## 🌐 Network Security

### HTTPS Enforcement:
```php
// Force HTTPS for payment routes
Route::group(['middleware' => 'force.https'], function () {
    Route::post('/billing/topup', 'BillingController@topup');
    Route::post('/billing/auto-topup', 'BillingController@configureAutoTopup');
});

// Middleware
class ForceHttps
{
    public function handle($request, Closure $next)
    {
        if (!$request->secure() && app()->environment('production')) {
            return redirect()->secure($request->getRequestUri());
        }
        return $next($request);
    }
}
```

### CORS Configuration:
```php
// config/cors.php
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['POST', 'GET', 'OPTIONS'],
    'allowed_origins' => [
        'https://app.askproai.de',
        'https://portal.askproai.de'
    ],
    'allowed_headers' => ['Content-Type', 'X-Requested-With', 'Authorization'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
```

---

## 📱 Frontend Security

### Secure Implementation:
```html
<!-- Use Stripe's hosted checkout -->
<script src="https://js.stripe.com/v3/"></script>
<script>
    const stripe = Stripe('pk_live_...', {
        apiVersion: '2023-10-16'
    });

    // Redirect to Stripe Checkout
    async function checkout() {
        const response = await fetch('/api/create-checkout-session', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        const session = await response.json();
        
        // Redirect to Stripe's secure checkout
        stripe.redirectToCheckout({
            sessionId: session.id
        });
    }
</script>
```

### Content Security Policy:
```php
// Add CSP headers for payment pages
class ContentSecurityPolicy
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        
        if ($request->is('billing/*')) {
            $response->headers->set(
                'Content-Security-Policy',
                "default-src 'self'; " .
                "script-src 'self' https://js.stripe.com; " .
                "frame-src 'self' https://js.stripe.com https://hooks.stripe.com; " .
                "connect-src 'self' https://api.stripe.com"
            );
        }
        
        return $response;
    }
}
```

---

## 🚨 Incident Response

### Security Incident Checklist:

1. **Immediate Actions**:
   - [ ] Disable affected API keys
   - [ ] Enable maintenance mode
   - [ ] Stop processing new payments
   - [ ] Preserve logs

2. **Investigation**:
   - [ ] Review access logs
   - [ ] Check for unauthorized transactions
   - [ ] Identify attack vector
   - [ ] Document timeline

3. **Remediation**:
   - [ ] Rotate all API keys
   - [ ] Update webhook secrets
   - [ ] Patch vulnerabilities
   - [ ] Notify affected users

4. **Recovery**:
   - [ ] Re-enable services
   - [ ] Monitor for anomalies
   - [ ] Update security measures
   - [ ] Post-incident review

### Emergency Contacts:
```yaml
Stripe Security: security@stripe.com
Stripe Support: Via Dashboard
Internal Security Team: security@askproai.de
Legal Team: legal@askproai.de
```

---

## 📋 Security Checklist

### Daily:
- [ ] Review failed payment attempts
- [ ] Check for suspicious activity patterns
- [ ] Monitor webhook failures
- [ ] Verify backup integrity

### Weekly:
- [ ] Review access logs
- [ ] Check for unusual API usage
- [ ] Audit admin actions
- [ ] Test monitoring alerts

### Monthly:
- [ ] Review and update permissions
- [ ] Audit third-party access
- [ ] Check for security updates
- [ ] Review incident response plan

### Quarterly:
- [ ] Rotate API keys
- [ ] Security training
- [ ] Penetration testing
- [ ] PCI compliance review

---

## 🎓 Training & Awareness

### Developer Guidelines:
1. Never handle raw card data
2. Always use HTTPS
3. Validate all inputs
4. Log security events
5. Follow least privilege principle

### Support Team Guidelines:
1. Never ask for full card numbers
2. Verify customer identity
3. Use secure communication
4. Report suspicious activity
5. Follow data retention policies

---

Remember: Security is everyone's responsibility. When in doubt, err on the side of caution and consult the security team.