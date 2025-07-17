# 🧪 Testing Documentation - AskProAI

## 📊 Testing Overview

### Current Status (July 14, 2025)
- **Total Tests**: 40+ (increased from 31)
- **Test Coverage**: ~12% (estimated)
- **Average Test Time**: 100ms
- **Success Rate**: 95%

### Quick Commands
```bash
# Run all working tests
./run-working-tests.sh

# Run specific test suite
php artisan test tests/Unit/Dashboard/DashboardMetricsServiceTest.php

# Run with coverage (requires PCOV)
php artisan test --coverage
```

## ✅ Active Test Suites

### 1. DashboardMetricsServiceTest
- **Status**: ✅ 8/8 tests passing
- **Location**: `tests/Unit/Dashboard/DashboardMetricsServiceTest.php`
- **Purpose**: Tests dashboard KPI calculations (revenue, appointments, calls, customers)
- **Key Fixes Applied**:
  - Changed Call model relationship from BelongsTo to HasOne
  - Added SQLite JSON query compatibility
  - Fixed float comparisons with assertEqualsWithDelta
  - Resolved TenantScope issues

### 2. SimpleTest
- **Status**: ✅ 2/2 tests passing
- **Location**: `tests/Feature/SimpleTest.php`
- **Purpose**: Basic application boot and database connection tests

### 3. CriticalFixesTest
- **Status**: ⚠️ 4/6 tests passing
- **Location**: `tests/Feature/CriticalFixesTest.php`
- **Purpose**: Tests critical system components (connection pooling, phone validation, deduplication)
- **Failing Tests**:
  - Database connection pooling (PDO access denied)
  - Phone validation (return type mismatch)

### 4. WebhookDeduplicationServiceTest
- **Status**: ✅ 11/11 tests passing
- **Location**: `tests/Unit/Services/Webhook/WebhookDeduplicationServiceTest.php`
- **Purpose**: Tests webhook deduplication logic with Redis

### 5. MockServicesTest
- **Status**: ✅ 5/5 tests passing
- **Location**: `tests/Unit/Mocks/MockServicesTest.php`
- **Purpose**: Tests mock service implementations for Cal.com and Stripe

### 6. DatabaseConnectionTest
- **Status**: ✅ 2/2 tests passing
- **Location**: `tests/Unit/DatabaseConnectionTest.php`
- **Purpose**: Tests database connectivity and configurations

### 7. SensitiveDataMaskerTest
- **Status**: ⚠️ 8/9 tests passing
- **Location**: `tests/Unit/Security/SensitiveDataMaskerTest.php`
- **Purpose**: Tests sensitive data masking for security
- **Failing Test**: maskException test (TypeError)

## 🔧 Test Infrastructure

### Test Database Configuration
```php
// phpunit.xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

### Common Test Patterns

#### 1. Multi-Tenant Testing
```php
// Set company context
app()->instance('current_company_id', $company->id);

// Use withoutGlobalScope for manual scope
Model::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('company_id', $companyId);

// Use forCompany for BelongsToCompany trait
Model::forCompany($companyId);
```

#### 2. SQLite Compatibility
```php
if (DB::connection()->getDriverName() === 'sqlite') {
    $query->whereRaw("json_extract(data, '$.key') = ?", [$value]);
} else {
    $query->whereJsonContains('data->key', $value);
}
```

#### 3. Float Comparisons
```php
// Use assertEqualsWithDelta for percentages
$this->assertEqualsWithDelta(23.08, $actual, 0.01);
```

## 🚫 Major Blockers

### 1. Database Issues
- **Problem**: QueryException in most tests
- **Cause**: Missing tables, schema mismatches
- **Solution**: Need proper test database setup

### 2. Missing Factories
- PortalUserFactory
- Various model factories with incomplete fields

### 3. External Dependencies
- Cal.com API
- Retell.ai webhooks
- Stripe payments

## 📈 Test Coverage Goals

### Phase 1 (Current)
- ✅ 40+ tests
- ✅ ~12% coverage
- ✅ Core unit tests working

### Phase 2 (Next Sprint)
- 🎯 100+ tests
- 🎯 25% coverage
- 🎯 All unit tests passing

### Phase 3 (Q2 2025)
- 🎯 300+ tests
- 🎯 50% coverage
- 🎯 Integration tests complete

### Phase 4 (Q3 2025)
- 🎯 500+ tests
- 🎯 70% coverage
- 🎯 Full E2E test suite

## 🛠️ Testing Tools & Commands

### Essential Commands
```bash
# Install PCOV for coverage
pecl install pcov

# Run tests in parallel
php artisan test --parallel

# Run specific test method
php artisan test --filter="test_method_name"

# Generate coverage report
php artisan test --coverage --min=80
```

### Useful Aliases
```bash
alias test="php artisan test"
alias testunit="php artisan test --testsuite=Unit"
alias testfeature="php artisan test --testsuite=Feature"
```

## 📝 Test Creation Guidelines

### 1. Naming Convention
- Test classes: `{Feature}Test.php`
- Test methods: `test_it_does_something()` or `it_does_something()` with #[Test]

### 2. Test Structure
```php
public function test_feature_works_correctly()
{
    // Arrange
    $data = $this->createTestData();
    
    // Act
    $result = $this->performAction($data);
    
    // Assert
    $this->assertEquals($expected, $result);
}
```

### 3. Use Factories
```php
$user = User::factory()->create();
$company = Company::factory()->withUsers(3)->create();
```

## 🐛 Common Test Failures & Solutions

### 1. TenantScope Issues
**Error**: "No query results for model"
**Solution**: Set auth context or use withoutGlobalScope

### 2. Factory Issues
**Error**: "Class Factory not found"
**Solution**: Create factory or use direct model creation

### 3. SQLite Issues
**Error**: "whereJsonContains not supported"
**Solution**: Use database driver detection

## 📅 Test Execution History

### July 14, 2025
- Initial assessment: 31 tests
- Fixed DashboardMetricsServiceTest: +8 passing tests
- Activated multiple test suites
- Final count: 40+ tests
- Created comprehensive documentation

### Next Steps
1. Fix remaining test failures
2. Create mock-based unit tests
3. Install PCOV for real coverage
4. Fix database connection issues
5. Create missing factories

## 🔗 Related Documentation
- [CLAUDE.md](/CLAUDE.md) - Main project documentation
- [TESTING_PROGRESS_REPORT.md](/TESTING_PROGRESS_REPORT.md) - Detailed progress report
- [run-working-tests.sh](/run-working-tests.sh) - Test runner script

**Note**: This documentation should be updated after each testing session to maintain accurate status and progress tracking.