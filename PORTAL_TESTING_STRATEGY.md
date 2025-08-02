# Portal Testing Strategy & Implementation Guide

## Overview

This document outlines the comprehensive testing strategy for critical portal functionality in the AskProAI system, covering both Admin Portal and Business Portal authentication, authorization, multi-tenancy, and security.

## Test Coverage Summary

### ✅ Implemented Test Suites

1. **Authentication Tests** 
   - Admin Portal Login/Logout flows
   - Business Portal authentication with multiple user roles
   - Session management and isolation
   - Password validation and security

2. **Multi-Tenant Security Tests**
   - Data isolation between companies
   - Cross-tenant access prevention
   - Tenant scope enforcement
   - Super admin override capabilities

3. **CRUD Operation Tests**
   - Companies, Users, Customers, Appointments management
   - Branch and Service management
   - Call data access and permissions
   - Bulk operations and data integrity

4. **API Endpoint Security Tests**
   - Authentication and authorization on all endpoints
   - Input validation and sanitization
   - SQL injection and XSS protection
   - Rate limiting and CSRF protection

5. **Session Management Tests**
   - Portal-specific session configurations
   - Session isolation between portals
   - Cookie security and timeout handling
   - Concurrent session management

6. **Two-Factor Authentication Tests**
   - 2FA setup and configuration
   - Code generation and validation
   - Backup codes and recovery
   - Role-based 2FA requirements

7. **Middleware Tests**
   - Authentication middleware for both portals
   - Company context middleware
   - Permission-based access control
   - Security headers and protection

8. **Integration Workflow Tests**
   - Complete user workflows
   - Cross-portal session handling
   - Data consistency verification
   - Performance critical paths

## Test File Structure

```
tests/
├── Feature/
│   ├── Auth/
│   │   ├── AdminPortalAuthTest.php
│   │   ├── BusinessPortalAuthEnhancedTest.php
│   │   └── TwoFactorAuthenticationTest.php
│   ├── Security/
│   │   ├── MultiTenantSecurityTest.php
│   │   └── ApiEndpointSecurityTest.php
│   ├── Portal/
│   │   └── CriticalEntityCrudTest.php
│   ├── Session/
│   │   └── PortalSessionManagementTest.php
│   ├── Middleware/
│   │   └── PortalMiddlewareTest.php
│   └── Integration/
│       └── PortalWorkflowIntegrationTest.php
```

## Running the Tests

### Prerequisites

1. **Environment Setup**
   ```bash
   # Ensure testing environment is configured
   cp .env.example .env.testing
   
   # Set test database to SQLite for speed
   DB_CONNECTION=sqlite
   DB_DATABASE=:memory:
   ```

2. **Dependencies**
   ```bash
   composer install
   php artisan key:generate --env=testing
   ```

### Test Execution Commands

#### Run All Portal Tests
```bash
# Run all new portal tests
php artisan test tests/Feature/Auth/ tests/Feature/Security/ tests/Feature/Portal/ tests/Feature/Session/ tests/Feature/Middleware/ tests/Feature/Integration/ --no-coverage
```

#### Run Specific Test Categories
```bash
# Authentication tests only
php artisan test tests/Feature/Auth/ --no-coverage

# Security tests only  
php artisan test tests/Feature/Security/ --no-coverage

# Integration tests only
php artisan test tests/Feature/Integration/ --no-coverage
```

#### Run Individual Test Files
```bash
# Admin authentication
php artisan test tests/Feature/Auth/AdminPortalAuthTest.php --no-coverage

# Business portal authentication
php artisan test tests/Feature/Auth/BusinessPortalAuthEnhancedTest.php --no-coverage

# Multi-tenant security
php artisan test tests/Feature/Security/MultiTenantSecurityTest.php --no-coverage
```

#### Run with Coverage (Optional)
```bash
# Generate coverage report
php artisan test tests/Feature/Auth/ --coverage-html coverage/portal-auth
```

## Test Categories Explained

### 1. Authentication Tests

**Purpose**: Verify that login/logout mechanisms work correctly for both portals
**Key Scenarios**:
- Valid/invalid credential handling
- Session persistence and regeneration
- Inactive user blocking
- Password security requirements
- Remember me functionality

**Files**: 
- `AdminPortalAuthTest.php`
- `BusinessPortalAuthEnhancedTest.php`

### 2. Multi-Tenant Security Tests

**Purpose**: Ensure complete data isolation between companies
**Key Scenarios**:
- Users can only access their own company's data
- Cross-tenant queries return empty results
- Super admin can access all tenants
- Bulk operations respect tenant boundaries

**Files**: 
- `MultiTenantSecurityTest.php`

### 3. CRUD Operation Tests

