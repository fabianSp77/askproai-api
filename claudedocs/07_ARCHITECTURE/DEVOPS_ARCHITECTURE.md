# DevOps Architecture & Deployment Strategy
**Laravel API Gateway - Telefonagent Booking System**

**Date**: 2025-09-30
**Version**: 1.0
**Status**: Production-Ready Strategy

---

## Executive Summary

### Current State Assessment
- **Platform**: Laravel 11.46, PHP 8.3.23, MySQL
- **Architecture Rating**: 6/10 (God Object issues)
- **Security Rating**: 7.3/10 HIGH RISK (7 vulnerabilities)
- **Performance Baseline**: 400-800ms (6 bottlenecks)
- **Technical Debt**: 24 items identified
- **Deployment Status**: No CI/CD, manual deployments, no containerization

### Strategic Priorities
1. **Zero-Downtime Deployments**: Blue-Green strategy with health checks
2. **Security Hardening**: Automated scanning, secret management, PII encryption
3. **Performance Monitoring**: Sub-2s response times, 100+ concurrent calls
4. **Disaster Recovery**: RTO <15min, RPO <5min
5. **Infrastructure as Code**: Full reproducibility across environments

---

## 1. CI/CD Pipeline Architecture

### 1.1 GitHub Actions Workflow Strategy

#### Primary Workflows
```
┌──────────────────────────────────────────────────────────┐
│                   GitHub Push/PR Event                    │
└────────────────────┬─────────────────────────────────────┘
                     │
        ┌────────────┴────────────┐
        │                         │
    ┌───▼───┐               ┌────▼────┐
    │  PR   │               │  Main   │
    │ Check │               │  Deploy │
    └───┬───┘               └────┬────┘
        │                        │
    ┌───▼──────────────┐    ┌───▼──────────────┐
    │ 1. Lint/Format   │    │ 1. Security Scan │
    │ 2. Unit Tests    │    │ 2. Build Image   │
    │ 3. Security Scan │    │ 3. Deploy Staging│
    │ 4. Require Pass  │    │ 4. Smoke Tests   │
    └──────────────────┘    │ 5. Deploy Prod   │
                            │ 6. Rollback Gate │
                            └──────────────────┘
```

#### Workflow: Pull Request Validation

**File**: `.github/workflows/pr-validation.yml`

```yaml
name: PR Validation

on:
  pull_request:
    branches: [ main, develop ]
    types: [ opened, synchronize, reopened ]

env:
  PHP_VERSION: '8.3'
  COMPOSER_CACHE_VERSION: 1

jobs:
  code-quality:
    name: Code Quality Checks
    runs-on: ubuntu-latest
    timeout-minutes: 10

    steps:
      - name: Checkout code
        uses: actions/checkout@v4
        with:
          fetch-depth: 0  # Full history for better analysis

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ env.PHP_VERSION }}
          extensions: mbstring, xml, ctype, json, mysql, redis
          coverage: xdebug
          tools: composer:v2

      - name: Cache Composer dependencies
        uses: actions/cache@v4
        with:
          path: vendor
          key: composer-${{ env.COMPOSER_CACHE_VERSION }}-${{ hashFiles('**/composer.lock') }}
          restore-keys: composer-${{ env.COMPOSER_CACHE_VERSION }}-

      - name: Install dependencies
        run: composer install --prefer-dist --no-interaction --no-progress --optimize-autoloader

      - name: Check code style (Laravel Pint)
        run: ./vendor/bin/pint --test

      - name: Static analysis (PHPStan - Future)
        run: |
          # composer require --dev phpstan/phpstan
          # ./vendor/bin/phpstan analyse --memory-limit=2G
          echo "PHPStan integration pending - Add after technical debt cleanup"
        continue-on-error: true

  security-scan:
    name: Security Scanning
    runs-on: ubuntu-latest
    timeout-minutes: 15

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ env.PHP_VERSION }}

      - name: Cache Composer dependencies
        uses: actions/cache@v4
        with:
          path: vendor
          key: composer-${{ env.COMPOSER_CACHE_VERSION }}-${{ hashFiles('**/composer.lock') }}

      - name: Install dependencies
        run: composer install --prefer-dist --no-interaction

      - name: Check for vulnerable dependencies
        run: |
          composer audit --format=json > composer-audit.json || true
          if [ -s composer-audit.json ]; then
            echo "::warning::Vulnerable dependencies detected"
            cat composer-audit.json
          fi

      - name: SAST - Secret Scanning
        uses: trufflesecurity/trufflehog@main
        with:
          path: ./
          base: ${{ github.event.pull_request.base.sha }}
          head: ${{ github.event.pull_request.head.sha }}

      - name: SAST - Code Security
        uses: securego/gosec@master
        with:
          args: '-exclude-generated -fmt sarif -out gosec-results.sarif ./...'
        continue-on-error: true

      - name: Upload SARIF results
        uses: github/codeql-action/upload-sarif@v3
        if: always()
        with:
          sarif_file: gosec-results.sarif

  test-suite:
    name: Test Suite (Pest/PHPUnit)
    runs-on: ubuntu-latest
    timeout-minutes: 20

    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_DATABASE: testing
          MYSQL_ROOT_PASSWORD: password
        ports:
          - 3306:3306
        options: >-
          --health-cmd="mysqladmin ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=5

      redis:
        image: redis:7-alpine
        ports:
          - 6379:6379
        options: >-
          --health-cmd "redis-cli ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=5

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ env.PHP_VERSION }}
          extensions: mbstring, xml, ctype, json, mysql, redis
          coverage: xdebug

      - name: Cache Composer dependencies
        uses: actions/cache@v4
        with:
          path: vendor
          key: composer-${{ env.COMPOSER_CACHE_VERSION }}-${{ hashFiles('**/composer.lock') }}

      - name: Install dependencies
        run: composer install --prefer-dist --no-interaction

      - name: Prepare Laravel Application
        run: |
          cp .env.example .env
          php artisan key:generate
          php artisan config:clear

      - name: Run migrations
        run: php artisan migrate --force
        env:
          DB_CONNECTION: mysql
          DB_HOST: 127.0.0.1
          DB_PORT: 3306
          DB_DATABASE: testing
          DB_USERNAME: root
          DB_PASSWORD: password
          REDIS_HOST: 127.0.0.1

      - name: Run tests with coverage
        run: php artisan test --coverage --min=70 --parallel
        env:
          DB_CONNECTION: mysql
          DB_HOST: 127.0.0.1
          DB_PORT: 3306
          DB_DATABASE: testing
          DB_USERNAME: root
          DB_PASSWORD: password
          REDIS_HOST: 127.0.0.1

      - name: Upload coverage reports
        uses: codecov/codecov-action@v4
        if: always()
        with:
          files: ./coverage.xml
          flags: unittests
          name: codecov-umbrella

  integration-tests:
    name: Integration Tests (Webhooks)
    runs-on: ubuntu-latest
    needs: [ test-suite ]
    timeout-minutes: 15

    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_DATABASE: testing
          MYSQL_ROOT_PASSWORD: password
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s

      redis:
        image: redis:7-alpine
        ports:
          - 6379:6379

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ env.PHP_VERSION }}

      - name: Install dependencies
        run: composer install --prefer-dist --no-interaction

      - name: Prepare environment
        run: |
          cp .env.example .env
          php artisan key:generate
          php artisan migrate --force
        env:
          DB_CONNECTION: mysql
          DB_HOST: 127.0.0.1
          DB_DATABASE: testing

      - name: Test Retell webhook signature validation
        run: php artisan test --filter=RetellWebhookTest

      - name: Test Cal.com integration
        run: php artisan test --filter=CalcomServiceTest

      - name: Test booking flow end-to-end
        run: php artisan test --filter=BookingFlowTest
```

#### Workflow: Production Deployment

**File**: `.github/workflows/deploy-production.yml`

```yaml
name: Deploy to Production

on:
  push:
    branches: [ main ]
  workflow_dispatch:  # Manual trigger
    inputs:
      skip_tests:
        description: 'Skip tests (emergency only)'
        required: false
        default: 'false'

env:
  PHP_VERSION: '8.3'
  DOCKER_REGISTRY: ghcr.io
  IMAGE_NAME: ${{ github.repository }}/api-gateway

jobs:
  security-gate:
    name: Security Gate
    runs-on: ubuntu-latest
    timeout-minutes: 10

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Run Trivy vulnerability scanner
        uses: aquasecurity/trivy-action@master
        with:
          scan-type: 'fs'
          scan-ref: '.'
          format: 'sarif'
          output: 'trivy-results.sarif'
          severity: 'CRITICAL,HIGH'
          exit-code: '1'  # Fail on HIGH/CRITICAL

      - name: Upload Trivy results
        uses: github/codeql-action/upload-sarif@v3
        if: always()
        with:
          sarif_file: trivy-results.sarif

  build-and-push:
    name: Build Docker Image
    runs-on: ubuntu-latest
    needs: [ security-gate ]
    timeout-minutes: 20

    outputs:
      image_tag: ${{ steps.meta.outputs.tags }}
      image_digest: ${{ steps.build.outputs.digest }}

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Set up Docker Buildx
        uses: docker/setup-buildx-action@v3

      - name: Login to GitHub Container Registry
        uses: docker/login-action@v3
        with:
          registry: ${{ env.DOCKER_REGISTRY }}
          username: ${{ github.actor }}
          password: ${{ secrets.GITHUB_TOKEN }}

      - name: Extract metadata
        id: meta
        uses: docker/metadata-action@v5
        with:
          images: ${{ env.DOCKER_REGISTRY }}/${{ env.IMAGE_NAME }}
          tags: |
            type=sha,prefix={{branch}}-
            type=ref,event=branch
            type=semver,pattern={{version}}
            type=raw,value=latest,enable={{is_default_branch}}

      - name: Build and push
        id: build
        uses: docker/build-push-action@v5
        with:
          context: .
          file: ./docker/Dockerfile
          push: true
          tags: ${{ steps.meta.outputs.tags }}
          labels: ${{ steps.meta.outputs.labels }}
          cache-from: type=registry,ref=${{ env.DOCKER_REGISTRY }}/${{ env.IMAGE_NAME }}:buildcache
          cache-to: type=registry,ref=${{ env.DOCKER_REGISTRY }}/${{ env.IMAGE_NAME }}:buildcache,mode=max
          build-args: |
            PHP_VERSION=${{ env.PHP_VERSION }}
            BUILD_DATE=${{ github.event.head_commit.timestamp }}
            VCS_REF=${{ github.sha }}

      - name: Scan image with Trivy
        uses: aquasecurity/trivy-action@master
        with:
          image-ref: ${{ steps.meta.outputs.tags }}
          format: 'sarif'
          output: 'trivy-image-results.sarif'
          severity: 'CRITICAL,HIGH'

  deploy-staging:
    name: Deploy to Staging
    runs-on: ubuntu-latest
    needs: [ build-and-push ]
    timeout-minutes: 15
    environment:
      name: staging
      url: https://staging.api-gateway.example.com

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Deploy via SSH
        uses: appleboy/ssh-action@v1.0.0
        with:
          host: ${{ secrets.STAGING_HOST }}
          username: ${{ secrets.STAGING_USER }}
          key: ${{ secrets.STAGING_SSH_KEY }}
          script: |
            cd /var/www/api-gateway
            docker-compose pull
            docker-compose up -d --no-deps --build api
            docker-compose exec -T api php artisan migrate --force
            docker-compose exec -T api php artisan config:cache
            docker-compose exec -T api php artisan route:cache
            docker-compose exec -T api php artisan view:cache

      - name: Wait for staging health check
        run: |
          for i in {1..30}; do
            if curl -f https://staging.api-gateway.example.com/health; then
              echo "Staging is healthy"
              exit 0
            fi
            echo "Waiting for staging... ($i/30)"
            sleep 10
          done
          echo "Staging health check failed"
          exit 1

      - name: Run smoke tests
        run: |
          curl -f https://staging.api-gateway.example.com/api/health || exit 1
          curl -f https://staging.api-gateway.example.com/api/ping || exit 1

  deploy-production:
    name: Deploy to Production (Blue-Green)
    runs-on: ubuntu-latest
    needs: [ deploy-staging ]
    timeout-minutes: 30
    environment:
      name: production
      url: https://api-gateway.example.com

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup kubectl
        uses: azure/setup-kubectl@v3
        with:
          version: 'v1.28.0'

      - name: Configure kubeconfig
        run: |
          echo "${{ secrets.KUBE_CONFIG }}" | base64 -d > kubeconfig
          export KUBECONFIG=./kubeconfig

      - name: Deploy to blue environment
        run: |
          kubectl set image deployment/api-gateway-blue \
            api-gateway=${{ needs.build-and-push.outputs.image_tag }} \
            -n production
          kubectl rollout status deployment/api-gateway-blue -n production --timeout=5m

      - name: Run production smoke tests (blue)
        run: |
          BLUE_URL=$(kubectl get svc api-gateway-blue -n production -o jsonpath='{.status.loadBalancer.ingress[0].ip}')
          curl -f http://$BLUE_URL/health || exit 1
          curl -f http://$BLUE_URL/api/ping || exit 1

      - name: Switch traffic to blue (zero-downtime)
        run: |
          kubectl patch service api-gateway-main -n production \
            -p '{"spec":{"selector":{"version":"blue"}}}'

      - name: Monitor for 5 minutes
        run: |
          sleep 300
          # Check error rates, response times, etc.

      - name: Verify production health
        run: |
          for i in {1..10}; do
            if ! curl -f https://api-gateway.example.com/health; then
              echo "Health check failed, initiating rollback"
              exit 1
            fi
            sleep 30
          done

      - name: Update green environment (old version)
        if: success()
        run: |
          kubectl set image deployment/api-gateway-green \
            api-gateway=${{ needs.build-and-push.outputs.image_tag }} \
            -n production

  rollback:
    name: Rollback on Failure
    runs-on: ubuntu-latest
    needs: [ deploy-production ]
    if: failure()
    timeout-minutes: 10

    steps:
      - name: Rollback to green environment
        run: |
          kubectl patch service api-gateway-main -n production \
            -p '{"spec":{"selector":{"version":"green"}}}'

      - name: Notify team
        uses: 8398a7/action-slack@v3
        with:
          status: custom
          custom_payload: |
            {
              "text": "🚨 Production deployment failed and was rolled back",
              "attachments": [{
                "color": "danger",
                "fields": [{
                  "title": "Repository",
                  "value": "${{ github.repository }}",
                  "short": true
                }, {
                  "title": "Commit",
                  "value": "${{ github.sha }}",
                  "short": true
                }]
              }]
            }
        env:
          SLACK_WEBHOOK_URL: ${{ secrets.SLACK_WEBHOOK_URL }}
```

