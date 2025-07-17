# Environment Variables Reference

## Overview

This document provides a comprehensive reference for all environment variables used in the Business Portal. Variables are organized by category with descriptions, examples, and default values.

## Core Application

```env
# Application Settings
APP_NAME="AskProAI Business Portal"
APP_ENV=production              # local, staging, production
APP_KEY=base64:...             # Generated with: php artisan key:generate
APP_DEBUG=false                # true in development only
APP_URL=https://api.askproai.de

# Timezone and Locale
APP_TIMEZONE=Europe/Berlin     # Server timezone
APP_LOCALE=de                  # Default locale
APP_FALLBACK_LOCALE=en        # Fallback when translation missing
APP_FAKER_LOCALE=de_DE        # For test data generation
```

## Database Configuration

```env
# Primary Database (MariaDB/MySQL)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=askproai_db
DB_USERNAME=askproai_user
DB_PASSWORD=your_secure_password
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci

# Connection Pool Settings
DB_POOL_MIN=2
DB_POOL_MAX=10
DB_POOL_IDLE_TIMEOUT=60

# Read/Write Split (Optional)
DB_READ_HOST=127.0.0.1
DB_WRITE_HOST=127.0.0.1

# Database Backups
BACKUP_DATABASE_ENABLED=true
BACKUP_DATABASE_SCHEDULE="0 2 * * *"  # 2 AM daily
BACKUP_DATABASE_RETENTION_DAYS=30
```

## Cache & Session

```env
# Cache Configuration
CACHE_DRIVER=redis             # file, redis, memcached, array
CACHE_PREFIX=askproai_cache

# Redis Configuration
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_CLIENT=phpredis          # phpredis or predis
REDIS_CLUSTER=false

# Redis Databases
REDIS_CACHE_DB=0              # Cache storage
REDIS_SESSION_DB=1            # Session storage
REDIS_QUEUE_DB=2              # Queue jobs
REDIS_HORIZON_DB=3            # Horizon metrics

# Session Configuration
SESSION_DRIVER=redis          # file, cookie, database, redis
SESSION_LIFETIME=120          # Minutes
SESSION_ENCRYPT=true
SESSION_CONNECTION=default
SESSION_TABLE=sessions
SESSION_DOMAIN=.askproai.de   # For subdomain sharing
SESSION_SECURE_COOKIE=true    # HTTPS only
SESSION_SAME_SITE=lax        # lax, strict, none

# Portal-specific Sessions
PORTAL_SESSION_LIFETIME=120
PORTAL_SESSION_TABLE=portal_sessions
PORTAL_SESSION_COOKIE=portal_session
```

## Queue & Background Jobs

```env
# Queue Configuration
QUEUE_CONNECTION=redis        # sync, database, redis, sqs
QUEUE_FAILED_DRIVER=database

# Queue Names
QUEUE_DEFAULT=default
QUEUE_HIGH=high
QUEUE_LOW=low
QUEUE_WEBHOOKS=webhooks
QUEUE_EXPORTS=exports

# Horizon Configuration
HORIZON_MASTER_SUPERVISOR_NAME=horizon
HORIZON_ENVIRONMENT=production
HORIZON_BALANCE=auto         # simple, auto, false
HORIZON_MAX_PROCESSES=10
HORIZON_TIMEOUT=60
HORIZON_RETRY_AFTER=90
HORIZON_MEMORY_LIMIT=128

# Job Timeouts
JOB_TIMEOUT_DEFAULT=60
JOB_TIMEOUT_WEBHOOKS=30
JOB_TIMEOUT_EXPORTS=300
JOB_TIMEOUT_IMPORTS=600
```

## Business Portal Settings

```env
# Portal Configuration
PORTAL_DOMAIN=portal.askproai.de
PORTAL_API_VERSION=v2
PORTAL_SESSION_PREFIX=portal_
PORTAL_COOKIE_PREFIX=portal_

# Portal Features
PORTAL_2FA_ENABLED=true
PORTAL_2FA_REQUIRED_FOR_ADMINS=true
PORTAL_AUDIT_ENABLED=true
PORTAL_AUDIT_RETENTION_DAYS=365

# Portal Security
PORTAL_MAX_LOGIN_ATTEMPTS=5
PORTAL_LOGIN_DECAY_MINUTES=15
PORTAL_PASSWORD_MIN_LENGTH=8
PORTAL_PASSWORD_REQUIRE_UPPERCASE=true
PORTAL_PASSWORD_REQUIRE_NUMBERS=true
PORTAL_PASSWORD_REQUIRE_SYMBOLS=true

# Portal API Rate Limits
PORTAL_RATE_LIMIT_PER_MINUTE=60
PORTAL_RATE_LIMIT_AUTH_PER_MINUTE=5
PORTAL_RATE_LIMIT_EXPORT_PER_HOUR=10
```

