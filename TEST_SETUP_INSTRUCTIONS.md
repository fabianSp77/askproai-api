# Unit Test Setup Instructions

## Created Test Files

The following comprehensive unit tests have been created:

1. **TaxServiceTest** (`tests/Unit/Services/Tax/TaxServiceTest.php`)
   - Tests all tax calculation scenarios
   - Tests Kleinunternehmer (small business) logic
   - Tests threshold monitoring
   - Tests VAT ID validation
   - Tests Stripe tax rate synchronization

2. **EnhancedStripeInvoiceServiceTest** (`tests/Unit/Services/Stripe/EnhancedStripeInvoiceServiceTest.php`)
   - Tests draft invoice creation
   - Tests invoice preview functionality
   - Tests invoice finalization
   - Tests billing period invoice creation
   - Mocks Stripe API calls properly

3. **CustomerPortalServiceTest** (`tests/Unit/Services/CustomerPortalServiceTest.php`)
   - Tests portal access management
   - Tests magic link generation
   - Tests bulk operations
   - Tests authentication features
   - Tests portal feature management

4. **CustomerAuthTest** (`tests/Unit/Models/CustomerAuthTest.php`)
   - Tests authentication methods
   - Tests relationships (company, branch, appointments, calls)
   - Tests portal access checks
   - Tests token generation and verification
   - Tests Sanctum API tokens

5. **VerifyStripeSignatureTest** (`tests/Unit/Http/Middleware/VerifyStripeSignatureTest.php`)
   - Tests signature verification with valid signatures
   - Tests invalid signature handling
   - Tests missing signature scenarios
   - Tests error handling

## Supporting Factory Files Created

- `InvoiceFactory.php`
- `TaxRateFactory.php`
- `BillingPeriodFactory.php`
- `CompanyPricingFactory.php`
- `CustomerAuthFactory.php`

## Known Issues

The test suite encounters migration conflicts when running with SQLite in-memory database. This is due to:

1. Multiple migrations trying to create the same tables
2. SQLite-incompatible index creation syntax in some migrations
3. Duplicate table creation in different migrations

## Running the Tests

### Option 1: Run Tests Individually (Recommended for now)

```bash
# Run TaxService tests
php artisan test tests/Unit/Services/Tax/TaxServiceTest.php --filter="test_method_name"

# Run EnhancedStripeInvoiceService tests
php artisan test tests/Unit/Services/Stripe/EnhancedStripeInvoiceServiceTest.php --filter="test_method_name"

# Run CustomerPortalService tests
php artisan test tests/Unit/Services/CustomerPortalServiceTest.php --filter="test_method_name"

# Run CustomerAuth model tests
php artisan test tests/Unit/Models/CustomerAuthTest.php --filter="test_method_name"

# Run VerifyStripeSignature middleware tests
php artisan test tests/Unit/Http/Middleware/VerifyStripeSignatureTest.php --filter="test_method_name"
```

### Option 2: Fix Migration Issues

To run all tests successfully, you need to:

1. Clean up duplicate migrations
2. Ensure all migrations are SQLite-compatible
3. Use proper migration ordering

### Option 3: Use MySQL for Testing

1. Create a test database:
```sql
CREATE DATABASE askproai_test;
```

2. Update `phpunit.xml` to use MySQL instead of SQLite:
```xml
<env name="DB_CONNECTION" value="mysql"/>
<env name="DB_DATABASE" value="askproai_test"/>
```

3. Run tests:
```bash
php artisan test tests/Unit/Services/Tax/TaxServiceTest.php
```

## Test Coverage

All tests include comprehensive coverage of:

- **Happy path scenarios** - Normal expected behavior
- **Edge cases** - Boundary conditions and special cases
- **Error handling** - Exception scenarios and error responses
- **Mocking** - External services (Stripe, notifications) are properly mocked
- **Assertions** - Detailed assertions to verify correct behavior

## Mocking Strategy

The tests use Mockery for mocking dependencies:

- **Stripe API** - All Stripe operations are mocked to avoid real API calls
- **Notifications** - Email notifications are faked using Laravel's Notification::fake()
- **HTTP calls** - External HTTP requests (VIES VAT validation) use Http::fake()
- **Logging** - Log assertions verify proper error logging

## Best Practices Implemented

1. **Isolation** - Each test is independent and doesn't rely on others
2. **Clarity** - Test names clearly describe what is being tested
3. **Completeness** - Both positive and negative scenarios are tested
4. **Performance** - Tests run quickly by avoiding real external calls
5. **Maintainability** - Tests are well-structured and easy to update

## Future Improvements

1. Fix migration conflicts for seamless test execution
2. Add integration tests for end-to-end scenarios
3. Add performance benchmarks for critical operations
4. Implement continuous integration (CI) pipeline
5. Add code coverage reporting

## Example Test Execution

```bash
# Run a specific test class
php artisan test tests/Unit/Services/Tax/TaxServiceTest.php

# Run with coverage (requires Xdebug or PCOV)
php artisan test --coverage tests/Unit/Services/Tax/TaxServiceTest.php

# Run in parallel (when migrations are fixed)
php artisan test --parallel

# Run with detailed output
php artisan test tests/Unit/Services/Tax/TaxServiceTest.php -vvv
```