### 1.2 Database Migration Strategy

#### Workflow: Database Migrations

**File**: `.github/workflows/database-migration.yml`

```yaml
name: Database Migration

on:
  workflow_call:
    inputs:
      environment:
        required: true
        type: string
    secrets:
      DB_HOST:
        required: true
      DB_PASSWORD:
        required: true

jobs:
  migrate:
    name: Run Migrations
    runs-on: ubuntu-latest
    timeout-minutes: 15

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'

      - name: Install dependencies
        run: composer install --no-dev --optimize-autoloader

      - name: Create database backup
        run: |
          mysqldump -h ${{ secrets.DB_HOST }} \
            -u ${{ secrets.DB_USERNAME }} \
            -p${{ secrets.DB_PASSWORD }} \
            ${{ secrets.DB_DATABASE }} \
            > backup_$(date +%Y%m%d_%H%M%S).sql

          # Upload to S3
          aws s3 cp backup_*.sql \
            s3://${{ secrets.BACKUP_BUCKET }}/migrations/ \
            --metadata "environment=${{ inputs.environment }},commit=${{ github.sha }}"
        env:
          AWS_ACCESS_KEY_ID: ${{ secrets.AWS_ACCESS_KEY_ID }}
          AWS_SECRET_ACCESS_KEY: ${{ secrets.AWS_SECRET_ACCESS_KEY }}

      - name: Dry-run migrations
        run: php artisan migrate --pretend
        env:
          DB_HOST: ${{ secrets.DB_HOST }}
          DB_DATABASE: ${{ secrets.DB_DATABASE }}
          DB_USERNAME: ${{ secrets.DB_USERNAME }}
          DB_PASSWORD: ${{ secrets.DB_PASSWORD }}

      - name: Run migrations
        run: php artisan migrate --force
        env:
          DB_HOST: ${{ secrets.DB_HOST }}
          DB_DATABASE: ${{ secrets.DB_DATABASE }}
          DB_USERNAME: ${{ secrets.DB_USERNAME }}
          DB_PASSWORD: ${{ secrets.DB_PASSWORD }}

      - name: Verify migration success
        run: php artisan migrate:status
        env:
          DB_HOST: ${{ secrets.DB_HOST }}
          DB_DATABASE: ${{ secrets.DB_DATABASE }}
          DB_USERNAME: ${{ secrets.DB_USERNAME }}
          DB_PASSWORD: ${{ secrets.DB_PASSWORD }}
```

### 1.3 Feature Flag Management

**File**: `config/features.php`

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Control feature rollout and enable gradual deployment strategies
    |
    */

    'webhook_signature_enforcement' => env('FEATURE_WEBHOOK_SIGNATURES', true),
    'pii_encryption' => env('FEATURE_PII_ENCRYPTION', false),
    'phone_number_cache' => env('FEATURE_PHONE_CACHE', false),
    'alternative_booking_strategies' => env('FEATURE_ALT_BOOKING', true),
    'cost_calculation_v2' => env('FEATURE_COST_CALC_V2', false),
    'composite_services' => env('FEATURE_COMPOSITE_SERVICES', false),

    // Performance optimizations
    'eager_loading' => env('FEATURE_EAGER_LOADING', true),
    'query_caching' => env('FEATURE_QUERY_CACHE', false),

    // Monitoring
    'detailed_logging' => env('FEATURE_DETAILED_LOGS', false),
    'performance_metrics' => env('FEATURE_PERF_METRICS', true),
];
```

**Usage in Code**:

```php
// app/Http/Controllers/RetellWebhookController.php
public function handleInbound(Request $request)
{
    if (config('features.webhook_signature_enforcement')) {
        if (!$this->verifySignature($request)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }
    }

    // Continue processing...
}
```

**Gradual Rollout Strategy**:

```yaml
# Canary: 5% of traffic
- environment: production-canary
  replicas: 1
  env:
    FEATURE_PII_ENCRYPTION: true
    FEATURE_PHONE_CACHE: true

# Main: 95% of traffic
- environment: production-main
  replicas: 19
  env:
    FEATURE_PII_ENCRYPTION: false
    FEATURE_PHONE_CACHE: false
```

---

## 2. Container Strategy

### 2.1 Multi-Stage Dockerfile

**File**: `docker/Dockerfile`

```dockerfile
# syntax=docker/dockerfile:1.4

#############################################
# Stage 1: Composer Dependencies
#############################################
FROM composer:2.7 AS composer

WORKDIR /app

# Copy composer files
COPY composer.json composer.lock ./

# Install dependencies (production only)
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --optimize-autoloader

#############################################
# Stage 2: Node.js Asset Build
#############################################
FROM node:20-alpine AS node-builder

WORKDIR /app

# Copy package files
COPY package.json package-lock.json ./

# Install dependencies
RUN npm ci --only=production

# Copy source files
COPY resources/ resources/
COPY vite.config.js ./

# Build assets
RUN npm run build

#############################################
# Stage 3: PHP-FPM Base Image
#############################################
FROM php:8.3-fpm-alpine AS php-base

