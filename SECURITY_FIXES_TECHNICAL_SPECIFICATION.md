# Security Fixes Technical Specification

## 1. Phone Number Validation mit libphonenumber

### 1.1 Current State Analysis
- **PhoneNumberResolver**: Simplistic normalization with regex only
  - Removes non-numeric characters
  - Basic German number handling (0 → +49)
  - No proper validation or formatting
- **CustomerService**: Basic regex cleaning, phone variant generation
- **Multiple locations** handle phone numbers without standardization

### 1.2 Integration Requirements

#### 1.2.1 Dependency Installation
```bash
composer require giggsey/libphonenumber-for-php
```

#### 1.2.2 PhoneNumberValidator Service
```php
namespace App\Services\Validation;

use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumber;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;

class PhoneNumberValidator
{
    private PhoneNumberUtil $phoneUtil;
    private string $defaultRegion;
    
    public function __construct(string $defaultRegion = 'DE')
    {
        $this->phoneUtil = PhoneNumberUtil::getInstance();
        $this->defaultRegion = $defaultRegion;
    }
    
    /**
     * Validate and normalize phone number to E.164 format
     * 
     * @return array{valid: bool, formatted: ?string, errors: array}
     */
    public function validate(string $phoneNumber, ?string $region = null): array
    {
        try {
            $parsedNumber = $this->phoneUtil->parse(
                $phoneNumber, 
                $region ?? $this->defaultRegion
            );
            
            $isValid = $this->phoneUtil->isValidNumber($parsedNumber);
            
            return [
                'valid' => $isValid,
                'formatted' => $isValid ? 
                    $this->phoneUtil->format($parsedNumber, PhoneNumberFormat::E164) : 
                    null,
                'errors' => $isValid ? [] : ['Invalid phone number for region'],
                'type' => $isValid ? 
                    $this->phoneUtil->getNumberType($parsedNumber) : 
                    null,
                'region' => $isValid ?
                    $this->phoneUtil->getRegionCodeForNumber($parsedNumber) :
                    null
            ];
        } catch (NumberParseException $e) {
            return [
                'valid' => false,
                'formatted' => null,
                'errors' => [$e->getMessage()],
                'type' => null,
                'region' => null
            ];
        }
    }
    
    /**
     * Format phone number for display
     */
    public function formatForDisplay(string $phoneNumber, ?string $region = null): ?string
    {
        try {
            $parsedNumber = $this->phoneUtil->parse(
                $phoneNumber, 
                $region ?? $this->defaultRegion
            );
            
            if ($this->phoneUtil->isValidNumber($parsedNumber)) {
                return $this->phoneUtil->format(
                    $parsedNumber, 
                    PhoneNumberFormat::INTERNATIONAL
                );
            }
        } catch (NumberParseException $e) {
            // Log but don't throw
        }
        
        return null;
    }
    
    /**
     * Generate search variants for phone number matching
     */
    public function generateSearchVariants(string $phoneNumber): array
    {
        $variants = [];
        
        try {
            $parsedNumber = $this->phoneUtil->parse(
                $phoneNumber, 
                $this->defaultRegion
            );
            
            if ($this->phoneUtil->isValidNumber($parsedNumber)) {
                // E.164 format (primary storage format)
                $variants[] = $this->phoneUtil->format(
                    $parsedNumber, 
                    PhoneNumberFormat::E164
                );
                
                // National format
                $variants[] = $this->phoneUtil->format(
                    $parsedNumber, 
                    PhoneNumberFormat::NATIONAL
                );
                
                // International format
                $variants[] = $this->phoneUtil->format(
                    $parsedNumber, 
                    PhoneNumberFormat::INTERNATIONAL
                );
                
                // Raw digits only
                $variants[] = preg_replace('/[^0-9]/', '', $phoneNumber);
            }
        } catch (NumberParseException $e) {
            // Fallback to basic cleaning
            $variants[] = preg_replace('/[^0-9+]/', '', $phoneNumber);
        }
        
        return array_unique(array_filter($variants));
    }
}
```

#### 1.2.3 Integration Points

1. **Model Mutators** (Customer, Branch, PhoneNumber models):
```php
// In Customer model
protected function phone(): Attribute
{
    return Attribute::make(
        get: fn ($value) => $value,
        set: fn ($value) => app(PhoneNumberValidator::class)
            ->validate($value)['formatted'] ?? $value
    );
}
```

