# AskProAI Deployment Scripts

This directory contains comprehensive deployment scripts and configuration for the AskProAI platform.

## Quick Start

### Production Deployment
```bash
# Run pre-deployment checks
./pre-deployment-checklist.sh production

# Deploy to production
./deploy.sh production

# Verify deployment
./post-deployment-verify.sh
```

### Emergency Rollback
```bash
# Rollback to previous version
./rollback-enhanced.sh --backup=latest

# Or interactive rollback
./rollback-enhanced.sh
```

## Scripts Overview

### 1. **deploy.sh** - Main Deployment Script
Zero-downtime deployment with comprehensive safety checks.

**Features:**
- Automatic backups before deployment
- Smart database migrations
- Graceful service restarts
- Health checks and verification
- Automatic rollback on failure

**Usage:**
```bash
./deploy.sh [production|staging|maintenance]
```

**Environment Variables:**
- `SLACK_WEBHOOK_URL` - Slack notifications
- `DEPLOYMENT_EMAIL` - Email notifications
- `DEPLOY_TARGET` - Git branch/tag to deploy

### 2. **rollback-enhanced.sh** - Emergency Rollback
Restore system to a previous state from backup.

**Features:**
- Interactive backup selection
- Integrity verification
- Safety backup before rollback
- Partial rollback support (DB only)

**Usage:**
```bash
# Interactive mode
./rollback-enhanced.sh

# Automatic rollback to latest
./rollback-enhanced.sh --auto --backup=latest

# Partial rollback (database only)
./rollback-enhanced.sh --partial --backup=backup_20250619_120000
```

### 3. **safe-migrate.sh** - Database Migration Safety
Run database migrations with safety checks and rollback capability.

**Features:**
- Migration analysis and impact assessment
- Automatic backup of affected tables
- Progress monitoring
- Automatic rollback on failure

**Usage:**
```bash
# Dry run to see what would happen
./safe-migrate.sh --dry-run

# Run migrations with default settings
./safe-migrate.sh

# Force migrations without confirmation
./safe-migrate.sh --force

# Custom batch size for large tables
./safe-migrate.sh --batch-size=5000
```

### 4. **pre-deployment-checklist.sh** - Pre-deployment Validation
Comprehensive checklist before deployment.

**Features:**
- 50+ automated checks
- HTML and JSON reports
- Environment validation
- External service connectivity tests

**Usage:**
```bash
# Terminal output (default)
./pre-deployment-checklist.sh production

# Generate HTML report
./pre-deployment-checklist.sh production --output=html

# Verbose mode
./pre-deployment-checklist.sh production --verbose
```

### 5. **post-deployment-verify.sh** - Post-deployment Verification
Verify system health after deployment.

**Features:**
- API endpoint testing
- Performance benchmarks
- Security validation
- End-to-end flow testing

**Usage:**
```bash
# Run all verification tests
./post-deployment-verify.sh

# Custom base URL
BASE_URL=https://staging.askproai.de ./post-deployment-verify.sh
```

## Configuration Files

### **.env.production.template**
Production environment template. Copy to `.env.production` and fill in values.

### **.env.staging.template**
Staging environment template. Copy to `.env.staging` and fill in values.

### **deploy.conf**
Deployment configuration shared by all scripts. Customize paths, timeouts, and behaviors.

## Deployment Workflow

### Standard Deployment Process

1. **Preparation**
   ```bash
   # Ensure you're on the correct branch
   git checkout main
   git pull origin main
   
   # Run pre-deployment checks
   ./pre-deployment-checklist.sh production
   ```

2. **Deploy**
   ```bash
   # Set notification webhook (optional)
   export SLACK_WEBHOOK_URL="https://hooks.slack.com/..."
   
   # Run deployment
   ./deploy.sh production
   ```

3. **Verify**
   ```bash
   # Run post-deployment verification
   ./post-deployment-verify.sh
   
   # Check application logs
   tail -f /var/log/askproai/deployment-*.log
   ```

### Database Migration Process

1. **Analyze migrations**
   ```bash
   ./safe-migrate.sh --dry-run
   ```

2. **Run migrations**
   ```bash
   ./safe-migrate.sh
   ```

### Emergency Procedures

#### Rollback After Failed Deployment
```bash
# Quick rollback to last backup
./rollback-enhanced.sh --auto --backup=latest

# Or selective rollback
./rollback-enhanced.sh
```

#### Fix Broken Deployment
```bash
# Enable maintenance mode
php artisan down --secret=emergency

# Fix issues...

# Disable maintenance mode
php artisan up
```

## Best Practices

1. **Always run pre-deployment checks** before deploying
2. **Monitor logs** during deployment: `tail -f /var/log/askproai/deployment-*.log`
3. **Test in staging** before production deployment
4. **Keep backups** for at least 30 days
5. **Document any manual changes** made during deployment

## Backup Management

Backups are stored in `/var/backups/askproai/` with the following structure:
```
/var/backups/askproai/
├── backup_production_20250619_120000/
│   ├── metadata.json
│   ├── database.sql.gz
│   ├── files.tar.gz
│   └── .env.backup
└── migrations/
    └── pre_migration_20250619_120000/
```

### Manual Backup
```bash
php artisan askproai:backup --type=full --compress --encrypt
```

### Restore from Backup
```bash
# Use the rollback script
./rollback-enhanced.sh

# Or manual restore
cd /var/backups/askproai/backup_production_20250619_120000
gunzip -c database.sql.gz | mysql -u root -p askproai_db
```

## Monitoring

### Check Deployment Status
```bash
# View recent deployments
tail -20 /var/log/askproai/deployments.log

# Check current version
cd /var/www/api-gateway && git rev-parse HEAD
```

### Health Monitoring
```bash
# Quick health check
curl https://api.askproai.de/api/health

# Detailed health status
./post-deployment-verify.sh
```

## Troubleshooting

### Deployment Fails

1. **Check logs**
   ```bash
   tail -100 /var/log/askproai/deployment-*.log
   ```

2. **Verify prerequisites**
   ```bash
   ./pre-deployment-checklist.sh production --verbose
   ```

3. **Rollback if needed**
   ```bash
   ./rollback-enhanced.sh --backup=latest
   ```

### Migration Fails

1. **Check migration logs**
   ```bash
   tail -100 /var/log/askproai/migration-*.log
   ```

2. **Rollback migrations**
   ```bash
   php artisan migrate:rollback
   ```

3. **Restore from backup**
   ```bash
   ./rollback-enhanced.sh --partial --backup=latest
   ```

### Service Won't Start

1. **Check service status**
   ```bash
   systemctl status php8.2-fpm
   systemctl status nginx
   systemctl status redis
   ```

2. **Check error logs**
   ```bash
   tail -100 /var/log/nginx/error.log
   tail -100 /var/log/php8.2-fpm.log
   ```

3. **Verify permissions**
   ```bash
   chown -R www-data:www-data /var/www/api-gateway/storage
   chmod -R 755 /var/www/api-gateway/storage
   ```

## Security Notes

1. **Never commit** `.env.production` or `.env.staging` files
2. **Protect backup directory** with appropriate permissions
3. **Rotate webhook secrets** regularly
4. **Use strong database passwords**
5. **Enable 2FA** for server access

## Support

For deployment issues:
1. Check the logs in `/var/log/askproai/`
2. Run diagnostic scripts in verbose mode
3. Contact the development team with log excerpts

---

Last updated: 2025-06-19