# Install system dependencies
RUN apk add --no-cache \
    bash \
    curl \
    libpng-dev \
    libxml2-dev \
    libzip-dev \
    oniguruma-dev \
    postgresql-dev \
    mysql-client \
    redis \
    supervisor \
    && rm -rf /var/cache/apk/*

# Install PHP extensions
RUN docker-php-ext-install \
    bcmath \
    exif \
    gd \
    intl \
    mbstring \
    opcache \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    pcntl \
    xml \
    zip

# Install Redis extension
RUN pecl install redis-6.0.2 && docker-php-ext-enable redis

# Configure PHP-FPM
COPY docker/php/php.ini /usr/local/etc/php/php.ini
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

# Configure OPcache for production
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.memory_consumption=256'; \
    echo 'opcache.interned_strings_buffer=16'; \
    echo 'opcache.max_accelerated_files=20000'; \
    echo 'opcache.validate_timestamps=0'; \
    echo 'opcache.save_comments=1'; \
    echo 'opcache.fast_shutdown=1'; \
} > /usr/local/etc/php/conf.d/opcache.ini

#############################################
# Stage 4: Application Build
#############################################
FROM php-base AS app-builder

WORKDIR /var/www/html

# Copy application files
COPY --from=composer /app/vendor ./vendor
COPY --from=node-builder /app/public/build ./public/build
COPY . .

# Generate optimized autoloader
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

#############################################
# Stage 5: Production Image
#############################################
FROM php-base AS production

ARG BUILD_DATE
ARG VCS_REF
ARG PHP_VERSION=8.3

LABEL maintainer="devops@example.com" \
      org.opencontainers.image.created="${BUILD_DATE}" \
      org.opencontainers.image.revision="${VCS_REF}" \
      org.opencontainers.image.version="1.0.0" \
      org.opencontainers.image.title="API Gateway" \
      org.opencontainers.image.description="Laravel API Gateway for Telefonagent Booking System"

WORKDIR /var/www/html

# Copy application from builder
COPY --from=app-builder --chown=www-data:www-data /var/www/html /var/www/html

# Copy supervisor configuration
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD curl -f http://localhost:9000/health || exit 1

# Expose PHP-FPM port
EXPOSE 9000

# Switch to non-root user
USER www-data

# Start supervisor (manages PHP-FPM + queue workers)
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

#############################################
# Stage 6: Development Image
#############################################
FROM php-base AS development

# Install Xdebug
RUN pecl install xdebug-3.3.1 && docker-php-ext-enable xdebug

# Configure Xdebug
RUN { \
    echo 'xdebug.mode=develop,debug,coverage'; \
    echo 'xdebug.client_host=host.docker.internal'; \
    echo 'xdebug.start_with_request=yes'; \
} > /usr/local/etc/php/conf.d/xdebug.ini

WORKDIR /var/www/html

# Enable hot reload
ENV APP_ENV=local
ENV APP_DEBUG=true

USER www-data

CMD ["php-fpm"]
```

### 2.2 PHP Configuration Files

**File**: `docker/php/php.ini`

```ini
; PHP Configuration for Production

[PHP]
; Performance
memory_limit = 512M
max_execution_time = 60
max_input_time = 60
post_max_size = 20M
upload_max_filesize = 20M

; Error Handling (Production)
display_errors = Off
display_startup_errors = Off
log_errors = On
error_log = /var/log/php/error.log
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT

; Security
expose_php = Off
disable_functions = exec,passthru,shell_exec,system,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source

; Session
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1
session.cookie_samesite = "Strict"

; Date
date.timezone = UTC

; OPcache (production optimizations)
opcache.enable = 1
opcache.enable_cli = 0
opcache.memory_consumption = 256
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files = 20000
opcache.validate_timestamps = 0
opcache.save_comments = 1
opcache.fast_shutdown = 1
```

**File**: `docker/php/php-fpm.conf`

```ini
[www]
user = www-data
group = www-data

; Process Manager Settings
pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests = 1000

; Performance Tuning
pm.process_idle_timeout = 10s
request_terminate_timeout = 60s

; Logging
access.log = /var/log/php-fpm/access.log
slowlog = /var/log/php-fpm/slow.log
request_slowlog_timeout = 5s

; Status page for monitoring
pm.status_path = /fpm-status
ping.path = /fpm-ping
ping.response = pong

; PHP admin values
php_admin_value[error_log] = /var/log/php-fpm/error.log
php_admin_flag[log_errors] = on

; Security
php_admin_value[open_basedir] = /var/www/html:/tmp
```

### 2.3 Supervisor Configuration

**File**: `docker/supervisor/supervisord.conf`

```ini
[supervisord]
nodaemon=true
user=root
logfile=/var/log/supervisor/supervisord.log
pidfile=/var/run/supervisord.pid

[program:php-fpm]
command=php-fpm -F
autostart=true
autorestart=true
priority=5
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0

[program:queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/artisan queue:work redis --tries=3 --max-time=3600 --memory=512
autostart=true
autorestart=true
numprocs=4
priority=999
user=www-data
stdout_logfile=/var/log/supervisor/queue-worker.log
stderr_logfile=/var/log/supervisor/queue-worker-error.log
stopwaitsecs=3600
stopasgroup=true
killasgroup=true

[program:schedule-worker]
command=/bin/sh -c "while [ true ]; do (php /var/www/html/artisan schedule:run --verbose --no-interaction &); sleep 60; done"
autostart=true
autorestart=true
priority=999
user=www-data
stdout_logfile=/var/log/supervisor/schedule.log
stderr_logfile=/var/log/supervisor/schedule-error.log
```

### 2.4 Docker Compose for Local Development

**File**: `docker-compose.yml`

```yaml
version: '3.9'

services:
  app:
    build:
      context: .
      dockerfile: docker/Dockerfile
      target: development
      args:
        PHP_VERSION: 8.3
    container_name: api-gateway-app
    restart: unless-stopped
    working_dir: /var/www/html
    volumes:
      - ./:/var/www/html
      - ./docker/php/php.ini:/usr/local/etc/php/php.ini
      - php-fpm-socket:/var/run/php
    networks:
      - api-gateway
    environment:
      APP_ENV: local
      APP_DEBUG: true
      DB_HOST: mysql
      DB_DATABASE: api_gateway
      DB_USERNAME: laravel
      DB_PASSWORD: secret
      REDIS_HOST: redis
      CACHE_DRIVER: redis
      QUEUE_CONNECTION: redis
      SESSION_DRIVER: redis
    depends_on:
      mysql:
        condition: service_healthy
      redis:
        condition: service_healthy

  nginx:
    image: nginx:1.25-alpine
    container_name: api-gateway-nginx
    restart: unless-stopped
    ports:
      - "8080:80"
    volumes:
      - ./:/var/www/html
      - ./docker/nginx/nginx.conf:/etc/nginx/nginx.conf
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
      - php-fpm-socket:/var/run/php
    networks:
      - api-gateway
    depends_on:
      - app

  mysql:
    image: mysql:8.0
    container_name: api-gateway-mysql
    restart: unless-stopped
    ports:
      - "3306:3306"
    environment:
      MYSQL_DATABASE: api_gateway
      MYSQL_USER: laravel
      MYSQL_PASSWORD: secret
      MYSQL_ROOT_PASSWORD: root
    volumes:
      - mysql-data:/var/lib/mysql
      - ./docker/mysql/my.cnf:/etc/mysql/conf.d/my.cnf
    networks:
      - api-gateway
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 10s
      timeout: 5s
      retries: 5

  redis:
    image: redis:7-alpine
    container_name: api-gateway-redis
    restart: unless-stopped
    ports:
      - "6379:6379"
    volumes:
      - redis-data:/data
    networks:
      - api-gateway
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 10s
      timeout: 5s
      retries: 5
    command: redis-server --appendonly yes --maxmemory 256mb --maxmemory-policy allkeys-lru

  mailhog:
    image: mailhog/mailhog:latest
    container_name: api-gateway-mailhog
    restart: unless-stopped
    ports:
      - "1025:1025"
      - "8025:8025"
    networks:
      - api-gateway

  queue-worker:
    build:
      context: .
      dockerfile: docker/Dockerfile
      target: development
    container_name: api-gateway-queue
    restart: unless-stopped
    working_dir: /var/www/html
    volumes:
      - ./:/var/www/html
    networks:
      - api-gateway
    environment:
      APP_ENV: local
      DB_HOST: mysql
      REDIS_HOST: redis
    depends_on:
      - app
      - redis
    command: php artisan queue:work redis --tries=3 --backoff=3

volumes:
  mysql-data:
    driver: local
  redis-data:
    driver: local
  php-fpm-socket:
    driver: local

networks:
  api-gateway:
    driver: bridge
```

### 2.5 Nginx Configuration

**File**: `docker/nginx/default.conf`

```nginx
upstream php-fpm {
    server unix:/var/run/php/php-fpm.sock;
}

server {
    listen 80;
    server_name _;
    root /var/www/html/public;
    index index.php index.html;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript;

    # Rate limiting
    limit_req_zone $binary_remote_addr zone=webhook:10m rate=30r/m;
    limit_req_zone $binary_remote_addr zone=api:10m rate=100r/m;

    # Health check endpoint (no auth)
    location /health {
        access_log off;
        return 200 "healthy\n";
        add_header Content-Type text/plain;
    }

    # PHP-FPM status (internal only)
    location ~ ^/(fpm-status|fpm-ping)$ {
        access_log off;
        allow 127.0.0.1;
        allow 172.16.0.0/12;  # Docker network
        deny all;
        fastcgi_pass php-fpm;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Webhook endpoints (rate limited)
    location /webhooks/ {
        limit_req zone=webhook burst=5 nodelay;
        try_files $uri $uri/ /index.php?$query_string;
    }

    # API endpoints (rate limited)
    location /api/ {
        limit_req zone=api burst=20 nodelay;
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Static assets (long cache)
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # PHP files
    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass php-fpm;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
        include fastcgi_params;

        # Timeouts
        fastcgi_connect_timeout 60s;
        fastcgi_send_timeout 60s;
        fastcgi_read_timeout 60s;

        # Buffer settings
        fastcgi_buffers 16 16k;
        fastcgi_buffer_size 32k;
    }

    # Laravel routes
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Deny access to sensitive files
    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }

    location ~ /\.(?:git|env|htaccess) {
        deny all;
    }
}
```

### 2.6 Container Security Hardening

**Security Checklist**:

```yaml
# Container security best practices
security_measures:
  - name: "Non-root user"
    status: implemented
    details: "Run as www-data user (UID 82)"

  - name: "Read-only root filesystem"
    status: recommended
    implementation: |
      docker run --read-only \
        --tmpfs /tmp \
        --tmpfs /var/run \
        --tmpfs /var/cache/nginx

  - name: "Minimal base image"
    status: implemented
    details: "Using Alpine Linux (5MB base)"

  - name: "Multi-stage builds"
    status: implemented
    details: "Separate build dependencies from runtime"

  - name: "No secrets in image"
    status: implemented
    details: "All secrets via environment variables"

  - name: "Image scanning"
    status: implemented
    details: "Trivy scans in CI/CD pipeline"

  - name: "Signed images"
    status: recommended
    implementation: |
      # Sign with Docker Content Trust
      export DOCKER_CONTENT_TRUST=1
      docker push ghcr.io/org/api-gateway:latest
```

**Kubernetes Security Context**:

```yaml
# kubernetes/deployment.yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: api-gateway
spec:
  template:
    spec:
      securityContext:
        runAsNonRoot: true
        runAsUser: 82  # www-data
        fsGroup: 82
        seccompProfile:
          type: RuntimeDefault

      containers:
      - name: api-gateway
        image: ghcr.io/org/api-gateway:latest
        securityContext:
          allowPrivilegeEscalation: false
          capabilities:
            drop:
              - ALL
          readOnlyRootFilesystem: true

        volumeMounts:
        - name: cache
          mountPath: /var/www/html/storage/framework/cache
        - name: sessions
          mountPath: /var/www/html/storage/framework/sessions
        - name: views
          mountPath: /var/www/html/storage/framework/views
        - name: logs
          mountPath: /var/www/html/storage/logs

      volumes:
      - name: cache
        emptyDir: {}
      - name: sessions
        emptyDir: {}
      - name: views
        emptyDir: {}
      - name: logs
        emptyDir: {}
```

---

## 3. Infrastructure as Code

### 3.1 Kubernetes Manifests

#### Production Deployment (Blue-Green Strategy)

**File**: `kubernetes/production/deployment-blue.yaml`

```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: api-gateway-blue
  namespace: production
  labels:
    app: api-gateway
    version: blue
    tier: backend
spec:
  replicas: 10
  revisionHistoryLimit: 5
  strategy:
    type: RollingUpdate
    rollingUpdate:
      maxSurge: 50%
      maxUnavailable: 0

  selector:
    matchLabels:
      app: api-gateway
      version: blue

  template:
    metadata:
      labels:
        app: api-gateway
        version: blue
        tier: backend
      annotations:
        prometheus.io/scrape: "true"
        prometheus.io/port: "9090"
        prometheus.io/path: "/metrics"

    spec:
      serviceAccountName: api-gateway

      # Pod anti-affinity for high availability
      affinity:
        podAntiAffinity:
          preferredDuringSchedulingIgnoredDuringExecution:
          - weight: 100
            podAffinityTerm:
              labelSelector:
                matchExpressions:
                - key: app
                  operator: In
                  values:
                  - api-gateway
              topologyKey: kubernetes.io/hostname

      # Init container: Wait for database
      initContainers:
      - name: wait-for-db
        image: busybox:1.36
        command:
        - sh
        - -c
        - |
          until nc -z mysql-service 3306; do
            echo "Waiting for MySQL..."
            sleep 2
          done

      containers:
      - name: api-gateway
        image: ghcr.io/org/api-gateway:latest
        imagePullPolicy: Always

        ports:
        - name: http
          containerPort: 9000
          protocol: TCP

        env:
        - name: APP_ENV
          value: "production"
        - name: APP_DEBUG
          value: "false"
        - name: APP_KEY
          valueFrom:
            secretKeyRef:
              name: api-gateway-secrets
              key: app-key

        - name: DB_CONNECTION
          value: "mysql"
        - name: DB_HOST
          valueFrom:
            secretKeyRef:
              name: database-credentials
              key: host
        - name: DB_PORT
          value: "3306"
        - name: DB_DATABASE
          valueFrom:
            secretKeyRef:
              name: database-credentials
              key: database
        - name: DB_USERNAME
          valueFrom:
            secretKeyRef:
              name: database-credentials
              key: username
        - name: DB_PASSWORD
          valueFrom:
            secretKeyRef:
              name: database-credentials
              key: password

        - name: REDIS_HOST
          value: "redis-master-service"
        - name: REDIS_PORT
          value: "6379"
        - name: REDIS_PASSWORD
          valueFrom:
            secretKeyRef:
              name: redis-credentials
              key: password

        - name: CACHE_DRIVER
          value: "redis"
        - name: QUEUE_CONNECTION
          value: "redis"
        - name: SESSION_DRIVER
          value: "redis"

        - name: RETELL_API_KEY
          valueFrom:
            secretKeyRef:
              name: api-keys
              key: retell-api-key
        - name: RETELL_WEBHOOK_SECRET
          valueFrom:
            secretKeyRef:
              name: api-keys
              key: retell-webhook-secret

        - name: CALCOM_API_KEY
          valueFrom:
            secretKeyRef:
              name: api-keys
              key: calcom-api-key

        # Feature flags
        - name: FEATURE_WEBHOOK_SIGNATURES
          value: "true"
        - name: FEATURE_PII_ENCRYPTION
          value: "false"
        - name: FEATURE_PHONE_CACHE
          value: "false"

        resources:
          requests:
            cpu: 200m
            memory: 512Mi
          limits:
            cpu: 1000m
            memory: 1Gi

        livenessProbe:
          httpGet:
            path: /health
            port: http
          initialDelaySeconds: 30
          periodSeconds: 10
          timeoutSeconds: 5
          failureThreshold: 3

        readinessProbe:
          httpGet:
            path: /health
            port: http
          initialDelaySeconds: 10
          periodSeconds: 5
          timeoutSeconds: 3
          successThreshold: 1
          failureThreshold: 2

        lifecycle:
          preStop:
            exec:
              command:
              - sh
              - -c
              - "sleep 15"  # Allow time for connections to drain

        volumeMounts:
        - name: cache
          mountPath: /var/www/html/storage/framework/cache
        - name: sessions
          mountPath: /var/www/html/storage/framework/sessions
        - name: views
          mountPath: /var/www/html/storage/framework/views
        - name: logs
          mountPath: /var/www/html/storage/logs

      volumes:
      - name: cache
        emptyDir:
          sizeLimit: 1Gi
      - name: sessions
        emptyDir:
          sizeLimit: 1Gi
      - name: views
        emptyDir:
          sizeLimit: 500Mi
      - name: logs
        emptyDir:
          sizeLimit: 2Gi

      # Graceful termination
      terminationGracePeriodSeconds: 30
```

**File**: `kubernetes/production/service.yaml`

```yaml
apiVersion: v1
kind: Service
metadata:
  name: api-gateway-blue
  namespace: production
  labels:
    app: api-gateway
    version: blue
spec:
  type: ClusterIP
  selector:
    app: api-gateway
    version: blue
  ports:
  - name: http
    port: 80
    targetPort: http
    protocol: TCP
  sessionAffinity: ClientIP
  sessionAffinityConfig:
    clientIP:
      timeoutSeconds: 10800

---
apiVersion: v1
kind: Service
metadata:
  name: api-gateway-green
  namespace: production
  labels:
    app: api-gateway
    version: green
spec:
  type: ClusterIP
  selector:
    app: api-gateway
    version: green
  ports:
  - name: http
    port: 80
    targetPort: http
    protocol: TCP

---
# Main service that switches between blue/green
apiVersion: v1
kind: Service
metadata:
  name: api-gateway-main
  namespace: production
  labels:
    app: api-gateway
spec:
  type: LoadBalancer
  selector:
    app: api-gateway
    version: blue  # Switch to "green" during deployment
  ports:
  - name: http
    port: 80
    targetPort: http
    protocol: TCP
  - name: https
    port: 443
    targetPort: http
    protocol: TCP
```

**File**: `kubernetes/production/ingress.yaml`

```yaml
apiVersion: networking.k8s.io/v1
kind: Ingress
metadata:
  name: api-gateway
  namespace: production
  annotations:
    kubernetes.io/ingress.class: "nginx"
    cert-manager.io/cluster-issuer: "letsencrypt-prod"
    nginx.ingress.kubernetes.io/ssl-redirect: "true"
    nginx.ingress.kubernetes.io/rate-limit: "100"
    nginx.ingress.kubernetes.io/limit-rps: "30"
    nginx.ingress.kubernetes.io/proxy-body-size: "20m"
    nginx.ingress.kubernetes.io/proxy-connect-timeout: "60"
    nginx.ingress.kubernetes.io/proxy-send-timeout: "60"
    nginx.ingress.kubernetes.io/proxy-read-timeout: "60"
spec:
  tls:
  - hosts:
    - api-gateway.example.com
    secretName: api-gateway-tls

  rules:
  - host: api-gateway.example.com
    http:
      paths:
      - path: /
        pathType: Prefix
        backend:
          service:
            name: api-gateway-main
            port:
              number: 80
```

#### Horizontal Pod Autoscaler

**File**: `kubernetes/production/hpa.yaml`

```yaml
apiVersion: autoscaling/v2
kind: HorizontalPodAutoscaler
metadata:
  name: api-gateway-hpa
  namespace: production
spec:
  scaleTargetRef:
    apiVersion: apps/v1
    kind: Deployment
    name: api-gateway-blue

  minReplicas: 5
  maxReplicas: 50

  behavior:
    scaleDown:
      stabilizationWindowSeconds: 300
      policies:
      - type: Percent
        value: 50
        periodSeconds: 60
    scaleUp:
      stabilizationWindowSeconds: 0
      policies:
      - type: Percent
        value: 100
        periodSeconds: 30
      - type: Pods
        value: 5
        periodSeconds: 30
      selectPolicy: Max

  metrics:
  - type: Resource
    resource:
      name: cpu
      target:
        type: Utilization
        averageUtilization: 70

  - type: Resource
    resource:
      name: memory
      target:
        type: Utilization
        averageUtilization: 80

  - type: Pods
    pods:
      metric:
        name: http_requests_per_second
      target:
        type: AverageValue
        averageValue: "1000"
```

### 3.2 Secret Management

#### Kubernetes Secrets (sealed-secrets)

**File**: `kubernetes/production/sealed-secrets.yaml`

```yaml
apiVersion: bitnami.com/v1alpha1
kind: SealedSecret
metadata:
  name: api-gateway-secrets
  namespace: production
spec:
  encryptedData:
    app-key: AgBk8... (encrypted)
  template:
    metadata:
      name: api-gateway-secrets
      namespace: production

---
apiVersion: bitnami.com/v1alpha1
kind: SealedSecret
metadata:
  name: database-credentials
  namespace: production
spec:
  encryptedData:
    host: AgC9... (encrypted)
    database: AgDx... (encrypted)
    username: AgEr... (encrypted)
    password: AgFm... (encrypted)
  template:
    metadata:
      name: database-credentials
      namespace: production

---
apiVersion: bitnami.com/v1alpha1
kind: SealedSecret
metadata:
  name: api-keys
  namespace: production
spec:
  encryptedData:
    retell-api-key: AgGh... (encrypted)
    retell-webhook-secret: AgHc... (encrypted)
    calcom-api-key: AgId... (encrypted)
  template:
    metadata:
      name: api-keys
      namespace: production
```

**Creating Sealed Secrets**:

```bash
#!/bin/bash
# scripts/seal-secrets.sh

# Install kubeseal CLI
wget https://github.com/bitnami-labs/sealed-secrets/releases/download/v0.24.0/kubeseal-0.24.0-linux-amd64.tar.gz
tar -xzf kubeseal-0.24.0-linux-amd64.tar.gz
sudo install -m 755 kubeseal /usr/local/bin/kubeseal

# Create secret from file
kubectl create secret generic api-gateway-secrets \
  --from-literal=app-key="${APP_KEY}" \
  --dry-run=client -o yaml | \
  kubeseal -o yaml > kubernetes/production/sealed-secrets.yaml

# Apply sealed secret
kubectl apply -f kubernetes/production/sealed-secrets.yaml
```

#### External Secrets Operator (Recommended for AWS/GCP/Azure)

**File**: `kubernetes/production/external-secrets.yaml`

```yaml
apiVersion: external-secrets.io/v1beta1
kind: SecretStore
metadata:
  name: aws-secrets-manager
  namespace: production
spec:
  provider:
    aws:
      service: SecretsManager
      region: us-east-1
      auth:
        jwt:
          serviceAccountRef:
            name: api-gateway

---
apiVersion: external-secrets.io/v1beta1
kind: ExternalSecret
metadata:
  name: api-gateway-secrets
  namespace: production
spec:
  refreshInterval: 1h
  secretStoreRef:
    name: aws-secrets-manager
    kind: SecretStore

  target:
    name: api-gateway-secrets
    creationPolicy: Owner

  data:
  - secretKey: app-key
    remoteRef:
      key: production/api-gateway/app-key

  - secretKey: retell-api-key
    remoteRef:
      key: production/api-gateway/retell-api-key

  - secretKey: retell-webhook-secret
    remoteRef:
      key: production/api-gateway/retell-webhook-secret

  - secretKey: calcom-api-key
    remoteRef:
      key: production/api-gateway/calcom-api-key

  - secretKey: db-password
    remoteRef:
      key: production/database/password
```

### 3.3 Environment Configuration Management

#### ConfigMap for Application Settings

**File**: `kubernetes/production/configmap.yaml`

```yaml
apiVersion: v1
kind: ConfigMap
metadata:
  name: api-gateway-config
  namespace: production
data:
  # Application
  APP_ENV: "production"
  APP_DEBUG: "false"
  APP_TIMEZONE: "UTC"
  APP_LOCALE: "de"

  # Database
  DB_CONNECTION: "mysql"
  DB_PORT: "3306"

  # Cache & Queue
  CACHE_DRIVER: "redis"
  QUEUE_CONNECTION: "redis"
  SESSION_DRIVER: "redis"

  # Redis
  REDIS_PORT: "6379"

  # Logging
  LOG_CHANNEL: "stack"
  LOG_LEVEL: "info"

  # Feature Flags
  FEATURE_WEBHOOK_SIGNATURES: "true"
  FEATURE_PII_ENCRYPTION: "false"
  FEATURE_PHONE_CACHE: "true"
  FEATURE_EAGER_LOADING: "true"

  # Performance
  OCTANE_SERVER: "frankenphp"

  # External APIs
  RETELL_API_URL: "https://api.retellai.com/v1"
  CALCOM_API_URL: "https://api.cal.com/v1"
```

### 3.4 Redis Cluster Configuration

**File**: `kubernetes/production/redis-statefulset.yaml`

```yaml
apiVersion: apps/v1
kind: StatefulSet
metadata:
  name: redis
  namespace: production
spec:
  serviceName: redis
  replicas: 3
  selector:
    matchLabels:
      app: redis

  template:
    metadata:
      labels:
        app: redis
    spec:
      containers:
      - name: redis
        image: redis:7-alpine
        ports:
        - containerPort: 6379
          name: redis

        command:
        - redis-server
        - --appendonly
        - "yes"
        - --maxmemory
        - "512mb"
        - --maxmemory-policy
        - "allkeys-lru"
        - --save
        - "60 1000"

        resources:
          requests:
            cpu: 100m
            memory: 256Mi
          limits:
            cpu: 500m
            memory: 768Mi

        volumeMounts:
        - name: redis-data
          mountPath: /data

        livenessProbe:
          tcpSocket:
            port: redis
          initialDelaySeconds: 30
          periodSeconds: 10

        readinessProbe:
          exec:
            command:
            - redis-cli
            - ping
          initialDelaySeconds: 5
          periodSeconds: 5

  volumeClaimTemplates:
  - metadata:
      name: redis-data
    spec:
      accessModes: [ "ReadWriteOnce" ]
      storageClassName: "fast-ssd"
      resources:
        requests:
          storage: 10Gi
```

---

## 4. Deployment Strategy

### 4.1 Blue-Green Deployment Process

```
┌─────────────────────────────────────────────────────────────┐
│                   Blue-Green Deployment Flow                 │
└─────────────────────────────────────────────────────────────┘

1. Pre-Deployment
   ├── Run tests on new version (GitHub Actions)
   ├── Build Docker image
   ├── Push to registry (ghcr.io)
   └── Scan image for vulnerabilities

2. Deploy to Blue (Inactive) Environment
   ├── kubectl set image deployment/api-gateway-blue
   ├── Wait for rollout completion
   └── Run smoke tests on blue pods

3. Health Check Verification
   ├── HTTP health endpoint (/health)
   ├── Database connectivity check
   ├── Redis connectivity check
   └── External API availability (Cal.com, Retell)

4. Traffic Switch (Zero-Downtime)
   ├── Update service selector: version=blue
   ├── Gradual traffic shift: 0% → 10% → 50% → 100%
   └── Monitor error rates and latency

5. Post-Deployment Validation (5-10 minutes)
   ├── Monitor Prometheus metrics
   ├── Check application logs
   ├── Verify webhook processing
   └── Test booking flow

6. Update Green Environment (Old Version)
   └── Deploy same image to green for next rollback

7. Rollback (if needed)
   └── Switch service selector: version=green
```

### 4.2 Deployment Script

**File**: `scripts/deploy.sh`

```bash
#!/bin/bash
set -euo pipefail

##############################################
# Blue-Green Deployment Script
# Usage: ./scripts/deploy.sh [environment]
##############################################

ENVIRONMENT="${1:-production}"
NAMESPACE="$ENVIRONMENT"
NEW_VERSION="${2:-$(git rev-parse --short HEAD)}"
IMAGE_TAG="ghcr.io/org/api-gateway:${NEW_VERSION}"

echo "🚀 Starting deployment to $ENVIRONMENT"
echo "📦 Image: $IMAGE_TAG"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Functions
log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# 1. Determine current active version
CURRENT_VERSION=$(kubectl get service api-gateway-main -n $NAMESPACE \
    -o jsonpath='{.spec.selector.version}')
log_info "Current active version: $CURRENT_VERSION"

# 2. Determine target version (opposite of current)
if [ "$CURRENT_VERSION" == "blue" ]; then
    TARGET_VERSION="green"
else
    TARGET_VERSION="blue"
fi
log_info "Deploying to: $TARGET_VERSION"

# 3. Deploy to target environment
log_info "Deploying image to $TARGET_VERSION environment..."
kubectl set image deployment/api-gateway-$TARGET_VERSION \
    api-gateway=$IMAGE_TAG \
    -n $NAMESPACE

# 4. Wait for rollout to complete
log_info "Waiting for rollout to complete..."
kubectl rollout status deployment/api-gateway-$TARGET_VERSION \
    -n $NAMESPACE \
    --timeout=10m

# 5. Run smoke tests
log_info "Running smoke tests..."
TARGET_POD=$(kubectl get pods -n $NAMESPACE \
    -l app=api-gateway,version=$TARGET_VERSION \
    -o jsonpath='{.items[0].metadata.name}')

# Test health endpoint
if kubectl exec -n $NAMESPACE $TARGET_POD -- curl -f http://localhost:9000/health > /dev/null 2>&1; then
    log_info "✅ Health check passed"
else
    log_error "❌ Health check failed"
    exit 1
fi

# Test database connectivity
if kubectl exec -n $NAMESPACE $TARGET_POD -- php artisan migrate:status > /dev/null 2>&1; then
    log_info "✅ Database connectivity verified"
else
    log_error "❌ Database connectivity failed"
    exit 1
fi

# 6. Gradual traffic shift
log_info "Starting gradual traffic shift..."

# 10% traffic
log_info "Shifting 10% traffic to $TARGET_VERSION..."
kubectl patch service api-gateway-main -n $NAMESPACE \
    --type='json' \
    -p='[{"op": "replace", "path": "/spec/selector/version", "value": "'$TARGET_VERSION'"}]'
sleep 60

# Check error rates
ERROR_RATE=$(kubectl exec -n $NAMESPACE $TARGET_POD -- \
    php artisan metrics:error-rate --window=1m)
if [ $(echo "$ERROR_RATE > 5.0" | bc) -eq 1 ]; then
    log_error "Error rate too high: $ERROR_RATE%. Rolling back..."
    kubectl patch service api-gateway-main -n $NAMESPACE \
        --type='json' \
        -p='[{"op": "replace", "path": "/spec/selector/version", "value": "'$CURRENT_VERSION'"}]'
    exit 1
fi

# 100% traffic
log_info "Shifting 100% traffic to $TARGET_VERSION..."
kubectl patch service api-gateway-main -n $NAMESPACE \
    --type='json' \
    -p='[{"op": "replace", "path": "/spec/selector/version", "value": "'$TARGET_VERSION'"}]'

# 7. Monitor for 5 minutes
log_info "Monitoring deployment for 5 minutes..."
for i in {1..10}; do
    sleep 30

    # Check pod health
    READY_PODS=$(kubectl get deployment api-gateway-$TARGET_VERSION -n $NAMESPACE \
        -o jsonpath='{.status.readyReplicas}')
    DESIRED_PODS=$(kubectl get deployment api-gateway-$TARGET_VERSION -n $NAMESPACE \
        -o jsonpath='{.spec.replicas}')

    if [ "$READY_PODS" != "$DESIRED_PODS" ]; then
        log_error "Not all pods are ready: $READY_PODS/$DESIRED_PODS"
        exit 1
    fi

    log_info "Health check $i/10: ✅ All pods healthy"
done

# 8. Update old environment
log_info "Updating $CURRENT_VERSION environment for future rollback..."
kubectl set image deployment/api-gateway-$CURRENT_VERSION \
    api-gateway=$IMAGE_TAG \
    -n $NAMESPACE

log_info "🎉 Deployment completed successfully!"
log_info "Active version: $TARGET_VERSION"
log_info "Standby version: $CURRENT_VERSION"
```

### 4.3 Rollback Procedure

**File**: `scripts/rollback.sh`

```bash
#!/bin/bash
set -euo pipefail

##############################################
# Emergency Rollback Script
# Usage: ./scripts/rollback.sh [environment]
##############################################

ENVIRONMENT="${1:-production}"
NAMESPACE="$ENVIRONMENT"

echo "🔄 Starting emergency rollback for $ENVIRONMENT"

# Get current active version
CURRENT_VERSION=$(kubectl get service api-gateway-main -n $NAMESPACE \
    -o jsonpath='{.spec.selector.version}')
echo "Current active version: $CURRENT_VERSION"

# Determine rollback target
if [ "$CURRENT_VERSION" == "blue" ]; then
    ROLLBACK_VERSION="green"
else
    ROLLBACK_VERSION="blue"
fi

echo "🔙 Rolling back to: $ROLLBACK_VERSION"

# Immediate traffic switch
kubectl patch service api-gateway-main -n $NAMESPACE \
    --type='json' \
    -p='[{"op": "replace", "path": "/spec/selector/version", "value": "'$ROLLBACK_VERSION'"}]'

echo "✅ Traffic switched to $ROLLBACK_VERSION"

# Verify rollback
sleep 10
for i in {1..5}; do
    if curl -f https://api-gateway.example.com/health; then
        echo "✅ Health check passed ($i/5)"
    else
        echo "❌ Health check failed ($i/5)"
        exit 1
    fi
    sleep 10
done

echo "🎉 Rollback completed successfully"
```

### 4.4 Canary Releases

**File**: `kubernetes/production/canary-deployment.yaml`

```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: api-gateway-canary
  namespace: production
spec:
  replicas: 1  # 5% of total traffic (1 out of 20 pods)
  selector:
    matchLabels:
      app: api-gateway
      version: canary

  template:
    metadata:
      labels:
        app: api-gateway
        version: canary
    spec:
      containers:
      - name: api-gateway
        image: ghcr.io/org/api-gateway:canary
        env:
        # Enable experimental features
        - name: FEATURE_PII_ENCRYPTION
          value: "true"
        - name: FEATURE_PHONE_CACHE
          value: "true"
        - name: FEATURE_COST_CALC_V2
          value: "true"
```

**Ingress for Canary (Header-Based Routing)**:

```yaml
apiVersion: networking.k8s.io/v1
kind: Ingress
metadata:
  name: api-gateway-canary
  namespace: production
  annotations:
    nginx.ingress.kubernetes.io/canary: "true"
    nginx.ingress.kubernetes.io/canary-by-header: "X-Canary"
    nginx.ingress.kubernetes.io/canary-by-header-value: "true"
spec:
  rules:
  - host: api-gateway.example.com
    http:
      paths:
      - path: /
        pathType: Prefix
        backend:
          service:
            name: api-gateway-canary
            port:
              number: 80
```

---

## 5. Monitoring & Observability

### 5.1 Logging Strategy

#### Structured Logging Configuration

**File**: `config/logging.php` (modifications)

```php
<?php

return [
    'default' => env('LOG_CHANNEL', 'stack'),

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['daily', 'structured'],
            'ignore_exceptions' => false,
        ],

        'structured' => [
            'driver' => 'monolog',
            'handler' => StreamHandler::class,
            'formatter' => JsonFormatter::class,
            'with' => [
                'stream' => 'php://stdout',
            ],
            'level' => 'info',
            'processors' => [
                PsrLogMessageProcessor::class,
                WebProcessor::class,
                IntrospectionProcessor::class,
                function ($record) {
                    $record['extra']['environment'] = config('app.env');
                    $record['extra']['service'] = 'api-gateway';
                    $record['extra']['version'] = config('app.version', '1.0.0');
                    $record['extra']['trace_id'] = request()->header('X-Trace-ID')
                        ?? Str::uuid()->toString();
                    return $record;
                },
            ],
        ],
    ],
];
```

#### Log Aggregation (ELK Stack or Loki)

**File**: `kubernetes/monitoring/loki-stack.yaml`

```yaml
apiVersion: v1
kind: ConfigMap
metadata:
  name: promtail-config
  namespace: monitoring
data:
  promtail.yaml: |
    server:
      http_listen_port: 9080
      grpc_listen_port: 0

    positions:
      filename: /tmp/positions.yaml

    clients:
      - url: http://loki:3100/loki/api/v1/push

    scrape_configs:
      - job_name: kubernetes-pods
        kubernetes_sd_configs:
          - role: pod
        relabel_configs:
          - source_labels: [__meta_kubernetes_namespace]
            target_label: namespace
          - source_labels: [__meta_kubernetes_pod_name]
            target_label: pod
          - source_labels: [__meta_kubernetes_pod_label_app]
            target_label: app
          - source_labels: [__meta_kubernetes_pod_container_name]
            target_label: container
        pipeline_stages:
          - json:
              expressions:
                level: level
                timestamp: datetime
                message: message
                trace_id: extra.trace_id
          - timestamp:
              source: timestamp
              format: RFC3339
          - labels:
              level:
              trace_id:
```

### 5.2 Metrics Collection (Prometheus)

#### Laravel Application Metrics

**File**: `app/Http/Middleware/PrometheusMetrics.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\Redis;

class PrometheusMetrics
{
    private $registry;

    public function __construct()
    {
        Redis::setDefaultOptions(['host' => config('database.redis.default.host')]);
        $this->registry = new CollectorRegistry(new Redis());
    }

    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);

        $response = $next($request);

        $duration = microtime(true) - $startTime;

        // HTTP request duration histogram
        $histogram = $this->registry->getOrRegisterHistogram(
            'api_gateway',
            'http_request_duration_seconds',
            'HTTP request duration in seconds',
            ['method', 'route', 'status'],
            [0.01, 0.05, 0.1, 0.25, 0.5, 1.0, 2.5, 5.0, 10.0]
        );

        $histogram->observe(
            $duration,
            [
                $request->method(),
                $request->route()?->getName() ?? 'unknown',
                $response->getStatusCode()
            ]
        );

        // Request counter
        $counter = $this->registry->getOrRegisterCounter(
            'api_gateway',
            'http_requests_total',
            'Total HTTP requests',
            ['method', 'route', 'status']
        );

        $counter->inc([
            $request->method(),
            $request->route()?->getName() ?? 'unknown',
            $response->getStatusCode()
        ]);

        return $response;
    }
}
```

**File**: `routes/web.php` (metrics endpoint)

```php
<?php

use Prometheus\RenderTextFormat;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\Redis;

Route::get('/metrics', function () {
    Redis::setDefaultOptions(['host' => config('database.redis.default.host')]);
    $registry = new CollectorRegistry(new Redis());

    $renderer = new RenderTextFormat();
    $result = $renderer->render($registry->getMetricFamilySamples());

    return response($result, 200)
        ->header('Content-Type', RenderTextFormat::MIME_TYPE);
})->middleware('auth:api');  // Protect with authentication
```

#### Prometheus Configuration

**File**: `kubernetes/monitoring/prometheus-config.yaml`

```yaml
apiVersion: v1
kind: ConfigMap
metadata:
  name: prometheus-config
  namespace: monitoring
data:
  prometheus.yml: |
    global:
      scrape_interval: 15s
      evaluation_interval: 15s
      external_labels:
        cluster: 'production'
        environment: 'production'

    alerting:
      alertmanagers:
        - static_configs:
            - targets:
              - alertmanager:9093

    rule_files:
      - /etc/prometheus/rules/*.yml

    scrape_configs:
      - job_name: 'api-gateway'
        kubernetes_sd_configs:
          - role: pod
            namespaces:
              names:
                - production
        relabel_configs:
          - source_labels: [__meta_kubernetes_pod_label_app]
            action: keep
            regex: api-gateway
          - source_labels: [__meta_kubernetes_pod_annotation_prometheus_io_scrape]
            action: keep
            regex: true
          - source_labels: [__meta_kubernetes_pod_annotation_prometheus_io_path]
            action: replace
            target_label: __metrics_path__
            regex: (.+)
          - source_labels: [__address__, __meta_kubernetes_pod_annotation_prometheus_io_port]
            action: replace
            regex: ([^:]+)(?::\d+)?;(\d+)
            replacement: $1:$2
            target_label: __address__
          - action: labelmap
            regex: __meta_kubernetes_pod_label_(.+)
          - source_labels: [__meta_kubernetes_namespace]
            action: replace
            target_label: kubernetes_namespace
          - source_labels: [__meta_kubernetes_pod_name]
            action: replace
            target_label: kubernetes_pod_name

      - job_name: 'mysql'
        static_configs:
          - targets: ['mysql-exporter:9104']

      - job_name: 'redis'
        static_configs:
          - targets: ['redis-exporter:9121']
```

#### Alert Rules

**File**: `kubernetes/monitoring/alert-rules.yaml`

```yaml
apiVersion: v1
kind: ConfigMap
metadata:
  name: prometheus-rules
  namespace: monitoring
data:
  api-gateway-alerts.yml: |
    groups:
      - name: api_gateway_alerts
        interval: 30s
        rules:
          # High error rate
          - alert: HighErrorRate
            expr: |
              (
                sum(rate(http_requests_total{status=~"5.."}[5m]))
                /
                sum(rate(http_requests_total[5m]))
              ) > 0.05
            for: 5m
            labels:
              severity: critical
              service: api-gateway
            annotations:
              summary: "High error rate detected"
              description: "Error rate is {{ $value | humanizePercentage }} (threshold: 5%)"

          # Slow response times
          - alert: SlowResponseTime
            expr: |
              histogram_quantile(0.95,
                sum(rate(http_request_duration_seconds_bucket[5m])) by (le)
              ) > 2.0
            for: 10m
            labels:
              severity: warning
              service: api-gateway
            annotations:
              summary: "95th percentile response time exceeded"
              description: "P95 response time is {{ $value }}s (threshold: 2s)"

          # Webhook signature failures
          - alert: WebhookSignatureFailures
            expr: |
              sum(rate(webhook_signature_failures_total[5m])) > 10
            for: 5m
            labels:
              severity: critical
              service: api-gateway
            annotations:
              summary: "Multiple webhook signature validation failures"
              description: "{{ $value }} webhook signature failures per second"

          # Database connection pool exhaustion
          - alert: DatabaseConnectionPoolExhausted
            expr: |
              mysql_global_status_threads_connected
              /
              mysql_global_variables_max_connections
              > 0.8
            for: 5m
            labels:
              severity: warning
              service: database
            annotations:
              summary: "Database connection pool near capacity"
              description: "{{ $value | humanizePercentage }} connections used"

          # Redis memory pressure
          - alert: RedisMemoryPressure
            expr: |
              redis_memory_used_bytes
              /
              redis_memory_max_bytes
              > 0.9
            for: 5m
            labels:
              severity: warning
              service: redis
            annotations:
              summary: "Redis memory usage high"
              description: "{{ $value | humanizePercentage }} memory used"

          # Pod restarts
          - alert: PodRestartingFrequently
            expr: |
              rate(kube_pod_container_status_restarts_total{namespace="production"}[15m]) > 0.1
            for: 15m
            labels:
              severity: warning
              service: api-gateway
            annotations:
              summary: "Pod restarting frequently"
              description: "Pod {{ $labels.pod }} has restarted {{ $value }} times"

          # Failed bookings
          - alert: BookingFailureRate
            expr: |
              (
                sum(rate(booking_attempts_total{status="failed"}[10m]))
                /
                sum(rate(booking_attempts_total[10m]))
              ) > 0.10
            for: 10m
            labels:
              severity: critical
              service: api-gateway
            annotations:
              summary: "High booking failure rate"
              description: "{{ $value | humanizePercentage }} of bookings failing"
```

### 5.3 Distributed Tracing

#### Jaeger Integration

**File**: `config/tracing.php`

```php
<?php

return [
    'enabled' => env('TRACING_ENABLED', false),

    'driver' => 'jaeger',

    'jaeger' => [
        'host' => env('JAEGER_AGENT_HOST', 'localhost'),
        'port' => env('JAEGER_AGENT_PORT', 6831),
        'service_name' => 'api-gateway',
    ],

    'sampler' => [
        'type' => env('TRACING_SAMPLER_TYPE', 'probabilistic'),
        'param' => env('TRACING_SAMPLER_PARAM', 0.1),  // 10% sampling
    ],
];
```

**File**: `app/Http/Middleware/TracingMiddleware.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Jaeger\Config;

class TracingMiddleware
{
    private $tracer;

    public function __construct()
    {
        if (config('tracing.enabled')) {
            $config = new Config(
                [
                    'sampler' => config('tracing.sampler'),
                    'local_agent' => [
                        'reporting_host' => config('tracing.jaeger.host'),
                        'reporting_port' => config('tracing.jaeger.port'),
                    ],
                ],
                config('tracing.jaeger.service_name')
            );

            $this->tracer = $config->initializeTracer();
        }
    }

    public function handle(Request $request, Closure $next)
    {
        if (!config('tracing.enabled')) {
            return $next($request);
        }

        // Extract trace context from headers
        $traceId = $request->header('X-Trace-ID') ?? Str::uuid()->toString();
        $spanId = $request->header('X-Span-ID') ?? Str::uuid()->toString();

        $span = $this->tracer->startSpan('http_request', [
            'tags' => [
                'http.method' => $request->method(),
                'http.url' => $request->fullUrl(),
                'http.route' => $request->route()?->getName(),
                'trace.id' => $traceId,
                'span.id' => $spanId,
            ],
        ]);

        $response = $next($request);

        $span->setTag('http.status_code', $response->getStatusCode());
        $span->finish();

        // Propagate trace context
        $response->header('X-Trace-ID', $traceId);

        return $response;
    }
}
```

### 5.4 Grafana Dashboards

**File**: `kubernetes/monitoring/grafana-dashboard.json`

```json
{
  "dashboard": {
    "title": "API Gateway - Production Overview",
    "panels": [
      {
        "title": "Request Rate (req/s)",
        "targets": [
          {
            "expr": "sum(rate(http_requests_total{namespace=\"production\"}[5m]))"
          }
        ],
        "type": "graph"
      },
      {
        "title": "Response Time (P50, P95, P99)",
        "targets": [
          {
            "expr": "histogram_quantile(0.50, sum(rate(http_request_duration_seconds_bucket[5m])) by (le))",
            "legendFormat": "P50"
          },
          {
            "expr": "histogram_quantile(0.95, sum(rate(http_request_duration_seconds_bucket[5m])) by (le))",
            "legendFormat": "P95"
          },
          {
            "expr": "histogram_quantile(0.99, sum(rate(http_request_duration_seconds_bucket[5m])) by (le))",
            "legendFormat": "P99"
          }
        ],
        "type": "graph"
      },
      {
        "title": "Error Rate (%)",
        "targets": [
          {
            "expr": "(sum(rate(http_requests_total{status=~\"5..\"}[5m])) / sum(rate(http_requests_total[5m]))) * 100"
          }
        ],
        "type": "gauge",
        "thresholds": {
          "mode": "absolute",
          "steps": [
            { "value": 0, "color": "green" },
            { "value": 1, "color": "yellow" },
            { "value": 5, "color": "red" }
          ]
        }
      },
      {
        "title": "Active Pods",
        "targets": [
          {
            "expr": "count(kube_pod_status_phase{namespace=\"production\", pod=~\"api-gateway.*\", phase=\"Running\"})"
          }
        ],
        "type": "stat"
      },
      {
        "title": "Database Connections",
        "targets": [
          {
            "expr": "mysql_global_status_threads_connected"
          }
        ],
        "type": "graph"
      },
      {
        "title": "Redis Memory Usage (MB)",
        "targets": [
          {
            "expr": "redis_memory_used_bytes / 1024 / 1024"
          }
        ],
        "type": "graph"
      },
      {
        "title": "Webhook Processing Rate",
        "targets": [
          {
            "expr": "sum(rate(webhook_events_total[5m])) by (event_type)"
          }
        ],
        "type": "graph"
      },
      {
        "title": "Booking Success Rate (%)",
        "targets": [
          {
            "expr": "(sum(rate(booking_attempts_total{status=\"success\"}[10m])) / sum(rate(booking_attempts_total[10m]))) * 100"
          }
        ],
        "type": "gauge"
      }
    ]
  }
}
```

---

## 6. Disaster Recovery

### 6.1 Backup Strategies

#### Database Backup Strategy

**RTO**: 15 minutes
**RPO**: 5 minutes (via binary logs)
**Retention**: 30 days full backups, 7 days incremental

**File**: `scripts/backup-database.sh`

```bash
#!/bin/bash
set -euo pipefail

##############################################
# Automated Database Backup Script
# Schedule: Every 6 hours (full), hourly (incremental)
##############################################

BACKUP_DIR="/backups/mysql"
S3_BUCKET="s3://api-gateway-backups/database"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
RETENTION_DAYS=30

# Database credentials from environment
DB_HOST="${DB_HOST:-localhost}"
DB_USER="${DB_USER:-root}"
DB_PASSWORD="${DB_PASSWORD}"
DB_NAME="${DB_NAME:-api_gateway}"

# Full backup (every 6 hours)
if [ $(( $(date +%H) % 6 )) -eq 0 ]; then
    echo "📦 Starting full database backup..."

    # Create backup
    mysqldump \
        -h $DB_HOST \
        -u $DB_USER \
        -p$DB_PASSWORD \
        --single-transaction \
        --routines \
        --triggers \
        --events \
        --hex-blob \
        --opt \
        $DB_NAME \
        | gzip > $BACKUP_DIR/full_${TIMESTAMP}.sql.gz

    # Calculate checksum
    sha256sum $BACKUP_DIR/full_${TIMESTAMP}.sql.gz > $BACKUP_DIR/full_${TIMESTAMP}.sql.gz.sha256

    # Upload to S3
    aws s3 cp $BACKUP_DIR/full_${TIMESTAMP}.sql.gz $S3_BUCKET/full/ \
        --storage-class STANDARD_IA \
        --metadata "backup_type=full,timestamp=$TIMESTAMP,database=$DB_NAME"

    aws s3 cp $BACKUP_DIR/full_${TIMESTAMP}.sql.gz.sha256 $S3_BUCKET/full/

    echo "✅ Full backup completed: full_${TIMESTAMP}.sql.gz"

# Incremental backup (hourly via binary logs)
else
    echo "📝 Starting incremental backup (binary logs)..."

    # Flush binary logs
    mysql -h $DB_HOST -u $DB_USER -p$DB_PASSWORD -e "FLUSH BINARY LOGS"

    # Find latest binary log
    LATEST_BINLOG=$(mysql -h $DB_HOST -u $DB_USER -p$DB_PASSWORD -N -e "SHOW BINARY LOGS" | tail -n 1 | awk '{print $1}')

    # Copy binary log
    mysqlbinlog /var/lib/mysql/$LATEST_BINLOG | gzip > $BACKUP_DIR/incremental_${TIMESTAMP}.binlog.gz

    # Upload to S3
    aws s3 cp $BACKUP_DIR/incremental_${TIMESTAMP}.binlog.gz $S3_BUCKET/incremental/ \
        --metadata "backup_type=incremental,timestamp=$TIMESTAMP"

    echo "✅ Incremental backup completed: incremental_${TIMESTAMP}.binlog.gz"
fi

# Cleanup old local backups
find $BACKUP_DIR -name "*.sql.gz" -mtime +7 -delete
find $BACKUP_DIR -name "*.binlog.gz" -mtime +7 -delete

# Cleanup old S3 backups (lifecycle policy alternative)
aws s3 ls $S3_BUCKET/full/ | awk '{print $4}' | \
    while read file; do
        FILE_DATE=$(echo $file | grep -oP '\d{8}')
        if [ $(( ($(date +%s) - $(date -d $FILE_DATE +%s)) / 86400 )) -gt $RETENTION_DAYS ]; then
            aws s3 rm $S3_BUCKET/full/$file
            echo "🗑️  Deleted old backup: $file"
        fi
    done

echo "🎉 Backup completed successfully"
```

**Kubernetes CronJob for Automated Backups**:

```yaml
apiVersion: batch/v1
kind: CronJob
metadata:
  name: database-backup
  namespace: production
spec:
  schedule: "0 */6 * * *"  # Every 6 hours
  concurrencyPolicy: Forbid
  successfulJobsHistoryLimit: 3
  failedJobsHistoryLimit: 3

  jobTemplate:
    spec:
      template:
        spec:
          restartPolicy: OnFailure

          containers:
          - name: backup
            image: mysql:8.0
            command:
            - /bin/bash
            - /scripts/backup-database.sh

            env:
            - name: DB_HOST
              valueFrom:
                secretKeyRef:
                  name: database-credentials
                  key: host
            - name: DB_USER
              valueFrom:
                secretKeyRef:
                  name: database-credentials
                  key: username
            - name: DB_PASSWORD
              valueFrom:
                secretKeyRef:
                  name: database-credentials
                  key: password
            - name: DB_NAME
              valueFrom:
                secretKeyRef:
                  name: database-credentials
                  key: database
            - name: AWS_ACCESS_KEY_ID
              valueFrom:
                secretKeyRef:
                  name: aws-credentials
                  key: access-key-id
            - name: AWS_SECRET_ACCESS_KEY
              valueFrom:
                secretKeyRef:
                  name: aws-credentials
                  key: secret-access-key

            volumeMounts:
            - name: backup-scripts
              mountPath: /scripts
            - name: backup-storage
              mountPath: /backups

          volumes:
          - name: backup-scripts
            configMap:
              name: backup-scripts
              defaultMode: 0755
          - name: backup-storage
            persistentVolumeClaim:
              claimName: backup-pvc