2. **Form Requests Validation Rule**:
```php
namespace App\Rules;

class ValidPhoneNumber implements Rule
{
    private string $region;
    private array $errors = [];
    
    public function __construct(string $region = 'DE')
    {
        $this->region = $region;
    }
    
    public function passes($attribute, $value): bool
    {
        $result = app(PhoneNumberValidator::class)->validate($value, $this->region);
        $this->errors = $result['errors'];
        return $result['valid'];
    }
    
    public function message(): string
    {
        return !empty($this->errors) 
            ? implode(', ', $this->errors)
            : 'The :attribute must be a valid phone number.';
    }
}
```

3. **Update Services**:
- PhoneNumberResolver: Use PhoneNumberValidator for normalization
- CustomerService: Replace generatePhoneVariants with validator method
- AppointmentBookingService: Validate customer phone before processing
- RetellWebhookHandler: Normalize incoming phone numbers

#### 1.2.4 Migration Strategy

1. **Add normalized_phone columns**:
```php
Schema::table('customers', function (Blueprint $table) {
    $table->string('phone_normalized', 20)->nullable()->index();
    $table->string('phone_region', 2)->nullable();
});

Schema::table('branches', function (Blueprint $table) {
    $table->string('phone_normalized', 20)->nullable()->index();
});
```

2. **Data Migration Command**:
```php
class NormalizeExistingPhoneNumbers extends Command
{
    public function handle(PhoneNumberValidator $validator)
    {
        // Process in chunks to avoid memory issues
        Customer::chunk(1000, function ($customers) use ($validator) {
            foreach ($customers as $customer) {
                if ($customer->phone) {
                    $result = $validator->validate($customer->phone);
                    if ($result['valid']) {
                        $customer->phone_normalized = $result['formatted'];
                        $customer->phone_region = $result['region'];
                        $customer->save();
                    } else {
                        $this->warn("Invalid phone for customer {$customer->id}: {$customer->phone}");
                    }
                }
            }
        });
    }
}
```

### 1.3 Error Handling Strategy

1. **Validation Failures**:
   - Log invalid numbers with context
   - Store original value, flag as needs_validation
   - Queue for manual review

2. **Webhook Processing**:
   - Accept webhook even with invalid phone
   - Create customer with validation flag
   - Send alert to admin for review

3. **Search Fallbacks**:
   - Try normalized search first
   - Fall back to original value search
   - Use fuzzy matching as last resort

## 2. SQL Injection Prevention

### 2.1 Current Vulnerabilities Identified

1. **Raw Query Usage Locations**:
   - DashboardController: `selectRaw('DATE(call_time) as date, COUNT(*) as count')`
   - ReportsController: Multiple `selectRaw` with string concatenation
   - Various Filament widgets using `DB::raw()`
   - TenantScope: `whereRaw('1 = 0')` (safe but should be replaced)

2. **Risk Levels**:
   - **HIGH**: Any place accepting user input in raw queries
   - **MEDIUM**: Dashboard/reporting queries with date functions
   - **LOW**: Static raw queries without user input

### 2.2 Safe Query Builder Patterns

#### 2.2.1 QuerySanitizer Service
```php
namespace App\Services\Security;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Query\Expression;

class QuerySanitizer
{
    private array $allowedColumns = [];
    private array $allowedOperators = ['=', '!=', '<', '>', '<=', '>=', 'like', 'not like'];
    private array $allowedFunctions = ['DATE', 'COUNT', 'SUM', 'AVG', 'MAX', 'MIN'];
    
    /**
     * Set allowed columns for the current query
     */
    public function allowColumns(array $columns): self
    {
        $this->allowedColumns = $columns;
        return $this;
    }
    
    /**
     * Validate and sanitize column name
     */
    public function sanitizeColumn(string $column): string
    {
        // Remove any SQL injection attempts
        $column = preg_replace('/[^a-zA-Z0-9_.]/', '', $column);
        
        // Check against whitelist if provided
        if (!empty($this->allowedColumns) && !in_array($column, $this->allowedColumns)) {
            throw new \InvalidArgumentException("Column '{$column}' is not allowed");
        }
        
        // Escape with backticks for MySQL
        $parts = explode('.', $column);
        return implode('.', array_map(fn($part) => "`{$part}`", $parts));
    }
    
    /**
     * Validate operator
     */
    public function validateOperator(string $operator): string
    {
        $operator = strtolower($operator);
        if (!in_array($operator, $this->allowedOperators)) {
            throw new \InvalidArgumentException("Operator '{$operator}' is not allowed");
        }
        return $operator;
    }
    
    /**
     * Safe date aggregation
     */
    public function dateTrunc(string $column, string $unit = 'day'): Expression
    {
        $column = $this->sanitizeColumn($column);
        $allowedUnits = ['day', 'week', 'month', 'year'];
        
        if (!in_array($unit, $allowedUnits)) {
            throw new \InvalidArgumentException("Date unit '{$unit}' is not allowed");
        }
        
        return DB::raw("DATE({$column})");
    }
    
    /**
     * Safe aggregation function
     */
    public function aggregate(string $function, string $column): Expression
    {
        $function = strtoupper($function);
        if (!in_array($function, $this->allowedFunctions)) {
            throw new \InvalidArgumentException("Function '{$function}' is not allowed");
        }
        
        $column = $this->sanitizeColumn($column);
        return DB::raw("{$function}({$column})");
    }
    
    /**
     * Build safe WHERE clause from user input
     */
    public function buildWhereClause($query, array $filters)
    {
        foreach ($filters as $filter) {
            $column = $this->sanitizeColumn($filter['column']);
            $operator = $this->validateOperator($filter['operator'] ?? '=');
            $value = $filter['value'];
            
            // Use parameter binding
            $query->whereRaw("{$column} {$operator} ?", [$value]);
        }
        
        return $query;
    }
}
```

