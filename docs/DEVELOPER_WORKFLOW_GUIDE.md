# Developer Workflow Guide - AskProAI

**Version**: 1.0  
**Last Updated**: 2025-01-10  
**Target Audience**: Developers, QA Engineers

## Table of Contents

1. [Getting Started](#getting-started)
2. [Development Environment Setup](#development-environment-setup)
3. [Git Workflow](#git-workflow)
4. [Local Development](#local-development)
5. [Testing Strategy](#testing-strategy)
6. [Code Quality Standards](#code-quality-standards)
7. [CI/CD Integration](#cicd-integration)
8. [Debugging & Troubleshooting](#debugging--troubleshooting)
9. [Best Practices](#best-practices)
10. [Quick Reference](#quick-reference)

---

## Getting Started

### Prerequisites

- PHP 8.2 or higher
- Composer 2.x
- Node.js 18.x and npm
- MySQL 8.0
- Redis 7.x
- Git
- GitHub CLI (`gh`) - recommended
- Docker (optional but recommended)

### Initial Setup

```bash
# 1. Clone the repository
git clone https://github.com/askproai/api-gateway.git
cd api-gateway

# 2. Install dependencies
composer install
npm install

# 3. Set up environment
cp .env.example .env
php artisan key:generate

# 4. Configure database
mysql -u root -p -e "CREATE DATABASE askproai_dev;"

# 5. Run migrations
php artisan migrate --seed

# 6. Install Git hooks
git config core.hooksPath .githooks

# 7. Start development server
php artisan serve
npm run dev
```

---

## Development Environment Setup

### Using Docker (Recommended)

```bash
# Start all services
docker-compose up -d

# Access application
open http://localhost:8000

# Access Horizon dashboard
open http://localhost:8000/horizon

# Access database
docker-compose exec mysql mysql -u root -p
```

### Manual Setup

```bash
# Install PHP extensions
sudo apt-get install php8.2-{bcmath,curl,gd,intl,mbstring,mysql,redis,xml,zip}

# Configure PHP
sudo nano /etc/php/8.2/cli/php.ini
# memory_limit = 512M
# max_execution_time = 300

# Install Redis
sudo apt-get install redis-server
sudo systemctl enable redis-server

# Install MySQL
sudo apt-get install mysql-server
sudo mysql_secure_installation
```

### VS Code Configuration

`.vscode/settings.json`:
```json
{
  "php.validate.executablePath": "/usr/bin/php",
  "editor.formatOnSave": true,
  "editor.defaultFormatter": "bmewburn.vscode-intelephense-client",
  "[php]": {
    "editor.defaultFormatter": "open-southeners.laravel-pint"
  },
  "laravel-pint.configPath": "pint.json",
  "phpstan.enabled": true,
  "phpstan.configFile": "phpstan.neon"
}
```

Recommended Extensions:
- PHP Intelephense
- Laravel Pint
- Laravel Blade Snippets
- PHPStan
- GitLens
- Laravel Goto
- DotENV

---

## Git Workflow

### Branch Strategy

```mermaid
gitGraph
    commit
    branch develop
    checkout develop
    commit
    branch feature/new-feature
    checkout feature/new-feature
    commit
    commit
    checkout develop
    merge feature/new-feature
    branch staging
    checkout staging
    merge develop
    checkout main
    merge staging
```

**Branch Types**:
- `main` - Production-ready code
- `staging` - Pre-production testing
- `develop` - Development integration
- `feature/*` - New features
- `hotfix/*` - Emergency fixes
- `bugfix/*` - Non-critical bug fixes

### Creating a Feature

```bash
# 1. Start from develop
git checkout develop
git pull origin develop

# 2. Create feature branch
git checkout -b feature/booking-improvements

# 3. Work on feature
# ... make changes ...

# 4. Commit with conventional commits
git add .
git commit -m "feat: add multi-language support to booking flow"

# 5. Push and create PR
git push -u origin feature/booking-improvements
gh pr create --base develop --title "Add multi-language booking support"
```

### Commit Message Convention

Follow [Conventional Commits](https://www.conventionalcommits.org/):

```
<type>(<scope>): <subject>

<body>

<footer>
```

**Types**:
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes (formatting, etc.)
- `refactor`: Code refactoring
- `test`: Adding or updating tests
- `chore`: Maintenance tasks
- `perf`: Performance improvements

**Examples**:
```bash
git commit -m "feat(booking): add SMS confirmation support"
git commit -m "fix(webhook): handle missing phone number gracefully"
git commit -m "docs: update API documentation for v2 endpoints"
git commit -m "test: add integration tests for call processing"
```

### Pull Request Process

1. **Create PR**:
   ```bash
   gh pr create \
     --title "feat: Add SMS notifications" \
     --body "## Description\nAdds SMS notification support...\n\n## Testing\n- [ ] Unit tests pass\n- [ ] Integration tests pass\n- [ ] Manual testing complete" \
     --assignee @me \
     --label enhancement
   ```

2. **PR Checklist**:
   - [ ] Tests pass
   - [ ] Code follows style guidelines
   - [ ] Documentation updated
   - [ ] No security vulnerabilities
   - [ ] Performance impact considered

3. **Review Process**:
   - Automatic checks run
   - Code review required
   - All checks must pass
   - Squash and merge

---

## Local Development

### Running the Application

```bash
# Start Laravel development server
php artisan serve

# Start Vite development server (in another terminal)
npm run dev

# Start queue worker
php artisan queue:work

# Or use Horizon (recommended)
php artisan horizon
```

### Database Management

```bash
# Create new migration
php artisan make:migration add_sms_notifications_to_companies

# Run migrations
php artisan migrate

# Rollback migrations
php artisan migrate:rollback --step=1

# Refresh database (CAUTION: drops all tables)
php artisan migrate:fresh --seed

# Create seeder
php artisan make:seeder DemoDataSeeder

# Run specific seeder
php artisan db:seed --class=DemoDataSeeder
```

### Working with Queues

```bash
# Process jobs synchronously (for debugging)
QUEUE_CONNECTION=sync php artisan serve

# Monitor queue in real-time
php artisan queue:monitor redis:default,redis:notifications

# Retry failed jobs
php artisan queue:retry all

# Clear all jobs
php artisan queue:flush
```

### API Development

```bash
# List all routes
php artisan route:list

# Filter routes
php artisan route:list --path=api/v2

# Test API endpoint
curl -X POST http://localhost:8000/api/appointments \
  -H "Content-Type: application/json" \
  -d '{"date": "2025-01-15", "time": "10:00"}'

# Generate API documentation
php artisan scribe:generate
```

---

## Testing Strategy

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature
php artisan test --testsuite=Integration

# Run specific test file
php artisan test tests/Feature/BookingTest.php

# Run specific test method
php artisan test --filter=test_user_can_create_appointment

# Run tests in parallel
php artisan test --parallel

# Run with coverage
php artisan test --coverage --min=80
```

### Writing Tests

**Unit Test Example**:
```php
<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\BookingService;
use App\Models\Branch;

class BookingServiceTest extends TestCase
{
    public function test_can_check_availability(): void
    {
        // Arrange
        $branch = Branch::factory()->create();
        $service = new BookingService();
        
        // Act
        $available = $service->checkAvailability(
            $branch,
            '2025-01-15',
            '10:00'
        );
        
        // Assert
        $this->assertTrue($available);
    }
}
```

**Feature Test Example**:
```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;

class AppointmentApiTest extends TestCase
{
    public function test_authenticated_user_can_create_appointment(): void
    {
        // Arrange
        $user = User::factory()->create();
        
        // Act & Assert
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/appointments', [
                'branch_id' => 1,
                'service_id' => 1,
                'date' => '2025-01-15',
                'time' => '10:00',
            ])
            ->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'branch_id',
                    'service_id',
                    'scheduled_at',
                    'status'
                ]
            ]);
    }
}
```

### Test Database

```bash
# Use SQLite for tests (faster)
# In phpunit.xml:
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>

# Or use MySQL test database
php artisan migrate --database=mysql_test
```

---

## Code Quality Standards

### Automated Checks

```bash
# Run all quality checks
composer quality

# Individual tools:

# Format code with Pint
composer pint
# or
./vendor/bin/pint

# Static analysis with PHPStan
composer stan
# or
./vendor/bin/phpstan analyse

# Check for security issues
composer security

# Validate composer.json
composer validate

# Check for unused dependencies
composer unused
```

### Pint Configuration

`pint.json`:
```json
{
    "preset": "laravel",
    "rules": {
        "array_syntax": {
            "syntax": "short"
        },
        "binary_operator_spaces": {
            "default": "single_space"
        },
        "blank_line_after_namespace": true,
        "blank_line_after_opening_tag": true,
        "ordered_imports": {
            "sort_algorithm": "alpha"
        }
    }
}
```

### PHPStan Configuration

`phpstan.neon`:
```neon
includes:
    - ./vendor/nunomaduro/larastan/extension.neon

parameters:
    level: 8
    paths:
        - app
        - tests
    excludePaths:
        - app/Console/Kernel.php
    checkMissingIterableValueType: false
    checkGenericClassInNonGenericObjectType: false
```

### Pre-commit Hooks

`.githooks/pre-commit`:
```bash
#!/bin/bash

# Run Pint
./vendor/bin/pint --test
if [ $? -ne 0 ]; then
    echo "❌ Code style issues found. Run 'composer pint' to fix."
    exit 1
fi

# Run PHPStan
./vendor/bin/phpstan analyse
if [ $? -ne 0 ]; then
    echo "❌ Static analysis failed. Fix the issues and try again."
    exit 1
fi

# Run tests
php artisan test --parallel
if [ $? -ne 0 ]; then
    echo "❌ Tests failed. Fix the tests and try again."
    exit 1
fi

echo "✅ All checks passed!"
```

---

## CI/CD Integration

### GitHub Actions Status

Check CI status:
```bash
# View workflow runs
gh run list

# View specific run
gh run view

# Watch run in real-time
gh run watch

# Re-run failed jobs
gh run rerun --failed
```

### Required Checks

Before merging:
1. ✅ Code Quality (Pint, PHPStan)
2. ✅ Security Scan
3. ✅ Unit Tests
4. ✅ Integration Tests
5. ✅ E2E Tests
6. ✅ Documentation Check

### Manual Deployment

```bash
# Deploy to staging
gh workflow run deploy.yml \
  -f environment=staging \
  -f ref=develop \
  -f reason="Testing new booking feature"

# Deploy to production
gh workflow run deploy.yml \
  -f environment=production \
  -f ref=main \
  -f reason="Release v2.1.0"
```

---

## Debugging & Troubleshooting

### Laravel Debugging

```bash
# Enable debug mode locally
APP_DEBUG=true

# Use dd() for quick debugging
dd($variable);

# Use Ray for better debugging (if installed)
ray($data)->green();
ray()->showQueries();

# Log debugging
Log::debug('Booking data', ['booking' => $booking->toArray()]);

# SQL query debugging
DB::enableQueryLog();
// ... run queries ...
dd(DB::getQueryLog());
```

### Telescope (Development)

```bash
# Install Telescope
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate

# Access at http://localhost:8000/telescope
```

### Common Issues

**1. Class not found after adding new file**
```bash
composer dump-autoload
```

**2. Route not found**
```bash
php artisan route:clear
php artisan route:cache
```

**3. Configuration changes not reflected**
```bash
php artisan config:clear
php artisan config:cache
```

**4. View changes not showing**
```bash
php artisan view:clear
php artisan view:cache
```

**5. Queue jobs not processing**
```bash
php artisan queue:restart
php artisan horizon:terminate
php artisan horizon
```

---

## Best Practices

### Code Organization

```
app/
├── Actions/          # Single-purpose action classes
├── Console/          # Artisan commands
├── DTO/              # Data Transfer Objects
├── Enums/            # Enum classes
├── Events/           # Event classes
├── Exceptions/       # Custom exceptions
├── Http/
│   ├── Controllers/  # Keep thin, delegate to services
│   ├── Middleware/   # Request filtering
│   ├── Requests/     # Form requests for validation
│   └── Resources/    # API resources
├── Jobs/             # Queued jobs
├── Listeners/        # Event listeners
├── Mail/             # Mailable classes
├── Models/           # Eloquent models
├── Observers/        # Model observers
├── Policies/         # Authorization policies
├── Providers/        # Service providers
├── Repositories/     # Data access layer
├── Rules/            # Custom validation rules
├── Services/         # Business logic
└── Traits/           # Reusable traits
```

### Service Pattern Example

```php
<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Branch;
use App\Repositories\AppointmentRepository;
use App\Events\AppointmentCreated;
use Illuminate\Support\Facades\DB;

class AppointmentService
{
    public function __construct(
        private AppointmentRepository $repository,
        private NotificationService $notificationService
    ) {}

    public function createAppointment(array $data): Appointment
    {
        return DB::transaction(function () use ($data) {
            // Create appointment
            $appointment = $this->repository->create($data);
            
            // Fire event
            event(new AppointmentCreated($appointment));
            
            // Send notifications
            $this->notificationService->sendAppointmentConfirmation($appointment);
            
            return $appointment;
        });
    }
}
```

### Repository Pattern Example

```php
<?php

namespace App\Repositories;

use App\Models\Appointment;
use Illuminate\Support\Collection;

class AppointmentRepository extends BaseRepository
{
    public function __construct(Appointment $model)
    {
        parent::__construct($model);
    }

    public function findByDateRange(string $start, string $end): Collection
    {
        return $this->model
            ->whereBetween('scheduled_at', [$start, $end])
            ->orderBy('scheduled_at')
            ->get();
    }

    public function findAvailableSlots(int $branchId, string $date): array
    {
        // Complex query logic here
        return [];
    }
}
```

### API Resource Example

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'service' => new ServiceResource($this->whenLoaded('service')),
            'scheduled_at' => $this->scheduled_at->toIso8601String(),
            'duration_minutes' => $this->duration_minutes,
            'status' => $this->status,
            'notes' => $this->when($request->user()->can('view-notes', $this), $this->notes),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
```

---

## Quick Reference

### Essential Commands

```bash
# Development
php artisan serve              # Start dev server
npm run dev                    # Start Vite
php artisan tinker             # Interactive shell

# Database
php artisan migrate            # Run migrations
php artisan migrate:fresh      # Reset database
php artisan db:seed            # Seed database

# Queue
php artisan queue:work         # Process jobs
php artisan horizon            # Start Horizon
php artisan queue:retry all    # Retry failed jobs

# Cache
php artisan cache:clear        # Clear cache
php artisan config:cache       # Cache config
php artisan route:cache        # Cache routes

# Testing
php artisan test               # Run all tests
php artisan test --parallel    # Run in parallel
php artisan test --coverage    # With coverage

# Code Quality
composer pint                  # Format code
composer stan                  # Static analysis
composer test                  # Run tests
composer quality               # All checks

# Git
git status                     # Check status
git add .                      # Stage changes
git commit -m "message"        # Commit
git push                       # Push changes
gh pr create                   # Create PR
```

### Useful Aliases

Add to `~/.bashrc` or `~/.zshrc`:

```bash
# Laravel
alias pa="php artisan"
alias pat="php artisan test"
alias pam="php artisan migrate"
alias pafs="php artisan migrate:fresh --seed"

# Git
alias gs="git status"
alias ga="git add ."
alias gc="git commit -m"
alias gp="git push"
alias gpl="git pull"
alias gco="git checkout"

# Composer
alias cu="composer update"
alias ci="composer install"
alias cda="composer dump-autoload"

# NPM
alias ni="npm install"
alias nr="npm run"
alias nrd="npm run dev"
alias nrb="npm run build"
```

---

**Document Version**: 1.0  
**Last Updated**: 2025-01-10  
**Next Review**: 2025-02-10  
**Maintained By**: Development Team