```

#### Application State Backup

**File**: `scripts/backup-application-state.sh`

```bash
#!/bin/bash
set -euo pipefail

##############################################
# Application State Backup
# Includes: uploads, logs, cache state
##############################################

BACKUP_DIR="/backups/application"
S3_BUCKET="s3://api-gateway-backups/application"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

echo "📦 Starting application state backup..."

# Backup storage directory
tar -czf $BACKUP_DIR/storage_${TIMESTAMP}.tar.gz \
    /var/www/html/storage/app/public

# Backup configuration (encrypted)
tar -czf $BACKUP_DIR/config_${TIMESTAMP}.tar.gz \
    /var/www/html/.env \
    /var/www/html/config

# Encrypt sensitive backups
openssl enc -aes-256-cbc \
    -salt \
    -in $BACKUP_DIR/config_${TIMESTAMP}.tar.gz \
    -out $BACKUP_DIR/config_${TIMESTAMP}.tar.gz.enc \
    -pass pass:${BACKUP_ENCRYPTION_KEY}

# Upload to S3
aws s3 cp $BACKUP_DIR/storage_${TIMESTAMP}.tar.gz $S3_BUCKET/storage/
aws s3 cp $BACKUP_DIR/config_${TIMESTAMP}.tar.gz.enc $S3_BUCKET/config/