#### 2.2.2 Safe Query Builder Trait
```php
namespace App\Traits;

use App\Services\Security\QuerySanitizer;

trait SafeQueryBuilder
{
    protected function safeSelectRaw($query, string $expression, array $bindings = [])
    {
        // Validate expression doesn't contain dangerous patterns
        $dangerous = ['union', 'insert', 'update', 'delete', 'drop', '--', '/*', '*/'];
        $lowerExpression = strtolower($expression);
        
        foreach ($dangerous as $pattern) {
            if (str_contains($lowerExpression, $pattern)) {
                throw new \InvalidArgumentException("Potentially dangerous SQL pattern detected");
            }
        }
        
        return $query->selectRaw($expression, $bindings);
    }
    
    protected function safeDateGrouping($query, string $column, string $alias = 'date')
    {
        $sanitizer = new QuerySanitizer();
        $dateExpr = $sanitizer->dateTrunc($column);
        
        return $query->selectRaw("{$dateExpr} as {$alias}")
                     ->groupBy($alias);
    }
}
```

### 2.3 Migration Plan

1. **Phase 1: Critical Fixes** (Immediate)
   - Replace all user-input based raw queries
   - Add parameter binding to existing raw queries
   - Implement QuerySanitizer service

2. **Phase 2: Refactoring** (1 week)
   - Convert dashboard queries to use Eloquent/Query Builder
   - Implement safe aggregation helpers
   - Add query logging for audit

3. **Phase 3: Prevention** (2 weeks)
   - Static analysis rules (PHPStan/Psalm)
   - Code review checklist
   - Developer training on SQL injection

### 2.4 Specific Fixes

1. **DashboardController**:
```php
// Before (vulnerable)
$callsByDay = DB::table('calls')
    ->selectRaw('DATE(call_time) as date, COUNT(*) as count')
    ->groupBy('date')
    ->get();

// After (safe)
$callsByDay = DB::table('calls')
    ->select(DB::raw('DATE(call_time) as date'))
    ->selectRaw('COUNT(*) as count')
    ->groupBy('date')
    ->get();
```

2. **Dynamic Column Filtering**:
```php
// Before (vulnerable)
$column = request('sort_by');
$query->orderByRaw($column . ' DESC');

// After (safe)
$sanitizer = new QuerySanitizer();
$sanitizer->allowColumns(['name', 'created_at', 'email']);
$column = $sanitizer->sanitizeColumn(request('sort_by', 'created_at'));
$query->orderBy($column, 'DESC');
```

## 3. Multi-Tenancy Security Enhancements

### 3.1 Current Issues Identified

1. **Silent Failures**: TenantScope returns empty results when no company context
2. **No Audit Trail**: Tenant access not logged
3. **Potential Bypass**: Direct DB queries bypass scope
4. **Missing Validation**: No validation when setting company context

### 3.2 Enhanced TenantScope Implementation

#### 3.2.1 Strict TenantScope with Exceptions
```php
namespace App\Scopes;

use App\Exceptions\TenantContextException;
use Illuminate\Support\Facades\Log;

class StrictTenantScope extends TenantScope
{
    public function apply(Builder $builder, Model $model): void
    {
        $companyId = $this->getCurrentCompanyId();
        
        if ($companyId) {
            $builder->where($model->getTable() . '.company_id', $companyId);
            
            // Audit log
            $this->logTenantAccess($model, $companyId);
        } else {
            // Throw exception instead of silent failure
            throw new TenantContextException(
                'No tenant context available for model ' . get_class($model)
            );
        }
    }
    
    private function logTenantAccess($model, $companyId): void
    {
        if (config('tenant.audit_access', false)) {
            Log::channel('tenant_audit')->info('Tenant access', [
                'model' => get_class($model),
                'company_id' => $companyId,
                'user_id' => auth()->id(),
                'ip' => request()->ip(),
                'url' => request()->fullUrl(),
            ]);
        }
    }
}
```

