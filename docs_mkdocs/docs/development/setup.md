# Development Setup Guide

## Overview

This guide will help you set up a local development environment for AskProAI. Follow these steps to get the application running on your local machine.

## Prerequisites

### Required Software
- PHP 8.1 or higher
- Composer 2.x
- MySQL 8.0 or MariaDB 10.5+
- Redis 6.0+
- Node.js 18.x or 20.x LTS
- Git

### Recommended Tools
- Docker Desktop (for containerized development)
- TablePlus or phpMyAdmin (database management)
- Postman or Insomnia (API testing)
- Redis Commander (Redis GUI)
- VS Code or PhpStorm (IDE)

## Local Environment Setup

### 1. Clone the Repository

```bash
# Clone the repository
git clone https://github.com/your-org/askproai.git
cd askproai

# Checkout development branch
git checkout develop
```

### 2. Install PHP Dependencies

```bash
# Install Composer dependencies
composer install

# If you encounter memory errors
COMPOSER_MEMORY_LIMIT=-1 composer install
```

### 3. Environment Configuration

```bash
# Copy example environment file
cp .env.example .env.local

# Generate application key
php artisan key:generate
```

Edit `.env.local` with your local settings:

```env
APP_NAME=AskProAI
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=askproai_dev
DB_USERNAME=root
DB_PASSWORD=

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Queue
QUEUE_CONNECTION=redis

# Mail (use Mailhog for local development)
MAIL_MAILER=smtp
MAIL_HOST=localhost
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null

# External Services (use test/sandbox credentials)
DEFAULT_RETELL_API_KEY=test_key_xxxxx
DEFAULT_CALCOM_API_KEY=test_cal_xxxxx
RETELL_MOCK_ENABLED=true
CALCOM_MOCK_ENABLED=true
```

### 4. Database Setup

```bash
# Create database
mysql -u root -p -e "CREATE DATABASE askproai_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Run migrations
php artisan migrate

# Seed development data
php artisan db:seed --class=DevelopmentSeeder
```

### 5. Install Frontend Dependencies

```bash
# Install NPM packages
npm install

# Build assets for development
npm run dev

# Or run with hot reloading
npm run watch
```

### 6. Start Development Server

```bash
# Start PHP development server
php artisan serve

# In another terminal, start queue worker
php artisan queue:work --tries=1

# In another terminal, start Vite dev server
npm run dev
```

The application will be available at `http://localhost:8000`

## Docker Development Setup

### Using Docker Compose

```yaml
# docker-compose.yml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile.dev
    ports:
      - "8000:8000"
    volumes:
      - .:/var/www
    environment:
      - DB_HOST=mysql
      - REDIS_HOST=redis
    depends_on:
      - mysql
      - redis

  mysql:
    image: mysql:8.0
    ports:
      - "3306:3306"
    environment:
      MYSQL_DATABASE: askproai_dev
      MYSQL_ROOT_PASSWORD: secret
      MYSQL_ALLOW_EMPTY_PASSWORD: 'yes'
    volumes:
      - mysql_data:/var/lib/mysql

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"

  mailhog:
    image: mailhog/mailhog
    ports:
      - "1025:1025"
      - "8025:8025"

  phpmyadmin:
    image: phpmyadmin/phpmyadmin
    ports:
      - "8080:80"
    environment:
      PMA_HOST: mysql
      PMA_USER: root
      PMA_PASSWORD: secret

volumes:
  mysql_data:
```

### Start Docker Environment

```bash
# Build and start containers
docker-compose up -d

# Install dependencies inside container
docker-compose exec app composer install
docker-compose exec app npm install

# Run migrations
docker-compose exec app php artisan migrate --seed

# Build assets
docker-compose exec app npm run dev
```

## Development Tools Setup

### Laravel Telescope

```bash
# Install Telescope (already in composer.json)
php artisan telescope:install
php artisan migrate

# Access at http://localhost:8000/telescope
```

### Laravel Debugbar

```bash
# Install Debugbar
composer require barryvdh/laravel-debugbar --dev

# Publish config
php artisan vendor:publish --provider="Barryvdh\Debugbar\ServiceProvider"
```

### IDE Helper

```bash
# Generate IDE helper files
php artisan ide-helper:generate
php artisan ide-helper:models --write
php artisan ide-helper:meta
```

## Testing Environment

### PHPUnit Setup

```bash
# Copy testing environment file
cp .env.testing.example .env.testing

# Configure test database
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
```

### Run Tests

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# Run with coverage
php artisan test --coverage

# Run specific test
php artisan test tests/Feature/AppointmentTest.php
```

### Browser Testing (Dusk)

```bash
# Install Dusk
php artisan dusk:install

# Run Dusk tests
php artisan dusk

# Run in headless mode
php artisan dusk --headless
```

## Mock Services Setup

### Mock Retell.ai Service

```php
// app/Services/Mock/MockRetellService.php
class MockRetellService implements RetellServiceInterface
{
    public function createCall(array $data): array
    {
        return [
            'call_id' => 'mock_call_' . Str::random(10),
            'status' => 'completed',
            'duration' => rand(60, 300),
            'transcript' => $this->generateMockTranscript(),
        ];
    }
    