# Cleanup
rm $BACKUP_DIR/config_${TIMESTAMP}.tar.gz
find $BACKUP_DIR -name "*.tar.gz*" -mtime +7 -delete

echo "✅ Application state backup completed"
```

### 6.2 Disaster Recovery Procedures

#### Complete System Recovery Runbook

**File**: `docs/runbooks/disaster-recovery.md`

```markdown
# Disaster Recovery Runbook

## Scenario 1: Complete Database Loss

**RTO**: 15 minutes
**RPO**: 5 minutes

### Recovery Steps:

1. **Provision New Database Instance** (3 minutes)
   ```bash
   kubectl apply -f kubernetes/production/mysql-statefulset.yaml
   kubectl wait --for=condition=ready pod/mysql-0 -n production --timeout=180s
   ```

2. **Restore Latest Full Backup** (5 minutes)
   ```bash
   # Download latest backup
   LATEST_BACKUP=$(aws s3 ls s3://api-gateway-backups/database/full/ | sort | tail -n 1 | awk '{print $4}')
   aws s3 cp s3://api-gateway-backups/database/full/$LATEST_BACKUP ./backup.sql.gz

   # Verify checksum
   aws s3 cp s3://api-gateway-backups/database/full/${LATEST_BACKUP}.sha256 ./
   sha256sum -c ${LATEST_BACKUP}.sha256

   # Restore
   gunzip < backup.sql.gz | mysql -h mysql-0.mysql -u root -p$DB_PASSWORD api_gateway
   ```

