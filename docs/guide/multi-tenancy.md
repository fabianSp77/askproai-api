# Multi-Tenancy

AskPro API Gateway implements a robust multi-tenant architecture ensuring complete data isolation between companies.

## Overview

Each company (tenant) has:
- Isolated database records
- Separate configuration
- Independent users and customers
- Own service categories and SLA settings

## Implementation

### CompanyScope Global Scope

All tenant-scoped models use automatic query scoping:

```php
// app/Models/Traits/BelongsToCompany.php
trait BelongsToCompany
{
    protected static function bootBelongsToCompany()
    {
        static::addGlobalScope(new CompanyScope);

        static::creating(function ($model) {
            if (!$model->company_id) {
                $model->company_id = auth()->user()?->company_id;
            }
        });
    }
}
```

### Setting Tenant Context

The tenant context is set via middleware:

```php
// Middleware sets company context
public function handle($request, $next)
{
    if ($user = auth()->user()) {
        app()->instance('current_company_id', $user->company_id);
    }
    return $next($request);
}
```

## Tenant-Scoped Models

These models are automatically filtered by company:

| Model | Description |
|-------|-------------|
| Customer | End-users |
| Appointment | Bookings |
| Call | Voice records |
| ServiceCase | Support tickets |
| ServiceCaseCategory | Ticket categories |
| Staff | Employees |
| Branch | Locations |
| Service | Offered services |

## Cross-Tenant Operations

Some models are NOT tenant-scoped (system-wide):

- `User` - Can access multiple companies via roles
- `Company` - The tenant itself
- `SystemSettings` - Global configuration

### Super Admin Access

Super admins can access all tenant data:

```php
// Temporarily disable scope for admin operations
ServiceCase::withoutGlobalScope(CompanyScope::class)
    ->where('status', 'open')
    ->get();
```

## Data Isolation Verification

### Database Constraints

```sql
-- All tenant tables have company_id foreign key
ALTER TABLE service_cases
ADD CONSTRAINT fk_company
FOREIGN KEY (company_id) REFERENCES companies(id);
```

### Query Verification

```php
// All queries automatically include WHERE company_id = ?
ServiceCase::all();
// SELECT * FROM service_cases WHERE company_id = 1
```

## Configuration per Tenant

### Company Settings

```php
// config/companyscope.php
return [
    'default_company_id' => env('DEFAULT_COMPANY_ID', 1),
    'enforce_scope' => env('ENFORCE_COMPANY_SCOPE', true),
];
```

### Tenant-Specific Features

| Setting | Description |
|---------|-------------|
| gateway_mode | appointment / service_desk / hybrid |
| sla_tracking_enabled | Enable SLA monitoring |
| email_notifications | Enable email alerts |
| webhook_url | Custom webhook endpoint |

## Testing Multi-Tenancy

```php
// Test data isolation
public function test_tenant_isolation()
{
    $company1 = Company::factory()->create();
    $company2 = Company::factory()->create();

    $case1 = ServiceCase::factory()
        ->for($company1)
        ->create();

    // Acting as company2 user should not see company1 data
    $this->actingAs(User::factory()->for($company2)->create());

    $this->assertEmpty(ServiceCase::all());
}
```

## Security Considerations

1. **Never trust client-provided company_id** - Always use auth context
2. **Validate cross-tenant references** - Check foreign keys belong to same tenant
3. **Audit tenant access** - Log cross-tenant operations
4. **Test isolation regularly** - Automated tests for data boundaries
