# Coding Conventions

**Analysis Date:** 2026-01-19

## Naming Patterns

**Files:**
- Controllers: `{Name}Controller.php` - `app/Http/Controllers/Api/RetellApiController.php`
- Services: `{Domain}{Name}Service.php` - `app/Services/Billing/StripeInvoicingService.php`
- Models: PascalCase singular - `app/Models/Appointment.php`, `app/Models/Customer.php`
- Traits: PascalCase descriptive - `app/Traits/BelongsToCompany.php`
- Factories: `{Model}Factory.php` - `database/factories/CustomerFactory.php`
- Filament Resources: `{Model}Resource.php` - `app/Filament/Resources/AppointmentResource.php`

**Functions:**
- camelCase for methods: `ensureCustomer()`, `findService()`, `validateConfidence()`
- snake_case for test methods: `test_health_endpoint_returns_correct_status()`
- Pest test closures: `it('auto-assigns staff to appointments', function () {})`

**Variables:**
- camelCase: `$stripeCustomerId`, `$bookingDetails`, `$retellCallId`
- Descriptive: `$existingCustomer`, `$appointmentAlternatives`

**Types:**
- PascalCase for classes/interfaces: `AppointmentCreationService`, `CalendarServiceInterface`
- UPPER_SNAKE_CASE for constants: `DURATION_SHORT`, `PREFIX_MODEL`, `STATUS_DRAFT`

## Code Style

**Formatting:**
- Tool: Laravel Pint (`laravel/pint` ^1.13)
- Indentation: 4 spaces
- Line endings: LF
- Final newline: Yes
- Trailing whitespace: Trimmed

**EditorConfig (`.editorconfig`):**
```ini
[*]
charset = utf-8
indent_size = 4
indent_style = space
insert_final_newline = true
trim_trailing_whitespace = true

[*.{yml,yaml}]
indent_size = 2
```

**PHP Standards:**
- PHP 8.2+ with strict types implied
- Laravel 11 conventions
- Filament 3 patterns for admin panel

## Import Organization

**Order:**
1. PHP built-in classes (`Exception`, `RuntimeException`)
2. Vendor packages (`Carbon\Carbon`, `Stripe\StripeClient`)
3. Laravel framework (`Illuminate\*`)
4. Application classes (`App\*`)

**Example from `app/Services/Billing/StripeInvoicingService.php`:**
```php
<?php

namespace App\Services\Billing;

use App\Models\AggregateInvoice;
use App\Models\AggregateInvoiceItem;
use App\Models\Company;
use App\Models\StripeEvent;
use App\Mail\PartnerInvoiceMail;
use App\Notifications\InvoicePaymentFailedNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;
```

**Path Aliases:**
- None configured - use full `App\` namespace

## Error Handling

**Patterns:**
- Exception classes in `app/Exceptions/`
- Domain-specific exceptions: `app/Exceptions/Appointments/AppointmentException.php`
- Custom exception handler: `app/Exceptions/Handler.php`

**Exception Hierarchy:**
```php
App\Exceptions\
├── Handler.php                    # Main exception handler
├── CustomHandler.php              # Custom rendering logic
└── Appointments/
    ├── AppointmentException.php
    ├── AppointmentDatabaseException.php
    ├── CalcomBookingException.php
    └── CustomerValidationException.php
```

**Error Handling Strategy:**
```php
// API responses with detailed error information
if ($request->expectsJson() || $request->is('api/*')) {
    return $this->renderJsonResponse($e);
}

// Custom error pages for production
if (!config('app.debug') && $e instanceof HttpException) {
    return $this->renderCustomErrorPage($e);
}
```

**Sensitive Data Protection:**
```php
protected $dontFlash = [
    'current_password',
    'password',
    'password_confirmation',
    'api_key',
    'secret',
    'token',
];
```

**Retry Pattern (External APIs):**
```php
private function retryStripeCall(callable $callback, string $operation, int $maxAttempts = 3): mixed
{
    $attempt = 0;
    while ($attempt < $maxAttempts) {
        try {
            return $callback();
        } catch (ApiErrorException $e) {
            $isRetryable = in_array($e->getHttpStatus(), [429, 500, 502, 503, 504]);
            if (!$isRetryable || $attempt >= $maxAttempts) {
                throw $e;
            }
            $delay = (int) pow(2, $attempt - 1); // Exponential backoff
            sleep($delay);
        }
        $attempt++;
    }
}
```

## Logging

**Framework:** Laravel's `Illuminate\Support\Facades\Log`

**Patterns:**
```php
// Info for successful operations
Log::info('Invoice finalized and sent', [
    'invoice_id' => $invoice->id,
    'stripe_invoice_id' => $sentInvoice->id,
    'total' => $invoice->total,
]);