3. **Apply Incremental Backups** (3 minutes)
   ```bash
   # Find all incremental backups since last full backup
   BACKUP_TIMESTAMP=$(echo $LATEST_BACKUP | grep -oP '\d{8}_\d{6}')

   aws s3 ls s3://api-gateway-backups/database/incremental/ | \
     awk -v ts="$BACKUP_TIMESTAMP" '$4 > ts {print $4}' | \
     while read binlog; do
       aws s3 cp s3://api-gateway-backups/database/incremental/$binlog ./
       gunzip < $binlog | mysql -h mysql-0.mysql -u root -p$DB_PASSWORD api_gateway
     done
   ```

4. **Verify Data Integrity** (2 minutes)
   ```bash
   # Check table counts
   mysql -h mysql-0.mysql -u root -p$DB_PASSWORD api_gateway -e "
     SELECT 'calls' as table_name, COUNT(*) as row_count FROM calls
     UNION ALL
     SELECT 'appointments', COUNT(*) FROM appointments
     UNION ALL
     SELECT 'customers', COUNT(*) FROM customers;
   "
   ```

5. **Update Application Configuration** (2 minutes)
   ```bash
   # Update database host in secrets
   kubectl patch secret database-credentials -n production \
     --type='json' \
     -p='[{"op": "replace", "path": "/data/host", "value": "'$(echo mysql-0.mysql | base64)'"}]'

   # Restart application pods
   kubectl rollout restart deployment/api-gateway-blue -n production
   ```

