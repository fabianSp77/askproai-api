# 🧪 AskProAI Testing Guide

A comprehensive guide for testing in the AskProAI platform.

## 📋 Table of Contents

- [Quick Start](#quick-start)
- [Test Structure](#test-structure)
- [Running Tests](#running-tests)
- [Writing Tests](#writing-tests)
- [CI/CD Integration](#cicd-integration)
- [Performance Testing](#performance-testing)
- [Troubleshooting](#troubleshooting)

## 🚀 Quick Start

```bash
# Install dependencies
composer install
npm install

# Run all tests
npm run test:all

# Run specific test suites
php artisan test              # PHP tests
npm test                      # JavaScript tests  
npm run test:api             # API tests
npm run test:performance     # Performance tests
```

## 📁 Test Structure

```
tests/
├── Unit/                    # Isolated unit tests
│   ├── Services/           # Business logic tests
│   ├── Models/             # Model tests
│   ├── Helpers/            # Helper function tests
│   └── Database/           # Database operation tests
├── Integration/            # Service integration tests
├── Feature/                # Feature tests
│   └── API/               # API endpoint tests
├── E2E/                    # End-to-end tests
├── Performance/            # Performance tests
│   └── k6/                # K6 load test scripts
├── Examples/               # Example test implementations
└── Helpers/                # Test utilities

resources/js/__tests__/     # JavaScript/React tests
├── components/             # Component tests
├── Pages/                  # Page component tests
├── hooks/                  # Custom hook tests
├── utils/                  # Utility tests
└── mocks/                  # MSW mock handlers
```

## 🧪 Running Tests

### PHP Tests

```bash
# Run all PHP tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# Run specific test file
php artisan test tests/Unit/Services/BookingServiceTest.php

# Run specific test method
php artisan test --filter=test_creates_appointment_successfully

# Run with coverage
php artisan test --coverage
php artisan test --coverage-html=coverage

# Run in parallel
php artisan test --parallel

# Stop on first failure
php artisan test --stop-on-failure
```

### JavaScript Tests

```bash
# Run tests in watch mode
npm test

# Run tests once
npm run test:run

# Run with coverage
npm run test:coverage

# Run specific test file
npm test Button.test.jsx

# Run tests matching pattern
npm test -- --grep="appointment"

# Debug mode
npm test -- --reporter=verbose

# UI mode
npm run test:ui
```

### API Tests

```bash
# Run API tests locally
npm run test:api

# Run against different environments
npm run test:api:dev      # Development
npm run test:api:prod     # Production

# Run specific collection
newman run tests/Postman/Auth.postman_collection.json

# With detailed output
newman run tests/Postman/AskProAI.postman_collection.json --verbose
```

### Performance Tests

```bash
# Load test (normal traffic)
npm run test:performance

# Stress test (find breaking point)
npm run test:performance:stress

# Spike test (sudden traffic increase)
npm run test:performance:spike

# Soak test (extended duration)
npm run test:performance:soak

# With custom parameters
k6 run -e BASE_URL=http://localhost:8000 tests/Performance/k6/load-test.js
```

## ✍️ Writing Tests

### PHP Test Example

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Appointment;

class AppointmentTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_create_appointment()
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);
        
        // Act
        $response = $this->postJson('/api/appointments', [
            'service_id' => 1,
            'appointment_datetime' => '2024-01-20 14:00:00',
        ]);
        
        // Assert
        $response->assertCreated();
        $this->assertDatabaseHas('appointments', [
            'user_id' => $user->id,
        ]);
    }
}
```

### JavaScript Test Example

```javascript
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import AppointmentForm from '../AppointmentForm';

describe('AppointmentForm', () => {
  it('submits appointment data when form is filled', async () => {
    const user = userEvent.setup();
    const onSubmit = vi.fn();
    
    render(<AppointmentForm onSubmit={onSubmit} />);
    
    await user.type(screen.getByLabelText(/name/i), 'John Doe');
    await user.selectOptions(screen.getByLabelText(/service/i), '1');
    await user.click(screen.getByRole('button', { name: /submit/i }));
    
    expect(onSubmit).toHaveBeenCalledWith({
      name: 'John Doe',
      service_id: '1',
    });
  });
});
```

## 🔄 CI/CD Integration

### GitHub Actions

The test suite runs automatically on:
- Every push to `main` or `develop`
- Every pull request
- Scheduled nightly builds

```yaml
# .github/workflows/tests.yml
name: Run Tests

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Run PHP Tests
        run: |
          composer install
          php artisan test --parallel
      - name: Run JS Tests
        run: |
          npm ci
          npm run test:coverage
```

### Pre-commit Hooks

Install pre-commit hooks to run tests before commits:

```bash
# Install hooks
cp .githooks/pre-commit .git/hooks/pre-commit
chmod +x .git/hooks/pre-commit
```

## 📊 Coverage Reports

### Viewing Coverage

```bash
# Generate HTML coverage report
php artisan test --coverage-html=coverage
npm run test:coverage

# Open in browser
open coverage/index.html
open coverage-js/index.html

# Merge PHP and JS coverage
npm run coverage:merge
```

### Coverage Thresholds

Minimum coverage requirements:
- Lines: 80%
- Functions: 80%
- Branches: 80%
- Statements: 80%

## 🔧 Test Utilities

### Database Helpers

```php
use Tests\Helpers\DatabaseTestHelper;

// Seed test data
DatabaseTestHelper::seedTestCompany();
DatabaseTestHelper::seedAppointments(10);

// Clean up
DatabaseTestHelper::cleanDatabase();
```

### API Test Helpers

```php
use Tests\Helpers\ApiTestHelper;

// Authenticated request
$response = ApiTestHelper::authenticatedRequest('GET', '/api/user');

// Assert API response structure
ApiTestHelper::assertApiResponse($response, [
    'data' => ['id', 'name', 'email'],
    'meta' => ['current_page', 'total'],
]);
```

### Mock Helpers

```php
use Tests\Helpers\MockHelper;

// Mock external service
MockHelper::mockRetellApi([
    'call_id' => '123',
    'status' => 'completed',
]);

// Mock time
MockHelper::freezeTime('2024-01-15 10:00:00');
```

## 🐛 Troubleshooting

### Common Issues

#### Tests failing with "Database not found"
```bash
# Create test database
php artisan migrate --env=testing
```

#### JavaScript tests not finding modules
```bash
# Clear cache and reinstall
rm -rf node_modules package-lock.json
npm install
```

#### Slow test execution
```bash
# Run in parallel
php artisan test --parallel

# Use in-memory database
# In phpunit.xml:
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

#### Flaky tests
```php
// Add retry for flaky tests
$this->retry(3, function () {
    // Test that sometimes fails
});

// Or use sleep for timing issues
sleep(1);
$this->assertTrue($condition);
```

### Debug Mode

```bash
# PHP debug output
php artisan test --debug

# JavaScript debug
npm test -- --no-coverage --verbose

# Step debugging
# Add breakpoint in code: debugger;
# Run: npm test -- --inspect-brk
```

## 📚 Best Practices

1. **Write tests first** (TDD)
2. **One assertion per test** when possible
3. **Use descriptive test names**
4. **Follow AAA pattern** (Arrange, Act, Assert)
5. **Keep tests fast** (< 100ms per test)
6. **Mock external dependencies**
7. **Use factories for test data**
8. **Test edge cases and errors**
9. **Maintain test code quality**
10. **Run tests before pushing**

## 🔗 Additional Resources

- [Testing Playbook](./TESTING_PLAYBOOK.md) - Detailed testing guide
- [Real World Examples](./Examples/RealWorldTestExamples.php) - Common scenarios
- [Performance Testing Guide](./Performance/k6/README.md) - K6 documentation
- [Laravel Testing Docs](https://laravel.com/docs/testing)
- [Vitest Documentation](https://vitest.dev/)
- [Testing Library](https://testing-library.com/)