## External Service Integrations

### Retell.ai Configuration

```env
# Retell.ai API
RETELL_API_KEY=key_e973c8962e09d6a34b3b1cf386
RETELL_WEBHOOK_SECRET=Hqj8iGCaWxGXdoKCqQQFaHsUjFKHFjUO
RETELL_BASE_URL=https://api.retellai.com
RETELL_API_VERSION=v2

# Default Retell Settings
DEFAULT_RETELL_API_KEY=${RETELL_API_KEY}
DEFAULT_RETELL_AGENT_ID=agent_1234567890

# Retell Features
RETELL_ENABLE_RECORDING=true
RETELL_ENABLE_TRANSCRIPT=true
RETELL_ENABLE_SUMMARY=true
RETELL_ENABLE_SENTIMENT=true

# Retell Webhook
RETELL_WEBHOOK_URL=https://api.askproai.de/api/retell/webhook-simple
RETELL_WEBHOOK_TIMEOUT=30
```

### Cal.com Configuration

```env
# Cal.com API
CALCOM_API_KEY=cal_live_1234567890abcdef
CALCOM_API_URL=https://api.cal.com
CALCOM_API_VERSION=v2

# Default Cal.com Settings
DEFAULT_CALCOM_API_KEY=${CALCOM_API_KEY}
DEFAULT_CALCOM_TEAM_SLUG=askproai
DEFAULT_CALCOM_EVENT_TYPE_ID=1234

# Cal.com Webhook
CALCOM_WEBHOOK_SECRET=whsec_1234567890
CALCOM_WEBHOOK_EVENTS=booking.created,booking.cancelled,booking.rescheduled

# Cal.com Sync
CALCOM_SYNC_ENABLED=true
CALCOM_SYNC_INTERVAL=300     # Seconds
CALCOM_SYNC_BATCH_SIZE=50
```

### Stripe Configuration

```env
# Stripe API
STRIPE_KEY=pk_live_1234567890
STRIPE_SECRET=sk_live_1234567890
STRIPE_WEBHOOK_SECRET=whsec_1234567890

# Stripe Settings
STRIPE_API_VERSION=2023-10-16
STRIPE_CURRENCY=EUR
STRIPE_COUNTRY=DE

# Stripe Products
STRIPE_PRODUCT_TOPUP=prod_1234567890
STRIPE_PRODUCT_SUBSCRIPTION=prod_0987654321

# Stripe Test Mode
STRIPE_TEST_MODE=false
STRIPE_TEST_CLOCK=false
```

## Email Configuration

```env
# Mail Driver
MAIL_MAILER=smtp              # smtp, sendmail, log, array
MAIL_HOST=smtp.udag.de
MAIL_PORT=587
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls           # tls, ssl, null
MAIL_FROM_ADDRESS=noreply@askproai.de
MAIL_FROM_NAME="${APP_NAME}"

# Mail Settings
MAIL_QUEUE_CONNECTION=redis
MAIL_LOG_CHANNEL=mail
MAIL_MARKDOWN_THEME=default

# Email Features
EMAIL_VERIFICATION_ENABLED=true
EMAIL_VERIFICATION_EXPIRE=60   # Minutes
EMAIL_NOTIFICATION_DELAY=0     # Seconds
```

## Logging & Monitoring

```env
# Logging
LOG_CHANNEL=stack             # single, daily, stack, syslog
LOG_STACK=daily,slack
LOG_LEVEL=info               # debug, info, warning, error, critical
LOG_DAILY_DAYS=14            # Days to keep daily logs
LOG_DEPRECATIONS_CHANNEL=null

# Detailed Logging
LOG_SQL_QUERIES=false        # Log all SQL queries
LOG_SQL_SLOW_TIME=1000      # ms - Log slow queries
LOG_API_REQUESTS=true       # Log API requests/responses
LOG_WEBHOOK_PAYLOADS=true   # Log webhook data

# Sentry Error Tracking
SENTRY_LARAVEL_DSN=https://key@sentry.io/project
SENTRY_ENVIRONMENT=${APP_ENV}
SENTRY_TRACES_SAMPLE_RATE=0.1
SENTRY_PROFILES_SAMPLE_RATE=0.1

# Monitoring
MONITORING_ENABLED=true
MONITORING_SLACK_WEBHOOK=https://hooks.slack.com/...
MONITORING_EMAIL=monitoring@askproai.de
```

## Security & Authentication