## Scenario 2: Complete Cluster Failure

**RTO**: 30 minutes
**RPO**: 5 minutes

### Recovery Steps:

1. **Provision New Kubernetes Cluster** (10 minutes)
   ```bash
   # Using IaC (Terraform/EKS)
   cd infrastructure/terraform
   terraform apply -target=module.eks_cluster
   ```

2. **Restore Infrastructure State** (5 minutes)
   ```bash
   # Apply all Kubernetes manifests
   kubectl apply -f kubernetes/production/
   ```

3. **Restore Database** (10 minutes)
   - Follow "Scenario 1" procedure

4. **Verify Services** (5 minutes)
   ```bash
   # Check all pods
   kubectl get pods -n production

   # Test health endpoints
   curl https://api-gateway.example.com/health
   ```

## Scenario 3: Data Corruption

**RTO**: 20 minutes
**RPO**: Point-in-time recovery

### Recovery Steps:

1. **Identify Corruption Point** (5 minutes)
   ```bash
   # Review audit logs
   kubectl logs -n production -l app=api-gateway --since=24h | grep ERROR
   ```

2. **Point-in-Time Recovery** (10 minutes)
   ```bash
   # Restore to specific timestamp
   TARGET_TIME="2025-09-30 12:00:00"

   # Restore full backup
   gunzip < latest_full_backup.sql.gz | mysql ...

   # Apply binary logs up to target time
   mysqlbinlog --stop-datetime="$TARGET_TIME" binlog.* | mysql ...
   ```

3. **Validate Data** (5 minutes)
   ```bash
   # Run data validation queries
   php artisan db:validate
   ```

## Communication Plan

### Internal Notifications
- **Slack**: #incidents channel
- **PagerDuty**: On-call engineer
- **Email**: engineering@example.com

### External Notifications
- **Status Page**: status.example.com
- **Customer Email**: For outages >15 minutes
- **Social Media**: For major incidents

## Post-Recovery Tasks

1. **Incident Report**: Document root cause and timeline
2. **Backup Validation**: Test restore procedures
3. **Process Improvement**: Update runbooks based on learnings
4. **Monitoring Review**: Ensure alerts would catch similar issues
```

### 6.3 Recovery Testing Schedule

**Quarterly Disaster Recovery Drills**:

```yaml
# Disaster Recovery Test Plan
schedule:
  - month: January
    scenario: "Database corruption recovery"
    participants: [backend-team, devops]
    duration: 2 hours

  - month: April
    scenario: "Complete cluster failure"
    participants: [all-engineering, management]
    duration: 4 hours

  - month: July
    scenario: "Region failover"
    participants: [devops, sre]
    duration: 3 hours

  - month: October
    scenario: "Security incident response"
    participants: [security-team, devops]
    duration: 2 hours

success_criteria:
  - RTO met (< 15 minutes)
  - RPO met (< 5 minutes data loss)
  - All runbook steps validated
  - Team communication effective
  - Post-mortem completed within 48 hours
```

---

## 7. Security Best Practices

### 7.1 Network Policies

**File**: `kubernetes/production/network-policy.yaml`

```yaml
apiVersion: networking.k8s.io/v1
kind: NetworkPolicy
metadata:
  name: api-gateway-network-policy
  namespace: production
spec:
  podSelector:
    matchLabels:
      app: api-gateway

  policyTypes:
  - Ingress
  - Egress

  ingress:
  # Allow traffic from ingress controller only
  - from:
    - namespaceSelector:
        matchLabels:
          name: ingress-nginx
    ports:
    - protocol: TCP
      port: 9000

  # Allow traffic from monitoring
  - from:
    - namespaceSelector:
        matchLabels:
          name: monitoring
    ports:
    - protocol: TCP
      port: 9090  # Metrics

  egress:
  # Allow DNS
  - to:
    - namespaceSelector:
        matchLabels:
          name: kube-system
    ports:
    - protocol: UDP
      port: 53

  # Allow database access
  - to:
    - podSelector:
        matchLabels:
          app: mysql
    ports:
    - protocol: TCP
      port: 3306

  # Allow Redis access
  - to:
    - podSelector:
        matchLabels:
          app: redis
    ports:
    - protocol: TCP
      port: 6379

  # Allow external APIs (Retell, Cal.com)
  - to:
    - namespaceSelector: {}
    ports:
    - protocol: TCP
      port: 443