// Warning for non-critical issues
Log::warning('Stripe API call failed, retrying', [
    'operation' => $operation,
    'attempt' => $attempt,
    'http_status' => $httpStatus,
    'delay_seconds' => $delay,
]);

// Error for failures
Log::error('Failed to create Stripe invoice', [
    'invoice_id' => $invoice->id,
    'error' => $e->getMessage(),
]);
```

**Context Arrays:**
- Always include relevant IDs
- Include operation context
- Use snake_case keys in context arrays

## Comments

**When to Comment:**
- Security-critical code sections
- Complex business logic
- Multi-tenant isolation patterns
- Workarounds for known issues

**Documentation Blocks:**
```php
/**
 * Mass Assignment Protection
 *
 * SECURITY: Protects against VULN-009 - Mass Assignment vulnerability
 * Tenant isolation and financial fields must never be mass-assigned
 */
protected $guarded = [
    'id',
    'company_id',  // Must be set only via service/customer relationship
    // ...
];
```

**Inline Comments:**
```php
// ✅ FIXED: was appointment_made
'has_appointment' => 'boolean',

// 🔒 SECURITY FIX 2025-11-21: Multi-tenant isolation
$query->where('company_id', $user->company_id);
```

**Section Markers:**
```php
// ==========================================
// Customer Management Tests (4 tests)
// ==========================================
```

## Function Design

**Size:**
- Single responsibility
- Typically 20-50 lines
- Extract helpers for complex logic

**Parameters:**
- Type hints required
- Nullable types with `?`
- Default values for optional params

**Return Values:**
- Return type declarations required
- Nullable returns: `?Model`, `?string`
- Array returns documented with PHPDoc

**Example:**
```php
public function createMonthlyInvoice(
    Company $partner,
    Carbon $periodStart,
    Carbon $periodEnd
): AggregateInvoice {
    // Implementation
}
```

## Module Design

**Exports:**
- One class per file
- Public API through service classes
- Interfaces for swappable implementations

**Service Layer Pattern:**
```
app/Services/
├── Billing/
│   ├── StripeInvoicingService.php
│   ├── MonthlyBillingAggregator.php
│   └── FeeService.php
├── Retell/
│   ├── AppointmentCreationService.php
│   ├── CallLifecycleService.php
│   └── WebhookResponseService.php
└── Appointments/
    ├── BookingService.php
    ├── WeeklyAvailabilityService.php
    └── AppointmentModificationService.php
```

## Multi-Tenant Isolation Pattern (CRITICAL)

**Trait Usage:**
```php
class Appointment extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;
}
```

**BelongsToCompany Trait (`app/Traits/BelongsToCompany.php`):**
```php
trait BelongsToCompany
{
    protected static function bootBelongsToCompany(): void
    {
        // Apply global scope for automatic company filtering
        static::addGlobalScope(new CompanyScope);

        // Auto-fill company_id on creation
        static::creating(function (Model $model) {
            if (!$model->company_id && Auth::check()) {
                $model->company_id = Auth::user()->company_id;
            }
        });
    }
}
```

**Guarded Fields Pattern:**
```php
protected $guarded = [
    'id',
    // Multi-tenant isolation (CRITICAL)
    'company_id',
    'branch_id',
    // Financial data (CRITICAL)
    'cost',
    'cost_cents',
    // System timestamps
    'created_at',
    'updated_at',
    'deleted_at',
];
```

## Filament Resource Pattern

**Structure:**
```
app/Filament/Resources/
├── AppointmentResource.php
├── AppointmentResource/
│   ├── Pages/
│   │   ├── ListAppointments.php
│   │   ├── CreateAppointment.php
│   │   ├── EditAppointment.php
│   │   ├── ViewAppointment.php
│   │   └── Calendar.php
│   ├── RelationManagers/
│   └── Widgets/
│       ├── AppointmentStats.php
│       ├── UpcomingAppointments.php
│       └── AppointmentCalendar.php
```

**Navigation Labels (German):**
```php
protected static ?string $navigationLabel = 'Termine';
protected static ?string $navigationGroup = 'CRM';
```

**Status Options Pattern:**
```php
Forms\Components\Select::make('status')
    ->options([
        'pending' => '⏳ Ausstehend',
        'confirmed' => '✅ Bestätigt',
        'in_progress' => '🔄 In Bearbeitung',
        'completed' => '✨ Abgeschlossen',
        'cancelled' => '❌ Storniert',
        'no_show' => '👻 Nicht erschienen',
    ])
```

## Model Casts Pattern

**Standard Casts:**
```php
protected $casts = [
    'metadata' => 'array',
    'is_recurring' => 'boolean',
    'starts_at' => 'datetime',
    'ends_at' => 'datetime',
    'price' => 'decimal:2',
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
];
```

**Integer Cents for Money:**
```php
'cost_cents' => 'integer',
'base_cost' => 'integer',
'platform_profit' => 'integer',
```

---

*Convention analysis: 2026-01-19*