**Purpose**: Verify create, read, update, delete operations work correctly with proper authorization
**Key Scenarios**:
- Entity creation with proper company assignment
- Update operations with validation
- Delete operations with cascade handling
- Permission-based access control

**Files**: 
- `CriticalEntityCrudTest.php`

### 4. API Endpoint Security Tests

**Purpose**: Ensure all API endpoints are properly secured
**Key Scenarios**:
- Authentication requirements
- Input validation and sanitization
- Protection against common attacks (SQL injection, XSS)
- Rate limiting and CSRF protection

**Files**: 
- `ApiEndpointSecurityTest.php`

### 5. Session Management Tests

**Purpose**: Verify session handling works correctly across portals
**Key Scenarios**:
- Session isolation between admin and business portals
- Proper session timeout and cleanup
- Security cookie configuration
- Session fixation prevention

**Files**: 
- `PortalSessionManagementTest.php`

### 6. Two-Factor Authentication Tests

**Purpose**: Test 2FA implementation (when available)
**Key Scenarios**:
- 2FA setup and configuration
- Code validation and backup codes
- Role-based 2FA requirements
- Recovery mechanisms

**Files**: 
- `TwoFactorAuthenticationTest.php`

### 7. Middleware Tests

**Purpose**: Verify middleware functions correctly
**Key Scenarios**:
- Authentication middleware blocking
- Company context setting
- Permission enforcement
- Security header application

**Files**: 
- `PortalMiddlewareTest.php`

### 8. Integration Workflow Tests

**Purpose**: Test complete user workflows end-to-end
**Key Scenarios**:
- Complete admin portal workflow
- Complete business portal workflow
- Cross-portal session isolation
- Data consistency across portals

**Files**: 
- `PortalWorkflowIntegrationTest.php`

## Expected Test Results

### Passing Tests Indicate:
- ✅ Authentication mechanisms are secure
- ✅ Multi-tenancy is properly enforced
- ✅ Sessions are isolated and secure
- ✅ API endpoints are protected
- ✅ User permissions are respected
- ✅ Data integrity is maintained

### Failing Tests May Indicate:
- ❌ Security vulnerabilities
- ❌ Authentication bypass possibilities
- ❌ Data leakage between tenants
- ❌ Session management issues
- ❌ Permission escalation vulnerabilities

## Troubleshooting Common Issues

### Database Migration Errors
```bash
# If migrations fail in testing
php artisan migrate:fresh --env=testing
php artisan db:seed --env=testing
```

### Class Conflict Errors
```bash
# Clear autoload cache
composer dump-autoload
```

### Route Not Found Errors
```bash
# Clear route cache
php artisan route:clear
```

### Factory/Model Issues
```bash
# Check if all required factories exist
ls database/factories/

# Ensure models have proper relationships
```

## Continuous Integration

### GitHub Actions Configuration
```yaml
name: Portal Tests
on: [push, pull_request]
jobs:
  portal-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      - name: Install dependencies
        run: composer install
      - name: Run portal tests
        run: php artisan test tests/Feature/Auth/ tests/Feature/Security/ tests/Feature/Portal/ --stop-on-failure
```

## Security Testing Best Practices

### What These Tests Verify:
1. **Authentication Security**
   - Password requirements and hashing
   - Session management and regeneration
   - Brute force protection (rate limiting)

2. **Authorization Security**
   - Role-based access control
   - Resource-level permissions
   - Administrative privilege separation

3. **Data Security**
   - Multi-tenant data isolation
   - SQL injection prevention
   - Cross-site scripting (XSS) protection

4. **Session Security**
   - Session fixation prevention
   - Secure cookie configuration
   - Proper session timeout

## Maintenance Guidelines

### Regular Test Maintenance:
1. **Update tests when adding new features**
2. **Review and update security tests quarterly**
3. **Add performance benchmarks to critical paths**
4. **Keep test data factories updated with model changes**

### Test Quality Indicators:
- Tests should run in under 2 minutes total
- No skipped tests in CI environment
- Coverage should be >80% for critical security functions
- All tests should be deterministic (no flaky tests)

## Extending the Test Suite

### Adding New Test Categories:
1. Create new test files following the existing pattern
2. Use the `RefreshDatabase` trait for database tests
3. Follow the `Arrange-Act-Assert` pattern
4. Include both positive and negative test cases
5. Add proper test documentation

### Test Naming Convention:
```php
public function test_specific_behavior_under_specific_conditions()
{
    // Arrange: Set up test data
    // Act: Perform the action being tested
    // Assert: Verify the expected outcome
}
```

## Conclusion

This comprehensive test suite provides confidence that the portal functionality is secure, reliable, and maintains proper multi-tenancy. Regular execution of these tests helps prevent security vulnerabilities and ensures consistent behavior across portal updates.

For questions or issues with the test suite, refer to the individual test files for specific implementation details.