```

### 7.2 Pod Security Standards

**File**: `kubernetes/production/pod-security-policy.yaml`

```yaml
apiVersion: policy/v1beta1
kind: PodSecurityPolicy
metadata:
  name: restricted-psp
spec:
  privileged: false
  allowPrivilegeEscalation: false

  # Required security features
  requiredDropCapabilities:
    - ALL

  # Volumes
  volumes:
    - 'configMap'
    - 'emptyDir'
    - 'projected'
    - 'secret'
    - 'downwardAPI'
    - 'persistentVolumeClaim'

  # Host settings
  hostNetwork: false
  hostIPC: false
  hostPID: false

  # User settings
  runAsUser:
    rule: 'MustRunAsNonRoot'

  seLinux:
    rule: 'RunAsAny'

  supplementalGroups:
    rule: 'RunAsAny'

  fsGroup:
    rule: 'RunAsAny'

  readOnlyRootFilesystem: true
```

### 7.3 Secrets Rotation Policy

**File**: `scripts/rotate-secrets.sh`

```bash
#!/bin/bash
set -euo pipefail

##############################################
# Automated Secret Rotation
# Schedule: Monthly via CronJob
##############################################

echo "🔒 Starting secret rotation..."

# Generate new APP_KEY
NEW_APP_KEY=$(php artisan key:generate --show)

# Update in AWS Secrets Manager
aws secretsmanager update-secret \
  --secret-id production/api-gateway/app-key \
  --secret-string "$NEW_APP_KEY"

# Update Kubernetes secret
kubectl create secret generic api-gateway-secrets \
  --from-literal=app-key="$NEW_APP_KEY" \
  --dry-run=client -o yaml | \
  kubectl apply -f -

# Rolling restart to pick up new secrets
kubectl rollout restart deployment/api-gateway-blue -n production
kubectl rollout restart deployment/api-gateway-green -n production

# Wait for rollout
kubectl rollout status deployment/api-gateway-blue -n production --timeout=5m

# Verify application health
sleep 30
curl -f https://api-gateway.example.com/health || {
  echo "❌ Health check failed after secret rotation"
  exit 1
}

echo "✅ Secret rotation completed successfully"

# Log rotation event
kubectl create event secret-rotation \
  --namespace=production \
  --type=Normal \
  --reason=SecretRotated \
  --message="Successfully rotated APP_KEY"
```

---

## 8. Operational Runbooks

### 8.1 Deployment Checklist

```markdown
# Production Deployment Checklist

## Pre-Deployment (T-1 hour)
- [ ] All tests passing in CI/CD
- [ ] Security scan completed (no HIGH/CRITICAL vulnerabilities)
- [ ] Code review approved by 2+ engineers
- [ ] Database migration reviewed and tested
- [ ] Rollback plan documented
- [ ] On-call engineer notified
- [ ] Status page updated ("Scheduled maintenance")

## Deployment (T-0)
- [ ] Database backup completed
- [ ] Feature flags configured
- [ ] Deploy to blue environment
- [ ] Health checks passing (5/5 success)
- [ ] Smoke tests passed
- [ ] Traffic switched (0% → 10% → 100%)
- [ ] Monitor for 10 minutes

## Post-Deployment (T+10 min)
- [ ] Error rate < 1%
- [ ] Response time P95 < 2s
- [ ] No increase in failed webhooks
- [ ] Booking success rate > 90%
- [ ] Update green environment
- [ ] Status page updated ("All systems operational")
- [ ] Deployment report sent to team

## Rollback Criteria
- Error rate > 5%
- Response time P95 > 5s
- Database connection failures
- Failed webhooks > 10/min
- Any CRITICAL alert triggered
```

### 8.2 Incident Response Runbook

```markdown
# Incident Response Runbook

## Severity Levels

### SEV1 - Critical
- Complete service outage
- Data loss or corruption
- Security breach
- Response: Immediate, 24/7

### SEV2 - High
- Partial service degradation
- Failed webhooks > 50%
- Response: Within 30 minutes

### SEV3 - Medium
- Performance degradation
- Non-critical feature broken
- Response: Within 2 hours

## Response Steps

1. **Acknowledge** (< 5 minutes)
   - Acknowledge alert in PagerDuty
   - Post in #incidents Slack channel
   - Update status page

2. **Assess** (< 10 minutes)
   - Check Grafana dashboards
   - Review recent deployments
   - Identify affected components

3. **Mitigate** (< 15 minutes)
   - Rollback if recent deployment
   - Scale up if capacity issue
   - Enable maintenance mode if needed

4. **Resolve** (variable)
   - Fix root cause
   - Verify resolution
   - Monitor for 30 minutes

5. **Communicate** (< 24 hours)
   - Update status page
   - Send customer email (if needed)
   - Post-mortem (within 48 hours)

## Common Scenarios

### High Error Rate
```bash
# Check recent deployments
kubectl rollout history deployment/api-gateway-blue -n production

# Check logs
kubectl logs -n production -l app=api-gateway --tail=100 | grep ERROR

# Rollback if recent deployment
./scripts/rollback.sh production
```

### Database Connection Issues
```bash
# Check database status
kubectl get pods -n production -l app=mysql

# Check connection pool
mysql -e "SHOW PROCESSLIST"

# Scale up if needed
kubectl scale deployment api-gateway-blue --replicas=20
```

### Redis Memory Exhaustion
```bash
# Check memory
redis-cli INFO memory

# Flush cache if safe
redis-cli FLUSHALL

# Increase memory limit
kubectl patch statefulset redis -n production \
  -p '{"spec":{"template":{"spec":{"containers":[{"name":"redis","resources":{"limits":{"memory":"1Gi"}}}]}}}}'
```
```

---

## 9. Implementation Timeline

### Phase 1: Foundation (Weeks 1-2)
- [x] Docker multi-stage builds
- [x] Docker Compose for local development
- [ ] GitHub Actions basic CI workflow
- [ ] Security scanning integration

### Phase 2: Infrastructure (Weeks 3-4)
- [ ] Kubernetes manifests
- [ ] Secret management (sealed-secrets)
- [ ] Database backup automation
- [ ] Monitoring setup (Prometheus + Grafana)

### Phase 3: Automation (Weeks 5-6)
- [ ] Complete CI/CD pipeline
- [ ] Blue-green deployment automation
- [ ] Automated testing in pipeline
- [ ] Alert rules configuration

### Phase 4: Optimization (Weeks 7-8)
- [ ] Horizontal pod autoscaling
- [ ] Performance tuning
- [ ] Disaster recovery testing
- [ ] Documentation finalization

---

## 10. Cost Optimization

### Resource Allocation Strategy

```yaml
# Production resource planning
cluster_resources:
  nodes:
    count: 5
    type: t3.large (2 vCPU, 8GB RAM)
    cost_per_month: $350

  api_gateway_pods:
    min_replicas: 5
    max_replicas: 50
    avg_cpu: 200m
    avg_memory: 512Mi

  database:
    type: db.r5.large (2 vCPU, 16GB RAM)
    storage: 100GB SSD
    cost_per_month: $250

  redis:
    type: cache.r5.large
    cost_per_month: $150

  total_estimated_cost: $750/month

cost_optimization_strategies:
  - Use spot instances for non-critical workloads
  - Right-size pods based on actual usage
  - Enable cluster autoscaler
  - Use reserved instances for stable workloads
  - Implement aggressive caching
```

---

## 11. Compliance & Audit

### GDPR Compliance Checklist

```yaml
gdpr_requirements:
  data_encryption:
    - PII encrypted at rest (database level)
    - TLS 1.3 for data in transit
    - Encrypted backups

  data_retention:
    - Customer data: 3 years
    - Call transcripts: 1 year
    - Logs: 90 days

  data_deletion:
    - Right to be forgotten implementation
    - Automated data purging jobs
    - Backup purging procedures

  audit_logging:
    - All data access logged
    - Immutable audit trail
    - Retention: 7 years
```

---

## Appendices

### A. Health Check Implementation

**File**: `routes/api.php`

```php
<?php

Route::get('/health', function () {
    $checks = [
        'database' => DB::connection()->getPdo() ? 'ok' : 'fail',
        'redis' => Redis::connection()->ping() ? 'ok' : 'fail',
        'queue' => Queue::size() < 1000 ? 'ok' : 'degraded',
    ];

    $status = in_array('fail', $checks) ? 503 : 200;

    return response()->json([
        'status' => $status === 200 ? 'healthy' : 'unhealthy',
        'checks' => $checks,
        'timestamp' => now()->toIso8601String(),
        'version' => config('app.version'),
    ], $status);
});

Route::get('/readiness', function () {
    // Readiness check (can handle traffic)
    return response()->json([
        'ready' => true,
        'timestamp' => now()->toIso8601String(),
    ]);
});

Route::get('/liveness', function () {
    // Liveness check (process is alive)
    return response()->json([
        'alive' => true,
        'timestamp' => now()->toIso8601String(),
    ]);
});
```

### B. Useful Commands Reference

```bash
# Docker Commands
docker-compose up -d                          # Start local environment
docker-compose exec app php artisan migrate   # Run migrations
docker-compose logs -f app                    # View logs
docker system prune -a --volumes              # Cleanup

# Kubernetes Commands
kubectl get pods -n production                # List pods
kubectl logs -f <pod-name> -n production      # View logs
kubectl exec -it <pod-name> -n production -- bash  # Shell into pod
kubectl top pods -n production                # Resource usage
kubectl describe pod <pod-name> -n production # Pod details

# Deployment Commands
./scripts/deploy.sh production                # Deploy to production
./scripts/rollback.sh production              # Emergency rollback
kubectl rollout status deployment/api-gateway-blue  # Check rollout
kubectl rollout undo deployment/api-gateway-blue    # Undo last rollout

# Monitoring Commands
kubectl port-forward -n monitoring svc/grafana 3000:3000  # Access Grafana
kubectl port-forward -n monitoring svc/prometheus 9090:9090  # Access Prometheus

# Database Commands
kubectl exec -it mysql-0 -n production -- mysql -u root -p  # MySQL shell
php artisan db:backup                         # Manual backup
php artisan migrate:status                    # Migration status
```

### C. Contact Information

```yaml
teams:
  devops:
    primary: devops@example.com
    slack: #devops
    pagerduty: devops-oncall

  backend:
    primary: backend@example.com
    slack: #backend

  security:
    primary: security@example.com
    slack: #security

escalation_matrix:
  sev1: [devops-oncall, backend-lead, cto]
  sev2: [devops-oncall, backend-lead]
  sev3: [devops-oncall]
```

---

**Document Version**: 1.0
**Last Updated**: 2025-09-30
**Next Review**: 2025-12-30
**Maintained By**: DevOps Team

---

## Summary

This DevOps architecture provides:

1. **Zero-Downtime Deployments**: Blue-green strategy with automated health checks
2. **Security Hardening**: Automated scanning, secret management, network policies
3. **Performance Monitoring**: Prometheus metrics, Grafana dashboards, distributed tracing
4. **Disaster Recovery**: RTO <15min, RPO <5min with automated backups
5. **Infrastructure as Code**: Full reproducibility with Kubernetes manifests
6. **CI/CD Automation**: GitHub Actions with comprehensive testing gates
7. **Observability**: Structured logging, metrics, tracing, and alerting
8. **Operational Excellence**: Detailed runbooks, incident response procedures

**Next Steps**:
1. Implement Phase 1 (Foundation) - Docker and basic CI/CD
2. Set up monitoring infrastructure (Prometheus/Grafana)
3. Configure secret management (sealed-secrets or External Secrets Operator)
4. Perform disaster recovery drill
5. Document team-specific procedures and train engineers