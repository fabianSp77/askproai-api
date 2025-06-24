# Environment Configuration

## Overview

This guide covers all environment variables used in AskProAI, organized by category. Environment configuration is managed through `.env` files with different configurations for each environment.

## Environment Files

### File Structure
```
.env                 # Local development
.env.example         # Template with all variables
.env.production      # Production settings (not in git)
.env.staging         # Staging settings (not in git)
.env.testing         # Test environment
```

### Loading Priority
1. System environment variables
2. `.env.{environment}` file
3. `.env` file
4. Default values in config files

## Core Configuration

### Application Settings
```bash
# Application
APP_NAME=AskProAI
APP_ENV=production              # local, staging, production
APP_KEY=base64:xxxxx           # Generate with: php artisan key:generate
APP_DEBUG=false                # Never true in production
APP_URL=https://api.askproai.de
APP_TIMEZONE=Europe/Berlin

# Localization
APP_LOCALE=de
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=de_DE

# Logging
LOG_CHANNEL=stack              # single, daily, stack, syslog
LOG_LEVEL=info                 # debug, info, warning, error, critical
LOG_DEPRECATIONS_CHANNEL=null
LOG_STACK=single,bugsnag

# Performance
DEBUGBAR_ENABLED=false
TELESCOPE_ENABLED=false
QUERY_DETECTOR_ENABLED=false
```

## Database Configuration

### Primary Database
```bash
# MySQL/MariaDB
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=askproai_db
DB_USERNAME=askproai_user
DB_PASSWORD=secure_password
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci

# Connection Pool
DB_POOL_MIN=2
DB_POOL_MAX=10
DB_CONNECTION_MAX_LIFETIME=120

# SSL Connection (Production)
DB_SSL_MODE=required
DB_SSL_CA=/path/to/ca-cert.pem
DB_SSL_CERT=/path/to/client-cert.pem
DB_SSL_KEY=/path/to/client-key.pem
```

### Read Replica (Optional)
```bash
DB_READ_HOST=127.0.0.1
DB_READ_PORT=3306
DB_READ_DATABASE=askproai_db
DB_READ_USERNAME=askproai_readonly
DB_READ_PASSWORD=secure_password
```

### Redis Cache/Queue
```bash
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1
REDIS_QUEUE_DB=2
REDIS_SESSION_DB=3

# Redis Cluster (Production)
REDIS_CLUSTER=true
REDIS_CLUSTERS_0=127.0.0.1:7000
REDIS_CLUSTERS_1=127.0.0.1:7001
REDIS_CLUSTERS_2=127.0.0.1:7002
```

## External Service Integration

### Retell.ai Configuration
```bash
# Retell.ai API
DEFAULT_RETELL_API_KEY=key_xxxxxxxxxxxxxx
DEFAULT_RETELL_AGENT_ID=agent_xxxxxxxxxxxxxx
RETELL_WEBHOOK_SECRET=key_xxxxxxxxxxxxxx
RETELL_BASE_URL=https://api.retellai.com
RETELL_API_VERSION=v2
RETELL_TIMEOUT=30

# Retell Development/Testing
RETELL_SANDBOX_MODE=false
RETELL_SANDBOX_API_KEY=sandbox_key_xxxxx
RETELL_MOCK_ENABLED=false
```

### Cal.com Configuration
```bash
# Cal.com API
DEFAULT_CALCOM_API_KEY=cal_live_xxxxxxxxxxxxxx
DEFAULT_CALCOM_TEAM_SLUG=askproai
CALCOM_API_BASE_URL=https://api.cal.com/v2
CALCOM_WEBHOOK_SECRET=cal_webhook_secret_xxxxx
CALCOM_ORGANIZATION_ID=12345

# Cal.com Settings
CALCOM_DEFAULT_TIMEZONE=Europe/Berlin
CALCOM_BOOKING_LIMIT=100
CALCOM_CACHE_TTL=300
```

### Stripe Configuration
```bash
# Stripe API
STRIPE_KEY=pk_live_xxxxxxxxxxxxxx
STRIPE_SECRET=sk_live_xxxxxxxxxxxxxx
STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxxx

# Stripe Settings
CASHIER_CURRENCY=eur
CASHIER_CURRENCY_LOCALE=de_DE
STRIPE_API_VERSION=2023-10-16

# Stripe Products/Prices
STRIPE_PRICE_STARTER=price_starter_monthly
STRIPE_PRICE_PROFESSIONAL=price_pro_monthly
STRIPE_PRICE_ENTERPRISE=price_enterprise_monthly
STRIPE_PRICE_PER_CALL=price_per_call_overage
```

## Communication Services

### Email Configuration
```bash
# Mail Driver
MAIL_MAILER=smtp               # smtp, mailgun, postmark, ses
MAIL_HOST=smtp.udag.de
MAIL_PORT=587
MAIL_USERNAME=askproai@udag.de
MAIL_PASSWORD=secure_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@askproai.de
MAIL_FROM_NAME="${APP_NAME}"

# Alternative Mail Services
MAILGUN_DOMAIN=mg.askproai.de
MAILGUN_SECRET=key-xxxxxx
MAILGUN_ENDPOINT=api.eu.mailgun.net

POSTMARK_TOKEN=xxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx

AWS_SES_KEY=AKIAXXXXXXXXXXXXXX
AWS_SES_SECRET=xxxxxxxxxxxxxx
AWS_SES_REGION=eu-central-1
```