    private function generateMockTranscript(): string
    {
        return "Agent: Guten Tag, wie kann ich Ihnen helfen?\n" .
               "Customer: Ich möchte einen Termin buchen.\n" .
               "Agent: Gerne, wann hätten Sie Zeit?";
    }
}
```

### Mock Cal.com Service

```php
// app/Services/Mock/MockCalcomService.php
class MockCalcomService implements CalcomServiceInterface
{
    public function getAvailability($eventTypeId, $date): array
    {
        $slots = [];
        $start = Carbon::parse($date)->setTime(9, 0);
        
        for ($i = 0; $i < 8; $i++) {
            $slots[] = [
                'time' => $start->copy()->addHours($i)->toIso8601String(),
                'available' => rand(0, 1) === 1,
            ];
        }
        
        return ['slots' => $slots];
    }
}
```

## Development Workflow

### Git Workflow

```bash
# Create feature branch
git checkout -b feature/your-feature-name

# Make changes and commit
git add .
git commit -m "feat: add new feature"

# Push to remote
git push origin feature/your-feature-name

# Create pull request via GitHub/GitLab
```

### Code Style

```bash
# Run PHP CS Fixer
./vendor/bin/php-cs-fixer fix

# Run PHPStan
./vendor/bin/phpstan analyse

# Run ESLint for JavaScript
npm run lint

# Fix JavaScript style issues
npm run lint:fix
```

### Pre-commit Hooks

```bash
# Install pre-commit hooks
cp .hooks/pre-commit .git/hooks/pre-commit
chmod +x .git/hooks/pre-commit
```

## Common Development Tasks

### Creating a New Feature

```bash
# Generate model with migration, factory, and controller
php artisan make:model Feature -mfc

# Generate API resource
php artisan make:resource FeatureResource

# Generate form request
php artisan make:request StoreFeatureRequest

# Generate test
php artisan make:test FeatureTest
```

### Database Operations

```bash
# Create new migration
php artisan make:migration add_status_to_features_table

# Rollback migrations
php artisan migrate:rollback

# Refresh database with seeds
php artisan migrate:fresh --seed

# Generate factory
php artisan make:factory FeatureFactory
```

### Queue Development

```bash
# Create new job
php artisan make:job ProcessFeature

# Monitor queue locally
php artisan queue:listen --tries=1

# Clear failed jobs
php artisan queue:flush
```

## Debugging

### Debug Helpers

```php
// Dump and die
dd($variable);

// Dump and continue
dump($variable);

// Log to file
Log::debug('Debug message', ['context' => $data]);

// Use Ray for debugging (if installed)
ray($variable)->green();
ray()->showQueries();
```

### Laravel Telescope

Access Telescope at `http://localhost:8000/telescope` to:
- View all requests
- Inspect database queries
- Monitor queue jobs
- Check mail sending
- Review exceptions

### Xdebug Setup

```ini
; php.ini or xdebug.ini
[xdebug]
xdebug.mode=debug
xdebug.start_with_request=yes
xdebug.client_host=127.0.0.1
xdebug.client_port=9003
```

## Environment Variables

### Local Development Variables

```env
# Development-specific settings
DEBUGBAR_ENABLED=true
TELESCOPE_ENABLED=true
QUERY_DETECTOR_ENABLED=true

# Mock services
RETELL_MOCK_ENABLED=true
CALCOM_MOCK_ENABLED=true
STRIPE_MOCK_ENABLED=true

# Development mail
MAIL_MAILER=log  # Or use mailhog

# Development URLs
FRONTEND_URL=http://localhost:3000
WEBHOOK_URL=http://localhost:8000/webhooks
```

## Troubleshooting

### Common Issues

#### Composer Memory Errors
```bash
# Increase memory limit
COMPOSER_MEMORY_LIMIT=-1 composer install
```

#### NPM Installation Issues
```bash
# Clear cache and reinstall
rm -rf node_modules package-lock.json
npm cache clean --force
npm install
```

#### Migration Errors
```bash
# Reset database
php artisan migrate:fresh

# Check migration status
php artisan migrate:status
```

#### Permission Issues
```bash
# Fix storage permissions
chmod -R 775 storage bootstrap/cache
chown -R $USER:www-data storage bootstrap/cache
```

### Development Tips

1. **Use Tinker for Quick Testing**
   ```bash
   php artisan tinker
   >>> User::factory()->create()
   >>> app(RetellService::class)->testConnection()
   ```

2. **Monitor Logs**
   ```bash
   tail -f storage/logs/laravel.log
   ```

3. **Use Model Factories**
   ```php
   // Create test data quickly
   Appointment::factory()->count(10)->create();
   ```

4. **Test Email Locally**
   - Use Mailhog: `http://localhost:8025`
   - Or set `MAIL_MAILER=log` to log emails

## Next Steps

1. Review the [Architecture Overview](../architecture/overview.md)
2. Understand the [Coding Standards](standards.md)
3. Learn about [Testing Practices](testing.md)
4. Explore the [API Documentation](../api/reference.md)

## Related Documentation

- [Testing Guide](testing.md)
- [Debugging Guide](debugging.md)
- [Contributing Guide](contributing.md)
- [API Development](../api/development.md)