```env
# Authentication
AUTH_GUARD_WEB=web
AUTH_GUARD_API=sanctum
AUTH_MODEL=App\Models\User

# Sanctum Configuration
SANCTUM_STATEFUL_DOMAINS=portal.askproai.de,admin.askproai.de
SANCTUM_TOKEN_LIFETIME=120    # Minutes
SANCTUM_TOKEN_PREFIX=

# Password Hashing
BCRYPT_ROUNDS=10

# Encryption
ENCRYPT_API_KEYS=true
ENCRYPT_SENSITIVE_DATA=true

# CORS Configuration
CORS_ALLOWED_ORIGINS=https://portal.askproai.de,https://admin.askproai.de
CORS_ALLOWED_METHODS=GET,POST,PUT,DELETE,OPTIONS
CORS_ALLOWED_HEADERS=Content-Type,Authorization,X-Requested-With,X-CSRF-TOKEN
CORS_EXPOSED_HEADERS=X-RateLimit-Limit,X-RateLimit-Remaining
CORS_MAX_AGE=86400
CORS_SUPPORTS_CREDENTIALS=true
```

## Feature Flags

```env
# Core Features
FEATURE_MULTI_TENANT=true
FEATURE_MULTI_BRANCH=true
FEATURE_MULTI_LANGUAGE=true

# Portal Features
FEATURE_GOALS_ENABLED=true
FEATURE_JOURNEY_ENABLED=true
FEATURE_AUDIT_TRAIL=true
FEATURE_2FA=true
FEATURE_API_TOKENS=true

# Advanced Features
FEATURE_WEBSOCKET_ENABLED=false
FEATURE_MOBILE_API_ENABLED=true
FEATURE_EXPORT_ENABLED=true
FEATURE_IMPORT_ENABLED=true
FEATURE_BULK_OPERATIONS=true

# Experimental Features
FEATURE_AI_INSIGHTS=false
FEATURE_PREDICTIVE_ANALYTICS=false
FEATURE_VOICE_COMMANDS=false
```

## MCP Server Configuration

```env
# MCP Server Enable/Disable
MCP_DATABASE_ENABLED=true
MCP_RETELL_ENABLED=true
MCP_CALCOM_ENABLED=true
MCP_STRIPE_ENABLED=true
MCP_CUSTOMER_ENABLED=true
MCP_APPOINTMENT_ENABLED=true
MCP_COMPANY_ENABLED=true
MCP_BRANCH_ENABLED=true
MCP_GOAL_ENABLED=true
MCP_AUDIT_ENABLED=true
MCP_WEBHOOK_ENABLED=true
MCP_QUEUE_ENABLED=true
MCP_SENTRY_ENABLED=true
MCP_KNOWLEDGE_ENABLED=true
MCP_NOTIFICATION_ENABLED=true

# MCP Settings
MCP_TIMEOUT=30               # Seconds
MCP_MAX_RETRIES=3
MCP_DEBUG=false
MCP_LOG_QUERIES=false

# External MCP Servers
MCP_SEQUENTIAL_THINKING_ENABLED=true
MCP_TASKMASTER_ENABLED=false
MCP_EFFECT_DOCS_ENABLED=false
```

## Development & Testing

```env
# Development Tools
DEBUGBAR_ENABLED=false
TELESCOPE_ENABLED=false
CLOCKWORK_ENABLED=false

# Testing
TESTING_DATABASE=askproai_test
TESTING_REDIS_DATABASE=15
TESTING_FAKE_WEBHOOKS=true
TESTING_MOCK_EXTERNAL_APIS=true

# Demo Mode
DEMO_MODE=false
DEMO_USER_EMAIL=demo@askproai.de
DEMO_USER_PASSWORD=demo123
DEMO_RESET_INTERVAL=3600     # Seconds
```

## Performance & Optimization

```env
# Caching
CACHE_VIEWS=true
CACHE_ROUTES=true
CACHE_CONFIG=true
CACHE_EVENTS=true
CACHE_TTL_DEFAULT=3600       # Seconds
CACHE_TTL_DASHBOARD=300
CACHE_TTL_ANALYTICS=900

# Query Optimization
QUERY_CACHE_ENABLED=true
QUERY_CACHE_TTL=60
EAGER_LOAD_RELATIONS=true
CHUNK_SIZE_DEFAULT=1000

# API Response
API_RESPONSE_CACHE=true
API_RESPONSE_CACHE_TTL=60
API_PAGINATION_DEFAULT=20
API_PAGINATION_MAX=100
```

## Deployment & Infrastructure