### SMS Configuration
```bash
# Primary SMS Provider
SMS_PROVIDER=twilio            # twilio, messagebird, vonage
SMS_FALLBACK_PROVIDER=messagebird

***REMOVED***
TWILIO_SID=ACxxxxxxxxxxxxxx
TWILIO_TOKEN=xxxxxxxxxxxxxx
TWILIO_FROM=+49301234567
TWILIO_MESSAGING_SERVICE_SID=MGxxxxxxxxxxxxxx

# MessageBird
MESSAGEBIRD_ACCESS_KEY=xxxxxxxxxxxxxx
MESSAGEBIRD_ORIGINATOR=AskProAI

# Vonage
VONAGE_KEY=xxxxxxxxxxxxxx
VONAGE_SECRET=xxxxxxxxxxxxxx
VONAGE_SMS_FROM=AskProAI
```

### WhatsApp (Planned)
```bash
WHATSAPP_API_URL=https://graph.facebook.com/v17.0
WHATSAPP_PHONE_NUMBER_ID=1234567890
WHATSAPP_BUSINESS_ACCOUNT_ID=987654321
WHATSAPP_ACCESS_TOKEN=EAAxxxxxxxxxxxxxx
WHATSAPP_WEBHOOK_VERIFY_TOKEN=verify_token_xxxxx
```

## Security & Authentication

### Authentication
```bash
# Session
SESSION_DRIVER=redis           # file, cookie, database, redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true     # true in production
SESSION_SAME_SITE=lax

# API Authentication
SANCTUM_STATEFUL_DOMAINS=app.askproai.de
API_RATE_LIMIT=60
API_RATE_LIMIT_PREMIUM=300

# MFA Settings
MFA_ENABLED=true
MFA_ISSUER="${APP_NAME}"
```

### Security Headers
```bash
# CORS
CORS_ALLOWED_ORIGINS=https://app.askproai.de,https://admin.askproai.de
CORS_ALLOWED_METHODS=GET,POST,PUT,DELETE,OPTIONS
CORS_ALLOWED_HEADERS=Content-Type,Authorization,X-Requested-With
CORS_MAX_AGE=86400

# Security
SECURE_HEADERS_ENABLED=true
FORCE_HTTPS=true
HSTS_MAX_AGE=31536000
HSTS_INCLUDE_SUBDOMAINS=true
```

### Encryption & Hashing
```bash
# Encryption
ENCRYPT_SENSITIVE_DATA=true
ENCRYPTION_CIPHER=AES-256-CBC

# Hashing
BCRYPT_ROUNDS=12
ARGON_MEMORY=65536
ARGON_THREADS=1
ARGON_TIME=4
```

## Queue & Cache Configuration

### Queue Settings
```bash
# Queue Driver
QUEUE_CONNECTION=redis         # sync, database, redis, sqs
QUEUE_RETRY_AFTER=90
QUEUE_BLOCK_FOR=5

# Queue Names
QUEUE_HIGH=high
QUEUE_DEFAULT=default
QUEUE_LOW=low
QUEUE_WEBHOOKS=webhooks
QUEUE_EMAILS=emails

# Horizon
HORIZON_DOMAIN=horizon.askproai.de
HORIZON_BASIC_AUTH_USERNAME=admin
HORIZON_BASIC_AUTH_PASSWORD=secure_password
HORIZON_MEMORY_LIMIT=128
HORIZON_TIME_LIMIT=3600
HORIZON_TRIES=3
```

### Cache Configuration
```bash
# Cache Driver
CACHE_DRIVER=redis             # file, database, redis, memcached
CACHE_PREFIX=askproai_cache

# Cache TTL (seconds)
CACHE_DEFAULT_TTL=3600
CACHE_EVENT_TYPES_TTL=300
CACHE_AVAILABILITY_TTL=60
CACHE_COMPANY_SETTINGS_TTL=1800

# Memcached (Alternative)
MEMCACHED_HOST=127.0.0.1
MEMCACHED_PORT=11211
MEMCACHED_USERNAME=null
MEMCACHED_PASSWORD=null
```

## Monitoring & Debugging

### Error Tracking
```bash
# Sentry
SENTRY_LARAVEL_DSN=https://xxxxx@xxx.ingest.sentry.io/xxxxx
SENTRY_TRACES_SAMPLE_RATE=0.1
SENTRY_PROFILES_SAMPLE_RATE=0.1
SENTRY_ENVIRONMENT="${APP_ENV}"
SENTRY_RELEASE="${APP_VERSION}"
SENTRY_SEND_DEFAULT_PII=false

# Bugsnag (Alternative)
BUGSNAG_API_KEY=xxxxxxxxxxxxxx
BUGSNAG_APP_TYPE=laravel
BUGSNAG_APP_VERSION="${APP_VERSION}"
```