#### 3.2.2 TenantContextException
```php
namespace App\Exceptions;

class TenantContextException extends \Exception
{
    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Tenant context not available',
                'message' => 'Unable to determine organization context for this request'
            ], 403);
        }
        
        // Log security event
        Log::channel('security')->warning('Tenant context missing', [
            'user_id' => auth()->id(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip()
        ]);
        
        // Redirect to tenant selection or error page
        return redirect()
            ->route('tenant.select')
            ->with('error', 'Please select an organization to continue');
    }
}
```

#### 3.2.3 Tenant Context Manager
```php
namespace App\Services\Security;

use App\Models\Company;
use Illuminate\Support\Facades\Cache;

class TenantContextManager
{
    private ?int $currentCompanyId = null;
    
    /**
     * Set current tenant with validation
     */
    public function setTenant(int $companyId): void
    {
        // Validate company exists and user has access
        $company = Company::findOrFail($companyId);
        
        if (!$this->userHasAccessToCompany($company)) {
            throw new \InvalidArgumentException('User does not have access to this company');
        }
        
        $this->currentCompanyId = $companyId;
        app()->instance('current_company_id', $companyId);
        
        // Store in session for web requests
        if (!app()->runningInConsole()) {
            session(['current_company_id' => $companyId]);
        }
        
        $this->logTenantSwitch($companyId);
    }
    
    /**
     * Get current tenant with validation
     */
    public function getCurrentTenant(): ?Company
    {
        if (!$this->currentCompanyId) {
            return null;
        }
        
        return Cache::remember(
            "company:{$this->currentCompanyId}",
            300,
            fn() => Company::find($this->currentCompanyId)
        );
    }
    
    /**
     * Execute closure in tenant context
     */
    public function forTenant(int $companyId, \Closure $callback)
    {
        $previousId = $this->currentCompanyId;
        
        try {
            $this->setTenant($companyId);
            return $callback();
        } finally {
            // Restore previous context
            if ($previousId) {
                $this->setTenant($previousId);
            } else {
                $this->clearTenant();
            }
        }
    }
    
    /**
     * Clear tenant context
     */
    public function clearTenant(): void
    {
        $this->currentCompanyId = null;
        app()->forgetInstance('current_company_id');
        session()->forget('current_company_id');
    }
    
    private function userHasAccessToCompany(Company $company): bool
    {
        if (!auth()->check()) {
            return false;
        }
        
        $user = auth()->user();
        
        // Super admins have access to all
        if ($user->hasRole('super_admin')) {
            return true;
        }
        
        // Check if user belongs to company
        return $user->companies->contains($company->id);
    }
    
    private function logTenantSwitch(int $companyId): void
    {
        Log::channel('tenant_audit')->info('Tenant context switched', [
            'company_id' => $companyId,
            'user_id' => auth()->id(),
            'previous_company_id' => $this->currentCompanyId,
            'timestamp' => now()->toIso8601String()
        ]);
    }
}
```

### 3.3 Fallback Strategies

1. **Queue Jobs**:
```php
class TenantAwareJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public int $companyId;
    
    public function __construct(int $companyId)
    {
        $this->companyId = $companyId;
    }
    
    public function handle(TenantContextManager $tenantManager)
    {
        // Set tenant context for job
        $tenantManager->setTenant($this->companyId);
        
        try {
            $this->processJob();
        } catch (\Exception $e) {
            Log::error('Job failed in tenant context', [
                'company_id' => $this->companyId,
                'job' => static::class,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
```

2. **Console Commands**:
```php
trait InteractsWithTenant
{
    protected function askForTenant(): int
    {
        $companies = Company::all(['id', 'name']);
        
        if ($companies->isEmpty()) {
            $this->error('No companies found');
            return 0;
        }
        
        if ($companies->count() === 1) {
            return $companies->first()->id;
        }
        
        $choice = $this->choice(
            'Select company',
            $companies->pluck('name', 'id')->toArray()
        );
        
        return (int) array_search($choice, $companies->pluck('name', 'id')->toArray());
    }
    
    protected function runInTenantContext(int $companyId, \Closure $callback)
    {
        return app(TenantContextManager::class)->forTenant($companyId, $callback);
    }
}
```