```env
# Server Configuration
SERVER_TIMEZONE=UTC
SERVER_MEMORY_LIMIT=512M
SERVER_MAX_EXECUTION_TIME=30
SERVER_UPLOAD_MAX_SIZE=10M
SERVER_POST_MAX_SIZE=10M

# Asset Configuration
ASSET_URL=${APP_URL}
MIX_ASSET_URL=${ASSET_URL}
VITE_ASSET_URL=${ASSET_URL}

# CDN Configuration
CDN_ENABLED=false
CDN_URL=https://cdn.askproai.de
CDN_ASSETS=true
CDN_MEDIA=true

# Filesystem
FILESYSTEM_CLOUD=s3
FILESYSTEM_LOCAL_VISIBILITY=private

# AWS S3 Configuration
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=eu-central-1
AWS_BUCKET=askproai-assets
AWS_URL=
AWS_ENDPOINT=
AWS_USE_PATH_STYLE_ENDPOINT=false
```

## Compliance & Legal

```env
# GDPR Compliance
GDPR_ENABLED=true
GDPR_DATA_RETENTION_DAYS=730  # 2 years
GDPR_ANONYMIZE_AFTER_DAYS=1095 # 3 years
GDPR_EXPORT_ENABLED=true
GDPR_DELETE_ENABLED=true

# Legal Requirements
LEGAL_IMPRINT_URL=https://askproai.de/impressum
LEGAL_PRIVACY_URL=https://askproai.de/datenschutz
LEGAL_TERMS_URL=https://askproai.de/agb
LEGAL_COOKIE_CONSENT=true

# Data Residency
DATA_RESIDENCY_REGION=EU
DATA_RESIDENCY_COUNTRY=DE
```

## Backup & Disaster Recovery

```env
# Backup Configuration
BACKUP_ENABLED=true
BACKUP_DRIVER=local          # local, s3, ftp
BACKUP_SCHEDULE="0 2 * * *"  # 2 AM daily
BACKUP_RETENTION_DAYS=30

# Backup Types
BACKUP_DATABASE=true
BACKUP_FILES=true
BACKUP_LOGS=false
BACKUP_COMPRESS=true
BACKUP_ENCRYPT=true
BACKUP_ENCRYPTION_KEY=

# Backup Notifications
BACKUP_NOTIFY_EMAIL=admin@askproai.de
BACKUP_NOTIFY_SLACK=https://hooks.slack.com/...
BACKUP_NOTIFY_ON_SUCCESS=false
BACKUP_NOTIFY_ON_FAILURE=true
```

## Custom Business Logic

```env
# Business Rules
BUSINESS_DEFAULT_CURRENCY=EUR
BUSINESS_DEFAULT_TIMEZONE=Europe/Berlin
BUSINESS_DEFAULT_LANGUAGE=de
BUSINESS_WORKING_DAYS=1,2,3,4,5  # Mon-Fri
BUSINESS_WORKING_HOURS_START=08:00
BUSINESS_WORKING_HOURS_END=18:00

# Appointment Settings
APPOINTMENT_BUFFER_MINUTES=15
APPOINTMENT_CANCELLATION_HOURS=24
APPOINTMENT_REMINDER_HOURS=24,2  # 24h and 2h before
APPOINTMENT_NO_SHOW_MINUTES=15

# Billing Settings
BILLING_CURRENCY=EUR
BILLING_TAX_RATE=19          # Percent
BILLING_PAYMENT_TERMS_DAYS=14
BILLING_AUTO_CHARGE=true
BILLING_MIN_TOPUP=10
BILLING_MAX_TOPUP=10000
```

## Environment File Management

### Loading Priority

1. `.env` - Default environment file
2. `.env.{environment}` - Environment-specific (e.g., `.env.production`)
3. `.env.local` - Local overrides (not committed to git)

### Best Practices

1. **Never commit sensitive values** to version control
2. **Use `.env.example`** as a template with dummy values
3. **Validate required variables** on application start
4. **Use strong, unique passwords** for all services
5. **Rotate keys and secrets** regularly
6. **Document all custom variables** added to the project

### Environment Validation

```php
// config/app.php
$requiredEnvVars = [
    'APP_KEY',
    'DB_CONNECTION',
    'DB_DATABASE',
    'RETELL_API_KEY',
    'CALCOM_API_KEY',
];

foreach ($requiredEnvVars as $var) {
    if (empty(env($var))) {
        throw new Exception("Required environment variable {$var} is not set");
    }
}
```

### Helper Commands

```bash
# Generate new app key
php artisan key:generate

# Validate environment
php artisan config:cache --env=production

# Show current environment
php artisan env:show

# Encrypt .env file
php artisan env:encrypt

# Decrypt .env file
php artisan env:decrypt --key=your-encryption-key
```

---

*For deployment instructions, see the [Deployment Guide](./DEPLOYMENT_GUIDE.md)*