### Performance Monitoring
```bash
# New Relic
NEW_RELIC_ENABLED=true
NEW_RELIC_APP_NAME="${APP_NAME}"
NEW_RELIC_LICENSE_KEY=xxxxxxxxxxxxxx

# Laravel Debugbar (Development only)
DEBUGBAR_ENABLED=false
DEBUGBAR_STORAGE_ENABLED=true
DEBUGBAR_CAPTURE_AJAX=true

# Query Monitoring
QUERY_LOG_ENABLED=false
SLOW_QUERY_LOG_ENABLED=true
SLOW_QUERY_TIME=1000          # milliseconds
```

### Logging Configuration
```bash
# Structured Logging
LOG_STRUCTURED=true
LOG_CONTEXT_PROCESSOR=true

# Log Channels
LOG_SLACK_WEBHOOK_URL=https://hooks.slack.com/services/xxxxx
LOG_PAPERTRAIL_URL=syslog://logs.papertrailapp.com:12345
LOG_PAPERTRAIL_PORT=12345

# Log Retention
LOG_RETENTION_DAYS=30
LOG_MAX_FILES=30
```

## Feature Flags

```bash
# Feature Toggles
FEATURE_CUSTOMER_PORTAL=false
FEATURE_SMS_NOTIFICATIONS=true
FEATURE_WHATSAPP_INTEGRATION=false
FEATURE_MULTI_LANGUAGE=true
FEATURE_ADVANCED_ANALYTICS=true
FEATURE_UNIFIED_SERVICES=false

# Experimental Features
EXPERIMENTAL_FEATURES=false
FEATURE_AI_INSIGHTS=false
FEATURE_VOICE_BIOMETRICS=false
```

## Development & Testing

### Development Tools
```bash
# IDE Helper
IDE_HELPER_ENABLED=true

# Faker
FAKER_LOCALE=de_DE

# Telescope
TELESCOPE_ENABLED=false
TELESCOPE_DOMAIN=telescope.askproai.local
TELESCOPE_PATH=telescope
TELESCOPE_DRIVER=database

# Clockwork
CLOCKWORK_ENABLE=false
```

### Testing Configuration
```bash
# Testing Database
DB_TEST_CONNECTION=sqlite
DB_TEST_DATABASE=:memory:

# Testing Services
MAIL_TEST_DRIVER=array
SMS_TEST_MODE=true
STRIPE_TEST_MODE=true
RETELL_MOCK_ENABLED=true
CALCOM_MOCK_ENABLED=true

# Browser Testing
DUSK_DRIVER_URL=http://localhost:9515
DUSK_BROWSER=chrome
DUSK_HEADLESS=true
```

## Deployment Configuration

### Build & Deploy
```bash
# Deployment
DEPLOY_SERVER=hosting215275.ae83d.netcup.net
DEPLOY_USER=hosting215275
DEPLOY_PATH=/var/www/api-gateway
DEPLOY_BRANCH=main

# Asset Building
MIX_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
MIX_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
ASSET_URL="${APP_URL}"

# Version Control
APP_VERSION=1.0.0
APP_COMMIT_SHA=abc123def
APP_BUILD_NUMBER=100
```

### Backup Configuration
```bash
# Backup Settings
BACKUP_ENABLED=true
BACKUP_SCHEDULE=daily
BACKUP_RETENTION_DAYS=30

# Backup Destinations
BACKUP_LOCAL_PATH=/var/backups/askproai
BACKUP_S3_BUCKET=askproai-backups
BACKUP_S3_REGION=eu-central-1
BACKUP_ENCRYPT=true
```

## Best Practices

### Security
1. **Never commit `.env` files** to version control
2. **Use strong passwords** for all services
3. **Rotate API keys** regularly
4. **Enable encryption** for sensitive data
5. **Use environment-specific** configurations

### Performance
1. **Enable caching** in production
2. **Use Redis** for queues and cache
3. **Configure connection pooling** for databases
4. **Set appropriate timeouts** for external services
5. **Monitor slow queries** and API calls

### Maintenance
1. **Document all variables** in `.env.example`
2. **Use descriptive names** for custom variables
3. **Group related variables** together
4. **Validate configuration** on deployment
5. **Keep backups** of production `.env` files

## Environment Variable Reference

### Required Variables
These must be set in all environments:
- `APP_KEY`
- `DB_CONNECTION`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `REDIS_HOST`
- `DEFAULT_RETELL_API_KEY`
- `DEFAULT_CALCOM_API_KEY`

### Optional Variables
These have sensible defaults but should be configured:
- `MAIL_*` settings
- `SMS_*` settings  
- `STRIPE_*` settings
- `SENTRY_*` settings
- Feature flags

### Environment-Specific
These vary by environment:
- `APP_ENV`, `APP_DEBUG`
- `APP_URL`
- Database credentials
- API endpoints
- Debug/monitoring settings

## Related Documentation
- [Service Configuration](services.md)
- [Security Configuration](security.md)
- [Deployment Guide](../deployment/production.md)