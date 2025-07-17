# AskProAI Test Suite

## 🚀 Quick Start

```bash
# Install dependencies
npm install
composer install

# Run all tests
npm run test:all

# Run PHP tests only
composer test

# Run JavaScript tests only
npm test

# Run with coverage
npm run test:coverage
composer test:coverage
```

## 📁 Test Structure

```
tests/
├── Unit/                    # Isolated component tests
│   ├── components/         # React components
│   ├── hooks/             # Custom hooks
│   ├── utils/             # Utility functions
│   └── services/          # Service classes
├── Integration/            # Component integration tests
│   ├── api/               # API endpoints
│   └── services/          # Service integrations
├── E2E/                    # End-to-end workflows
├── Performance/            # Load and performance tests
├── Feature/               # Laravel feature tests
├── fixtures/              # Test data and fixtures
├── helpers/               # Test utilities
└── mocks/                 # API mocks (MSW)
```

## 🧪 Running Tests

### PHP Tests (PHPUnit)

```bash
# Run all PHP tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# Run specific test
php artisan test --filter=EmailServiceTest

# Run with coverage
php artisan test --coverage --min=80

# Run in parallel
php artisan test --parallel
```

### JavaScript Tests (Vitest)

```bash
# Run all JavaScript tests
npm test

# Run in watch mode
npm run test:watch

# Run with UI
npm run test:ui

# Run with coverage
npm run test:coverage

# Run specific file
npm test AppointmentList.test.tsx
```

### API Tests (Newman)

```bash
# Run Postman collection
npm run test:api

# Run with specific environment
newman run tests/api/collection.json -e tests/api/env.local.json
```

## 📊 Coverage Reports

- **PHP Coverage**: `coverage/php/index.html`
- **JS Coverage**: `coverage/vitest/index.html`
- **Combined Report**: `coverage/index.html`

## 🎯 Coverage Goals

- Overall: 80%
- Critical paths: 95%
- New code: 90%

## 🔧 Configuration Files

- `phpunit.xml` - PHPUnit configuration
- `vitest.config.ts` - Vitest configuration
- `tests/setup.ts` - JavaScript test setup
- `tests/TestCase.php` - PHP test base class

## 📝 Writing Tests

See [TESTING_BEST_PRACTICES.md](./TESTING_BEST_PRACTICES.md) for detailed guidelines.

### Quick Examples

**PHP Unit Test:**
```php
/** @test */
public function it_sends_welcome_email()
{
    Mail::fake();
    $user = User::factory()->create();
    
    $this->service->sendWelcomeEmail($user);
    
    Mail::assertSent(WelcomeEmail::class);
}
```

**React Component Test:**
```tsx
it('renders appointment details', () => {
  const appointment = createMockAppointment()
  
  render(<AppointmentCard appointment={appointment} />)
  
  expect(screen.getByText(appointment.customer_name)).toBeInTheDocument()
})
```

## 🐛 Debugging

### PHP Tests
- Add `dd($variable)` in tests
- Use `--stop-on-failure` flag
- Check `storage/logs/testing.log`

### JavaScript Tests
- Use `console.log()` in tests
- Run with `npm run test:ui` for browser debugging
- Use `test.only()` to run single test

## 🚦 CI/CD Integration

Tests run automatically on:
- Every push to `main` or `develop`
- Every pull request
- Nightly scheduled runs

See `.github/workflows/tests.yml` for configuration.

## 📚 Resources

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Vitest Documentation](https://vitest.dev/)
- [Testing Library](https://testing-library.com/)
- [Laravel Testing](https://laravel.com/docs/testing)