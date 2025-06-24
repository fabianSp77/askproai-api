# Contributing to AskProAI

Thank you for your interest in contributing to AskProAI! This guide will help you get started with contributing to our AI-powered appointment booking platform.

## Code of Conduct

By participating in this project, you agree to abide by our Code of Conduct:

- Be respectful and inclusive
- Welcome newcomers and help them get started
- Focus on constructive criticism
- Respect differing viewpoints and experiences
- Show empathy towards other community members

## Getting Started

1. **Fork the Repository**
   ```bash
   git clone https://github.com/askproai/api-gateway.git
   cd api-gateway
   ```

2. **Set Up Development Environment**
   ```bash
   cp .env.example .env
   composer install
   npm install
   php artisan key:generate
   ```

3. **Run Tests**
   ```bash
   php artisan test
   ```

## Development Workflow

### 1. Create a Feature Branch
```bash
git checkout -b feature/your-feature-name
# or
git checkout -b fix/issue-description
```

### 2. Make Your Changes
- Write clean, documented code
- Follow our coding standards
- Add tests for new features
- Update documentation as needed

### 3. Commit Your Changes
```bash
git add .
git commit -m "feat: add new booking validation"
# or
git commit -m "fix: resolve timezone issue in appointments"
```

#### Commit Message Format
We use conventional commits:
- `feat:` New feature
- `fix:` Bug fix
- `docs:` Documentation changes
- `style:` Code style changes (formatting, etc.)
- `refactor:` Code refactoring
- `test:` Adding or updating tests
- `chore:` Maintenance tasks

### 4. Push and Create Pull Request
```bash
git push origin feature/your-feature-name
```

Then create a pull request on GitHub.

## Coding Standards

### PHP (Laravel)

1. **Follow PSR-12** coding standard
2. **Use Type Hints** wherever possible
3. **Document with PHPDoc**
   ```php
   /**
    * Book an appointment for a customer
    *
    * @param array $data Appointment data
    * @return Appointment
    * @throws BookingException
    */
   public function bookAppointment(array $data): Appointment
   {
       // Implementation
   }
   ```

4. **Use Dependency Injection**
   ```php
   public function __construct(
       private CalcomV2Service $calcomService,
       private NotificationService $notificationService
   ) {}
   ```

5. **Handle Errors Gracefully**
   ```php
   try {
       $result = $this->externalService->call();
   } catch (ApiException $e) {
       Log::error('API call failed', ['error' => $e->getMessage()]);
       throw new BookingException('Service temporarily unavailable');
   }
   ```

### JavaScript

1. **Use ES6+ Features**
2. **Prefer Alpine.js** for interactivity
3. **Document complex functions**

### Database

1. **Always use migrations**
2. **Name tables in plural** (e.g., `appointments`, not `appointment`)
3. **Use UUIDs for primary keys**
4. **Add indexes for frequently queried columns**

## Testing Guidelines

### 1. Write Tests First (TDD)
When adding new features, write tests first:

```php
public function test_appointment_can_be_booked()
{
    $customer = Customer::factory()->create();
    $service = Service::factory()->create();
    
    $response = $this->postJson('/api/appointments', [
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => '2025-04-15 14:30:00',
    ]);
    
    $response->assertStatus(201);
    $this->assertDatabaseHas('appointments', [
        'customer_id' => $customer->id,
    ]);
}
```

### 2. Test Categories
- **Unit Tests**: Test individual methods/classes
- **Integration Tests**: Test service interactions
- **Feature Tests**: Test API endpoints
- **E2E Tests**: Test complete workflows

### 3. Test Coverage
Aim for at least 80% code coverage for new features.

## Documentation

### 1. Code Documentation
- Add PHPDoc blocks to all public methods
- Document complex algorithms
- Include examples for non-obvious usage

### 2. API Documentation
- Update API documentation for new endpoints
- Include request/response examples
- Document error responses

### 3. User Documentation
- Update user guides for UI changes
- Add screenshots where helpful
- Keep language simple and clear

## Pull Request Process

### 1. Before Submitting
- [ ] All tests pass
- [ ] Code follows style guidelines
- [ ] Documentation is updated
- [ ] Commit messages follow convention
- [ ] Branch is up to date with main

### 2. PR Description Template
```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Testing
- [ ] Unit tests pass
- [ ] Integration tests pass
- [ ] Manual testing completed

## Screenshots (if applicable)
Add screenshots for UI changes

## Related Issues
Fixes #123
```

### 3. Review Process
- At least one maintainer review required
- All CI checks must pass
- No merge conflicts
- Documentation updated

## Security

### Reporting Security Issues
**Do not** create public issues for security vulnerabilities. Instead:
1. Email security@askproai.de
2. Include detailed description
3. Provide steps to reproduce
4. Wait for response before disclosure

### Security Best Practices
- Never commit sensitive data
- Use environment variables for secrets
- Validate all user input
- Use prepared statements for queries
- Keep dependencies updated

## Development Tips

### 1. Running Specific Tests
```bash
# Run a specific test file
php artisan test tests/Feature/AppointmentTest.php

# Run a specific test method
php artisan test --filter test_appointment_can_be_booked

# Run with coverage
php artisan test --coverage
```

### 2. Debugging
```php
// Use Laravel's dump and die
dd($variable);

// Use logs for production debugging
Log::info('Booking attempt', [
    'customer' => $customer->id,
    'service' => $service->id,
]);
```

### 3. Database Queries
```php
// Use query builder for complex queries
$appointments = DB::table('appointments')
    ->join('customers', 'appointments.customer_id', '=', 'customers.id')
    ->where('appointments.starts_at', '>=', now())
    ->select('appointments.*', 'customers.name')
    ->get();

// Always use eager loading
$appointments = Appointment::with(['customer', 'service', 'staff'])->get();
```

## Common Tasks

### Adding a New Service Integration
1. Create service class in `app/Services/`
2. Add configuration to `config/services.php`
3. Create interface if multiple implementations
4. Add tests in `tests/Integration/`
5. Document in `docs/integrations/`

### Adding a New API Endpoint
1. Add route in `routes/api.php`
2. Create controller method
3. Add request validation
4. Add feature test
5. Update API documentation

### Adding a Queue Job
1. Create job class: `php artisan make:job ProcessSomething`
2. Define handle method
3. Add to appropriate queue
4. Add retry logic
5. Create test

## Questions?

- Check existing issues and PRs
- Ask in discussions
- Email dev@askproai.de

Thank you for contributing to AskProAI! 🚀