### 3.4 Middleware for Tenant Validation
```php
namespace App\Http\Middleware;

class EnsureTenantContext
{
    public function handle($request, \Closure $next)
    {
        $tenantManager = app(TenantContextManager::class);
        
        // Try to resolve tenant from various sources
        $companyId = $this->resolveTenantId($request);
        
        if (!$companyId) {
            throw new TenantContextException('Unable to determine tenant context');
        }
        
        $tenantManager->setTenant($companyId);
        
        return $next($request);
    }
    
    private function resolveTenantId($request): ?int
    {
        // 1. From route parameter
        if ($request->route('company')) {
            return (int) $request->route('company');
        }
        
        // 2. From authenticated user
        if ($request->user() && $request->user()->company_id) {
            return $request->user()->company_id;
        }
        
        // 3. From header (API requests)
        if ($request->hasHeader('X-Company-ID')) {
            return (int) $request->header('X-Company-ID');
        }
        
        // 4. From subdomain
        $subdomain = explode('.', $request->getHost())[0];
        if ($subdomain && $subdomain !== 'www') {
            $company = Company::where('subdomain', $subdomain)->first();
            return $company?->id;
        }
        
        return null;
    }
}
```

## Implementation Roadmap

### Phase 1: Critical Security Fixes (Week 1)
1. Implement PhoneNumberValidator service
2. Fix SQL injection vulnerabilities in controllers
3. Add TenantContextException handling

### Phase 2: Data Migration & Testing (Week 2)
1. Run phone number normalization migration
2. Update all phone search queries
3. Comprehensive testing of tenant isolation

### Phase 3: Monitoring & Hardening (Week 3)
1. Implement audit logging
2. Add security monitoring dashboards
3. Developer training and documentation

### Phase 4: Continuous Improvement
1. Regular security audits
2. Dependency updates
3. Penetration testing

## Testing Strategy

### Unit Tests
```php
class PhoneNumberValidatorTest extends TestCase
{
    public function test_validates_german_numbers()
    {
        $validator = new PhoneNumberValidator('DE');
        
        $result = $validator->validate('030 837 93 369');
        $this->assertTrue($result['valid']);
        $this->assertEquals('+493083793369', $result['formatted']);
    }
    
    public function test_rejects_invalid_numbers()
    {
        $validator = new PhoneNumberValidator('DE');
        
        $result = $validator->validate('123');
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
    }
}
```

### Integration Tests
```php
class TenantIsolationTest extends TestCase
{
    public function test_throws_exception_without_context()
    {
        $this->expectException(TenantContextException::class);
        
        // Clear any tenant context
        app(TenantContextManager::class)->clearTenant();
        
        // This should throw
        Customer::all();
    }
    
    public function test_isolates_data_between_tenants()
    {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();
        
        $tenantManager = app(TenantContextManager::class);
        
        // Create customer for company 1
        $tenantManager->forTenant($company1->id, function() {
            Customer::factory()->create(['name' => 'Company 1 Customer']);
        });
        
        // Create customer for company 2
        $tenantManager->forTenant($company2->id, function() {
            Customer::factory()->create(['name' => 'Company 2 Customer']);
        });
        
        // Verify isolation
        $tenantManager->setTenant($company1->id);
        $customers = Customer::all();
        $this->assertCount(1, $customers);
        $this->assertEquals('Company 1 Customer', $customers->first()->name);
    }
}
```

## Monitoring & Alerts

### Security Events to Monitor
1. Failed tenant context resolutions
2. SQL injection attempt patterns
3. Invalid phone number submissions (potential spam)
4. Unusual cross-tenant access patterns

### Alert Configurations
```php
// config/security-monitoring.php
return [
    'alerts' => [
        'tenant_context_failures' => [
            'threshold' => 10, // per hour
            'action' => 'email|slack',
            'severity' => 'high'
        ],
        'sql_injection_attempts' => [
            'threshold' => 1,
            'action' => 'email|slack|sms',
            'severity' => 'critical'
        ],
        'invalid_phone_spam' => [
            'threshold' => 50, // per hour
            'action' => 'email',
            'severity' => 'medium'
        ]
    ]
];
```

## Documentation Updates Required

1. **Developer Guide**: Safe query patterns, phone validation usage
2. **API Documentation**: Phone number format requirements
3. **Operations Manual**: Security monitoring procedures
4. **Migration Guide**: Steps for existing installations