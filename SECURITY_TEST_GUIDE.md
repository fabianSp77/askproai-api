# AskProAI Security Test Infrastructure Guide

> 🔒 **Comprehensive security vulnerability testing for AskProAI platform**
> 
> **Status**: Production Ready | **Last Updated**: 2025-08-02 | **Version**: 1.0

## 📋 Table of Contents

- [Overview](#overview)
- [Quick Start](#quick-start)
- [Test Categories](#test-categories)
- [Usage Examples](#usage-examples)
- [Test Architecture](#test-architecture)
- [Critical Security Tests](#critical-security-tests)
- [Test Runner Features](#test-runner-features)
- [Continuous Integration](#continuous-integration)
- [Troubleshooting](#troubleshooting)
- [Contributing](#contributing)

## 🎯 Overview

The AskProAI Security Test Infrastructure is a comprehensive testing suite designed to identify and prevent security vulnerabilities in the multi-tenant SaaS platform. It focuses on the most critical areas where data breaches and security compromises could occur.

### 🚨 Critical Focus Areas

1. **Cross-Tenant Data Isolation** - Preventing data leakage between companies
2. **Admin API Security** - Protecting administrative functions from unauthorized access
3. **Session Management** - Ensuring secure user sessions across different contexts
4. **Input Validation** - Blocking injection attacks and malicious input
5. **Webhook Security** - Preventing data contamination via external webhooks
6. **File System Security** - Securing file uploads and access controls
7. **Database Security** - Protecting against SQL injection and data exposure

### 🎯 Security Test Philosophy

- **Zero Trust**: Assume all input is malicious until proven otherwise
- **Defense in Depth**: Multiple layers of security validation
- **Tenant Isolation**: Absolute separation between company data
- **Fail Securely**: When tests fail, they should fail securely

## 🚀 Quick Start

### Prerequisites

```bash
# Ensure Laravel environment is set up
composer install
php artisan key:generate

# Verify database connection
php artisan tinker --execute="DB::connection()->getPdo();"

# Check test environment
php artisan env
```

### Basic Usage

```bash
# Run critical security tests (recommended first step)
./security-test-runner.sh --critical

# Run all security tests
./security-test-runner.sh --all

# Fast security check (under 30 seconds)
./security-test-runner.sh --fast

# Run with coverage report
./security-test-runner.sh --all --coverage --report
```

### Emergency Security Check

```bash
# When you suspect a security issue
./security-test-runner.sh --critical --verbose

# Continuous monitoring mode
./security-test-runner.sh --continuous
```

## 🔍 Test Categories

### 🔴 Critical Severity Tests

These tests detect vulnerabilities that could lead to complete system compromise:

#### 1. Cross-Tenant Authentication Test
- **File**: `tests/Feature/Security/CrossTenantAuthenticationTest.php`
- **Purpose**: Ensures absolute data isolation between companies
- **Key Tests**:
  - Admin users cannot access other company data
  - Portal users are restricted to their company
  - Database queries respect tenant scope
  - Bulk operations don't affect other companies

#### 2. Admin API Access Control Test
- **File**: `tests/Feature/Security/AdminApiAccessControlTest.php`
- **Purpose**: Protects administrative functions from unauthorized access
- **Key Tests**:
  - All admin endpoints require authentication
  - Cross-company access is blocked
  - Privilege escalation is prevented
  - API responses don't leak sensitive data

#### 3. Webhook Data Contamination Test
- **File**: `tests/Feature/Security/WebhookDataContaminationTest.php`
- **Purpose**: Prevents external webhooks from contaminating company data
- **Key Tests**:
  - Retell.ai webhooks route to correct company
  - Cal.com webhooks respect company boundaries
  - Signature verification blocks tampering
  - Payload validation prevents injection

#### 4. Authentication Bypass Test
- **File**: `tests/Feature/Security/AuthenticationBypassTest.php`
- **Purpose**: Prevents unauthorized system access
- **Key Tests**:
  - Brute force protection
  - Weak password rejection
  - Session token manipulation protection
  - Privilege escalation prevention

#### 5. Database Security Test
- **File**: `tests/Feature/Security/DatabaseSecurityTest.php`
- **Purpose**: Protects against database-level attacks
- **Key Tests**:
  - SQL injection protection
  - Database privilege escalation
  - Row-level security enforcement
  - Connection security validation

### 🟡 Medium Severity Tests

These tests detect important security issues that could lead to data exposure:

#### 6. Session Isolation Test
- **File**: `tests/Feature/Security/SessionIsolationTest.php`
- **Purpose**: Ensures secure session management
- **Key Tests**:
  - Sessions isolated between companies
  - Session fixation protection
  - Concurrent session handling
  - Session data encryption

#### 7. Input Validation Security Test
- **File**: `tests/Feature/Security/InputValidationSecurityTest.php`
- **Purpose**: Blocks injection attacks and malicious input
- **Key Tests**:
  - SQL injection protection across all endpoints
  - XSS protection in input fields
  - File upload security
  - Command injection prevention

#### 8. Data Leakage Test
- **File**: `tests/Feature/Security/DataLeakageTest.php`
- **Purpose**: Prevents sensitive data exposure
- **Key Tests**:
  - API responses don't contain sensitive data
  - Error messages don't leak information
  - Logs don't contain sensitive data
  - Search functionality isolation

#### 9. API Security Vulnerabilities Test
- **File**: `tests/Feature/Security/ApiSecurityVulnerabilitiesTest.php`
- **Purpose**: Secures API endpoints against various attacks
- **Key Tests**:
  - CORS configuration security
  - HTTP method restrictions
  - Content-type validation
  - Response header security

#### 10. File System Security Test
- **File**: `tests/Feature/Security/FileSystemSecurityTest.php`
- **Purpose**: Secures file operations and storage
- **Key Tests**:
  - Path traversal protection
  - Executable file prevention
  - File access control
  - Storage permissions

## 📖 Usage Examples

### Development Workflow

```bash
# Before pushing code changes
./security-test-runner.sh --fast

# Before deployment
./security-test-runner.sh --all --report

# After security updates
./security-test-runner.sh --critical --verbose
```

### CI/CD Integration

```yaml
# .github/workflows/security.yml
name: Security Tests
on: [push, pull_request]
jobs:
  security:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.2
      - name: Install dependencies
        run: composer install
      - name: Run security tests
        run: ./security-test-runner.sh --all --coverage
```

### Production Monitoring

```bash
# Run as cron job every hour
0 * * * * cd /var/www/api-gateway && ./security-test-runner.sh --fast >> /var/log/security-monitor.log 2>&1

# Daily comprehensive check
0 2 * * * cd /var/www/api-gateway && ./security-test-runner.sh --all --report
```

## 🏗️ Test Architecture

### BaseSecurityTestCase

The foundation class providing reusable security testing utilities:

```php
abstract class BaseSecurityTestCase extends TestCase
{
    protected Company $company1;
    protected Company $company2;
    protected User $admin1;
    protected PortalUser $portalUser1;
    
    // Helper methods
    protected function assertCrossTenantAccessPrevented(...)
    protected function assertApiRequiresAuthentication(...)
    protected function assertSqlInjectionProtection(...)
    protected function assertXssProtection(...)
    // ... and many more
}
```

### Key Helper Methods

- `assertCrossTenantAccessPrevented()` - Verifies tenant isolation
- `assertApiRequiresAuthentication()` - Checks authentication requirements
- `assertSqlInjectionProtection()` - Tests SQL injection defenses
- `assertXssProtection()` - Validates XSS protection
- `assertFileUploadSecurity()` - Tests file upload security
- `assertSessionIsolation()` - Verifies session isolation
- `assertWebhookDataIsolation()` - Tests webhook data protection

### Test Data Management

```php
protected function createTestData(Company $company): array
{
    return [
        'branch' => Branch::factory()->create(['company_id' => $company->id]),
        'service' => Service::factory()->create(['company_id' => $company->id]),
        'customer' => Customer::factory()->create(['company_id' => $company->id]),
        'appointment' => Appointment::factory()->create(['company_id' => $company->id]),
        'call' => Call::factory()->create(['company_id' => $company->id]),
    ];
}
```

## 🔧 Test Runner Features

### Command Line Options

```bash
./security-test-runner.sh [options]

Options:
  --all          Run all security tests
  --critical     Run only critical severity tests  
  --fast         Run quick security checks only
  --coverage     Generate test coverage report
  --report       Generate detailed security report
  --continuous   Run in continuous monitoring mode
  --parallel     Run tests in parallel (faster)
  --verbose      Verbose output with detailed logging
  --help         Show help message
```

### Output Examples

#### Successful Run
```
╔══════════════════════════════════════════════════════════════════╗
║                    AskProAI Security Test Runner                 ║
║  Comprehensive security vulnerability testing for AskProAI       ║
╚══════════════════════════════════════════════════════════════════╝

[INFO] Running CRITICAL security tests...
[INFO] Running test [1/5]: CrossTenantAuthenticationTest
[INFO] ✅ PASSED: CrossTenantAuthenticationTest (12s)
[INFO] Running test [2/5]: AdminApiAccessControlTest
[INFO] ✅ PASSED: AdminApiAccessControlTest (8s)
...

Security Test Summary:
Passed: 5
Failed: 0
Total:  5

🔒 Security tests completed successfully!
```

#### Failed Run with Issues
```
[ERROR] ❌ FAILED: CrossTenantAuthenticationTest (15s)
[ERROR] ❌ FAILED: AdminApiAccessControlTest (10s)

Security Test Summary:
Passed: 3
Failed: 2
Total:  5

Failed tests:
  • CrossTenantAuthenticationTest
  • AdminApiAccessControlTest

🚨 Security issues detected! Please review failed tests.
```

### Report Generation

The test runner generates both HTML and JSON reports:

- **HTML Report**: `security-reports/security-report-TIMESTAMP.html`
- **JSON Summary**: `security-reports/security-summary-TIMESTAMP.json`

### Logging

All security test activities are logged to:
- `storage/logs/security/security-tests.log`
- Timestamped entries with severity levels
- Detailed test execution information

## 🔄 Continuous Integration

### GitHub Actions Example

```yaml
name: Security Testing
on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main]
  schedule:
    # Run daily at 2 AM
    - cron: '0 2 * * *'

jobs:
  security-tests:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: askproai_test
        options: --health-cmd="mysqladmin ping" --health-interval=10s
        
    steps:
      - name: Checkout code
        uses: actions/checkout@v3
        
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.2
          extensions: dom, curl, libxml, mbstring, zip, pcntl, pdo, sqlite, pdo_sqlite, bcmath, soap, intl, gd, exif, iconv
          
      - name: Install Composer dependencies
        run: composer install --no-progress --prefer-dist --optimize-autoloader
        
      - name: Prepare Laravel Application
        run: |
          cp .env.example .env
          php artisan key:generate
          php artisan migrate --force
          
      - name: Run Security Tests
        run: |
          chmod +x security-test-runner.sh
          ./security-test-runner.sh --all --coverage --report
          
      - name: Upload Coverage Reports
        uses: actions/upload-artifact@v3
        if: always()
        with:
          name: security-coverage
          path: security-reports/
          
      - name: Notify on Failure
        if: failure()
        uses: 8398a7/action-slack@v3
        with:
          status: failure
          text: '🚨 Security tests failed! Immediate attention required.'
        env:
          SLACK_WEBHOOK_URL: ${{ secrets.SLACK_WEBHOOK }}
```

### Pre-commit Hook

```bash
#!/bin/bash
# .git/hooks/pre-commit

echo "Running security tests before commit..."
./security-test-runner.sh --fast

if [ $? -ne 0 ]; then
    echo "❌ Security tests failed. Commit aborted."
    echo "Run './security-test-runner.sh --critical --verbose' for details"
    exit 1
fi

echo "✅ Security tests passed. Proceeding with commit."
```

## 🚨 Critical Test Scenarios

### Scenario 1: Cross-Tenant Data Access

```php
public function test_admin_users_cannot_access_other_company_data()
{
    $company1Data = $this->createTestData($this->company1);
    $company2Data = $this->createTestData($this->company2);

    // Login as company 1 admin
    $this->actingAs($this->admin1);
    
    // Try to access company 2 data
    $this->assertCrossTenantAccessPrevented(
        Customer::class,
        $company2Data['customer']->id,
        $this->admin1
    );
}
```

### Scenario 2: API Authentication Bypass

```php
public function test_admin_api_endpoints_require_authentication()
{
    $endpoints = [
        'GET /admin/api/customers',
        'POST /admin/api/customers',
        'PUT /admin/api/customers/1',
        'DELETE /admin/api/customers/1',
    ];

    foreach ($endpoints as $endpoint) {
        [$method, $path] = explode(' ', $endpoint, 2);
        $this->assertApiRequiresAuthentication($method, $path);
    }
}
```

### Scenario 3: Webhook Data Contamination

```php
public function test_retell_webhook_cannot_inject_data_into_wrong_company()
{
    $maliciousPayload = [
        'call' => [
            'company_id' => $this->company2->id, // Trying to inject into wrong company
            'call_id' => 'malicious-call-123',
        ]
    ];

    $response = $this->postJson('/api/retell/webhook', $maliciousPayload);
    
    // Data should go to correct company based on phone routing
    $call = Call::where('retell_call_id', 'malicious-call-123')->first();
    if ($call) {
        $this->assertEquals($this->company1->id, $call->company_id);
    }
}
```

### Scenario 4: SQL Injection Protection

```php
public function test_sql_injection_protection_across_all_endpoints()
{
    $sqlPayloads = [
        "'; DROP TABLE customers; --",
        "1' OR '1'='1",
        "' UNION SELECT password FROM users--",
    ];

    foreach ($sqlPayloads as $payload) {
        $response = $this->getJson("/admin/api/customers?search=" . urlencode($payload));
        $this->assertNotEquals(500, $response->status());
    }
    
    // Verify no malicious changes occurred
    $this->assertDatabaseMissing('customers', ['email' => 'hacked@evil.com']);
}
```

## 📊 Test Coverage Reporting

### Generating Coverage Reports

```bash
# Generate HTML coverage report
./security-test-runner.sh --all --coverage

# View coverage report
open security-reports/coverage/index.html
```

### Coverage Targets

- **Critical Security Functions**: 100% coverage required
- **API Endpoints**: 95% coverage minimum
- **Authentication Logic**: 100% coverage required
- **Database Queries**: 90% coverage minimum

### Coverage Analysis

```bash
# Check coverage for specific test
vendor/bin/phpunit tests/Feature/Security/CrossTenantAuthenticationTest.php --coverage-text

# Generate coverage for CI/CD
vendor/bin/phpunit tests/Feature/Security/ --coverage-clover coverage.xml
```

## 🔧 Troubleshooting

### Common Issues

#### Database Connection Errors
```bash
# Check database connection
php artisan tinker --execute="DB::connection()->getPdo();"

# Reset database for testing
php artisan migrate:fresh --env=testing
```

#### Test Environment Issues
```bash
# Clear all caches
php artisan optimize:clear

# Verify test environment
php artisan env
APP_ENV=testing
```

#### Permission Problems
```bash
# Fix script permissions
chmod +x security-test-runner.sh

# Fix storage permissions
chmod -R 755 storage/
```

#### Memory Issues
```bash
# Increase memory limit for tests
php -d memory_limit=512M vendor/bin/phpunit tests/Feature/Security/
```

### Debug Mode

```bash
# Run single test with maximum verbosity
./security-test-runner.sh --verbose
vendor/bin/phpunit tests/Feature/Security/CrossTenantAuthenticationTest.php --debug
```

### Test Isolation Issues

```bash
# Clear test database between runs
php artisan migrate:fresh --env=testing

# Run tests in isolation
vendor/bin/phpunit tests/Feature/Security/ --process-isolation
```

## 🛡️ Security Best Practices

### Test Development Guidelines

1. **Always Test the Negative Case**: Verify that unauthorized access is blocked
2. **Use Real-World Attack Vectors**: Base tests on actual attack patterns
3. **Test Edge Cases**: Include boundary conditions and unusual inputs
4. **Verify Data Integrity**: Ensure operations don't corrupt data
5. **Test Error Conditions**: Verify secure failure modes

### Test Data Management

```php
// ✅ Good: Create isolated test data
protected function setUp(): void
{
    parent::setUp();
    $this->company1 = Company::factory()->create();
    $this->company2 = Company::factory()->create();
}

// ❌ Bad: Using production data
$this->actingAs(User::first()); // Don't use real users
```

### Security Assertion Patterns

```php
// ✅ Comprehensive security check
$this->assertCrossTenantAccessPrevented(
    Customer::class,
    $otherCompanyCustomerId,
    $unauthorizedUser
);

// ❌ Incomplete check
$this->assertNull(Customer::find($otherCompanyCustomerId));
```

## 🤝 Contributing

### Adding New Security Tests

1. **Identify the Security Risk**: What vulnerability are you testing?
2. **Choose the Right Test Class**: Add to existing or create new test class
3. **Follow Naming Conventions**: Use descriptive test method names
4. **Use BaseSecurityTestCase**: Leverage existing helper methods
5. **Document the Test**: Add clear comments about what's being tested

### Test Class Template

```php
<?php

namespace Tests\Feature\Security;

/**
 * [Test Class Name] Security Test
 * 
 * [Description of what this test class covers]
 * 
 * SEVERITY: [CRITICAL|HIGH|MEDIUM|LOW] - [Brief impact description]
 */
class NewSecurityTest extends BaseSecurityTestCase
{
    public function test_descriptive_security_scenario()
    {
        // Arrange: Set up test conditions
        $this->actingAs($this->admin1);
        
        // Act: Perform the action being tested
        $response = $this->getJson('/some/endpoint');
        
        // Assert: Verify security is enforced
        $this->assertTrue(in_array($response->status(), [401, 403]));
        
        // Log the result
        $this->logSecurityTestResult('test_name', true);
    }
}
```

### Pull Request Guidelines

1. **Run all security tests**: `./security-test-runner.sh --all`
2. **Add tests for new features**: Security tests for any new functionality
3. **Update documentation**: Update this guide if needed
4. **Include security impact assessment**: Describe security implications

### Security Test Review Checklist

- [ ] Test covers a real security vulnerability
- [ ] Test uses appropriate severity level
- [ ] Test follows existing patterns and conventions
- [ ] Test includes both positive and negative cases
- [ ] Test has clear, descriptive naming
- [ ] Test includes proper documentation
- [ ] Test can run in isolation
- [ ] Test cleans up after itself

## 📞 Support

### Getting Help

1. **Documentation**: Start with this guide
2. **Code Comments**: Check inline documentation in test files
3. **Issue Tracker**: Report bugs or request features
4. **Security Team**: Contact for urgent security issues

### Reporting Security Issues

🚨 **For security vulnerabilities**: Do NOT create public issues. Contact the security team directly.

### Contact Information

- **Security Team**: security@askproai.com
- **Development Team**: dev@askproai.com
- **Emergency Contact**: +49-XXX-XXXXXXX

---

## 📄 License

This security testing infrastructure is part of the AskProAI platform and is subject to the same licensing terms.

---

**Last Updated**: 2025-08-02  
**Version**: 1.0  
**Maintainer**: AskProAI Security Team

> 🔒 **Remember**: Security is everyone's responsibility. Run these tests regularly and keep security at the forefront of all development activities.