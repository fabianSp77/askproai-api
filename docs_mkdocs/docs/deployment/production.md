# Production Deployment Guide

## Overview

This guide covers best practices and procedures for deploying AskProAI to production, including deployment strategies, optimization, security hardening, and maintenance procedures.

## Pre-Deployment Checklist

### Code Preparation
```bash
# Ensure all tests pass
php artisan test
npm run test

# Check code quality
./vendor/bin/phpstan analyse
./vendor/bin/php-cs-fixer fix --dry-run

# Verify no debug code
grep -r "dd(" app/
grep -r "dump(" app/
grep -r "var_dump" app/

# Check for sensitive data
grep -r "password" .env.example
grep -r "secret" config/
```

### Environment Verification
```bash
# Verify production environment
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.askproai.de

# Ensure all required services are configured
php artisan config:show services

# Test external connections
php artisan askproai:test-connections
```

## Deployment Strategies

### Blue-Green Deployment

```bash
#!/bin/bash
# deploy-blue-green.sh

BLUE_DIR="/var/www/api-gateway-blue"
GREEN_DIR="/var/www/api-gateway-green"
CURRENT_DIR="/var/www/api-gateway"

# Determine which is current
if [ "$(readlink $CURRENT_DIR)" = "$BLUE_DIR" ]; then
    DEPLOY_TO=$GREEN_DIR
    OLD_DIR=$BLUE_DIR
else
    DEPLOY_TO=$BLUE_DIR
    OLD_DIR=$GREEN_DIR
fi

echo "Deploying to $DEPLOY_TO"

# Deploy new version
cd $DEPLOY_TO
git pull origin main
composer install --optimize-autoloader --no-dev
npm ci && npm run build

# Copy environment file
cp $OLD_DIR/.env $DEPLOY_TO/.env

# Run migrations
php artisan migrate --force

# Cache everything
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Warm up cache
php artisan cache:warm

# Run health checks
php artisan health:check

# Switch symlink
ln -sfn $DEPLOY_TO $CURRENT_DIR

# Reload PHP-FPM
sudo systemctl reload php8.2-fpm

echo "Deployment complete"
```

### Rolling Deployment with Zero Downtime

```php
// app/Console/Commands/DeployRolling.php
class DeployRolling extends Command
{
    protected $signature = 'deploy:rolling';
    
    public function handle()
    {
        $this->info('Starting rolling deployment...');
        
        // Put application in maintenance mode with secret
        $secret = Str::random(32);
        $this->call('down', [
            '--secret' => $secret,
            '--render' => 'errors::503',
            '--retry' => 60,
        ]);
        
        $this->info("Maintenance mode enabled. Secret: {$secret}");
        
        // Run deployment tasks
        $this->runDeploymentTasks();
        
        // Bring application back online
        $this->call('up');
        
        $this->info('Deployment complete!');
    }
    
    private function runDeploymentTasks()
    {
        // Update code
        $this->info('Updating code...');
        exec('git pull origin main');
        
        // Install dependencies
        $this->info('Installing dependencies...');
        exec('composer install --optimize-autoloader --no-dev');
        exec('npm ci && npm run build');
        
        // Run migrations
        $this->info('Running migrations...');
        $this->call('migrate', ['--force' => true]);
        
        // Clear and rebuild caches
        $this->info('Rebuilding caches...');
        $this->call('optimize:clear');
        $this->call('optimize');
        
        // Restart queue workers gracefully
        $this->info('Restarting queue workers...');
        $this->call('horizon:terminate');
        sleep(5);
        exec('sudo supervisorctl restart horizon');
    }
}
```

### Container-Based Deployment

```dockerfile
# Dockerfile
FROM php:8.2-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nginx \
    supervisor

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application
COPY . .

# Install dependencies
RUN composer install --optimize-autoloader --no-dev
RUN npm ci && npm run build

# Copy configuration files
COPY docker/nginx/conf.d/app.conf /etc/nginx/conf.d/
COPY docker/supervisor/conf.d/supervisord.conf /etc/supervisor/conf.d/

# Set permissions
RUN chown -R www-data:www-data /var/www

EXPOSE 80

CMD ["/usr/bin/supervisord"]
```

## Production Optimization

### PHP Optimization

```ini
; /etc/php/8.2/fpm/conf.d/99-production.ini

; OPcache settings
opcache.enable=1
opcache.memory_consumption=512
opcache.interned_strings_buffer=64
opcache.max_accelerated_files=32531
opcache.revalidate_freq=0
opcache.save_comments=1
opcache.fast_shutdown=1
opcache.enable_file_override=1
opcache.validate_timestamps=0

; Performance settings
realpath_cache_size=4096K
realpath_cache_ttl=600
max_execution_time=30
max_input_time=60
memory_limit=512M

; Security
expose_php=Off
```

### Laravel Optimization

```bash
# Optimize autoloader
composer dump-autoload --optimize --no-dev

# Cache all configurations
php artisan optimize

# Compile assets
npm run production

# Enable route caching
php artisan route:cache

# Enable view caching
php artisan view:cache

# Enable event caching
php artisan event:cache

# Optimize database
php artisan db:optimize
```

### Database Optimization

```sql
-- Optimize tables
OPTIMIZE TABLE appointments;
OPTIMIZE TABLE calls;
OPTIMIZE TABLE customers;

-- Update statistics
ANALYZE TABLE appointments;
ANALYZE TABLE calls;
ANALYZE TABLE customers;

-- Add missing indexes
ALTER TABLE appointments ADD INDEX idx_date_status (date, status);
ALTER TABLE calls ADD INDEX idx_created_company (created_at, company_id);
```

### Redis Optimization

```bash
# redis.conf optimizations
maxmemory 4gb
maxmemory-policy allkeys-lru
save ""  # Disable RDB snapshots if using AOF
appendonly yes
appendfsync everysec
no-appendfsync-on-rewrite yes
auto-aof-rewrite-percentage 100
auto-aof-rewrite-min-size 64mb
```

## Security Hardening

### Server Security

```bash
# Disable unnecessary services
sudo systemctl disable bluetooth
sudo systemctl disable cups

# Configure sysctl for security
sudo nano /etc/sysctl.d/99-security.conf

# Add these settings:
net.ipv4.tcp_syncookies = 1
net.ipv4.tcp_max_syn_backlog = 2048
net.ipv4.tcp_synack_retries = 2
net.ipv4.ip_forward = 0
net.ipv4.conf.all.send_redirects = 0
net.ipv4.conf.all.accept_redirects = 0
net.ipv4.conf.all.accept_source_route = 0
net.ipv4.conf.all.log_martians = 1

# Apply settings
sudo sysctl -p /etc/sysctl.d/99-security.conf
```

### Application Security

```php
// app/Http/Kernel.php
protected $middlewareGroups = [
    'web' => [
        \App\Http\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \App\Http\Middleware\SecurityHeaders::class,
        \App\Http\Middleware\PreventClickjacking::class,
        \App\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\ThrottleRequests::class,
    ],
];

// app/Http/Middleware/SecurityHeaders.php
public function handle($request, Closure $next)
{
    $response = $next($request);
    
    $response->headers->set('X-Content-Type-Options', 'nosniff');
    $response->headers->set('X-Frame-Options', 'DENY');
    $response->headers->set('X-XSS-Protection', '1; mode=block');
    $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->headers->set('Content-Security-Policy', "default-src 'self'");
    $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
    
    if ($request->secure()) {
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
    }
    
    return $response;
}
```

### SSL/TLS Configuration

```nginx
# /etc/nginx/sites-available/askproai-ssl

server {
    listen 443 ssl http2;
    server_name api.askproai.de;
    
    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/api.askproai.de/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.askproai.de/privkey.pem;
    
    # SSL Security
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;
    
    # SSL Optimization
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;
    ssl_stapling on;
    ssl_stapling_verify on;
    
    # Security Headers
    add_header Strict-Transport-Security "max-age=63072000; includeSubDomains; preload" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "DENY" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
}
```

## Monitoring Setup

### Application Monitoring

```php
// config/monitoring.php
return [
    'alerts' => [
        'high_error_rate' => [
            'threshold' => 100, // errors per minute
            'duration' => 5, // minutes
            'channels' => ['slack', 'email'],
        ],
        'slow_response' => [
            'threshold' => 1000, // milliseconds
            'percentile' => 95,
            'channels' => ['slack'],
        ],
        'queue_size' => [
            'threshold' => 1000,
            'queues' => ['default', 'webhooks'],
            'channels' => ['slack', 'sms'],
        ],
        'failed_jobs' => [
            'threshold' => 10,
            'window' => 60, // minutes
            'channels' => ['email', 'slack'],
        ],
    ],
];
```

### Health Checks

```php
// app/Http/Controllers/HealthController.php
class HealthController extends Controller
{
    public function check()
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'queue' => $this->checkQueue(),
            'storage' => $this->checkStorage(),
            'services' => $this->checkExternalServices(),
        ];
        
        $healthy = collect($checks)->every(fn($check) => $check['status'] === 'healthy');
        
        return response()->json([
            'status' => $healthy ? 'healthy' : 'unhealthy',
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }
    
    private function checkDatabase(): array
    {
        try {
            DB::select('SELECT 1');
            return ['status' => 'healthy', 'response_time' => DB::getQueryLog()[0]['time'] ?? 0];
        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'error' => $e->getMessage()];
        }
    }
}
```

## Backup Procedures

### Automated Backup Script

```bash
#!/bin/bash
# /usr/local/bin/askproai-backup.sh

# Configuration
BACKUP_DIR="/var/backups/askproai"
S3_BUCKET="s3://askproai-backups"
DATE=$(date +%Y%m%d_%H%M%S)
RETENTION_DAYS=30

# Create backup directory
mkdir -p $BACKUP_DIR

# Database backup
echo "Backing up database..."
mysqldump \
    --single-transaction \
    --routines \
    --triggers \
    --add-drop-table \
    -h localhost \
    -u askproai_user \
    -p'password' \
    askproai_db | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Application files backup
echo "Backing up application files..."
tar -czf $BACKUP_DIR/files_$DATE.tar.gz \
    -C /var/www/api-gateway \
    storage/app \
    .env \
    --exclude=storage/app/public/cache

# Upload to S3
echo "Uploading to S3..."
aws s3 cp $BACKUP_DIR/db_$DATE.sql.gz $S3_BUCKET/daily/
aws s3 cp $BACKUP_DIR/files_$DATE.tar.gz $S3_BUCKET/daily/

# Cleanup old backups
echo "Cleaning up old backups..."
find $BACKUP_DIR -type f -mtime +7 -delete
aws s3 ls $S3_BUCKET/daily/ | while read -r line; do
    createDate=$(echo $line | awk '{print $1" "$2}')
    createDate=$(date -d "$createDate" +%s)
    olderThan=$(date -d "$RETENTION_DAYS days ago" +%s)
    if [[ $createDate -lt $olderThan ]]; then
        fileName=$(echo $line | awk '{print $4}')
        aws s3 rm $S3_BUCKET/daily/$fileName
    fi
done

echo "Backup completed"
```

### Backup Verification

```bash
#!/bin/bash
# /usr/local/bin/verify-backup.sh

LATEST_BACKUP=$(ls -t /var/backups/askproai/db_*.sql.gz | head -1)
TEMP_DB="askproai_verify"

echo "Verifying backup: $LATEST_BACKUP"

# Create temporary database
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS $TEMP_DB"

# Restore backup
gunzip < $LATEST_BACKUP | mysql -u root -p $TEMP_DB

# Run verification queries
TABLES=$(mysql -u root -p $TEMP_DB -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$TEMP_DB'" -s -N)
APPOINTMENTS=$(mysql -u root -p $TEMP_DB -e "SELECT COUNT(*) FROM appointments" -s -N)

echo "Tables: $TABLES"
echo "Appointments: $APPOINTMENTS"

# Cleanup
mysql -u root -p -e "DROP DATABASE $TEMP_DB"

if [ $TABLES -gt 50 ] && [ $APPOINTMENTS -gt 0 ]; then
    echo "Backup verification: PASSED"
    exit 0
else
    echo "Backup verification: FAILED"
    exit 1
fi
```

## Maintenance Mode

### Scheduled Maintenance

```php
// app/Console/Commands/ScheduledMaintenance.php
class ScheduledMaintenance extends Command
{
    protected $signature = 'maintenance:scheduled {--duration=30}';
    
    public function handle()
    {
        $duration = $this->option('duration');
        
        // Notify users in advance
        Notification::send(
            User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->get(),
            new MaintenanceScheduled($duration)
        );
        
        // Schedule maintenance mode
        $this->info("Scheduling maintenance in 5 minutes for {$duration} minutes");
        
        sleep(300); // Wait 5 minutes
        
        // Enable maintenance mode
        $this->call('down', [
            '--message' => 'Scheduled maintenance in progress',
            '--retry' => $duration * 60,
            '--refresh' => 15,
        ]);
        
        // Perform maintenance tasks
        $this->performMaintenance();
        
        // Disable maintenance mode
        $this->call('up');
        
        $this->info('Maintenance completed');
    }
}
```

### Emergency Maintenance

```bash
# Enable maintenance mode immediately
php artisan down --message="Emergency maintenance" --retry=60

# Allow specific IPs during maintenance
php artisan down --allow=192.168.1.1 --allow=10.0.0.1

# Create custom maintenance page
php artisan down --render="errors::maintenance"
```

## Rollback Procedures

### Database Rollback

```bash
#!/bin/bash
# rollback-database.sh

BACKUP_FILE=$1
TEMP_DB="askproai_rollback"

echo "Rolling back database to: $BACKUP_FILE"

# Create backup of current state
mysqldump -u askproai_user -p askproai_db | gzip > rollback_before_$(date +%Y%m%d_%H%M%S).sql.gz

# Restore from backup
gunzip < $BACKUP_FILE | mysql -u askproai_user -p askproai_db

echo "Database rolled back successfully"
```

### Code Rollback

```bash
#!/bin/bash
# rollback-code.sh

PREVIOUS_RELEASE=$1

cd /var/www/api-gateway

# Create backup tag
git tag -a "rollback-$(date +%Y%m%d_%H%M%S)" -m "Rollback point"

# Rollback to previous release
git checkout $PREVIOUS_RELEASE

# Install dependencies for that release
composer install --optimize-autoloader --no-dev
npm ci && npm run build

# Clear caches
php artisan optimize:clear
php artisan optimize

# Restart services
sudo systemctl reload php8.2-fpm
sudo supervisorctl restart horizon

echo "Code rolled back to $PREVIOUS_RELEASE"
```

## Performance Tuning

### Database Tuning

```sql
-- Monitor slow queries
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 1;
SET GLOBAL slow_query_log_file = '/var/log/mysql/slow-query.log';

-- Analyze query performance
EXPLAIN ANALYZE 
SELECT * FROM appointments 
WHERE company_id = 1 
  AND date >= CURDATE() 
  AND status = 'scheduled'
ORDER BY date, time;
```

### Redis Tuning

```bash
# Monitor Redis performance
redis-cli --latency
redis-cli --latency-history
redis-cli --latency-dist

# Check memory usage
redis-cli INFO memory

# Enable Redis slow log
redis-cli CONFIG SET slowlog-log-slower-than 10000
redis-cli SLOWLOG GET 10
```

## Post-Deployment Verification

### Automated Tests

```bash
#!/bin/bash
# post-deploy-test.sh

echo "Running post-deployment tests..."

# API endpoint tests
curl -f https://api.askproai.de/api/health || exit 1
curl -f https://api.askproai.de/api/version || exit 1

# Database connectivity
php artisan tinker --execute="DB::select('SELECT 1')" || exit 1

# Redis connectivity
php artisan tinker --execute="Redis::ping()" || exit 1

# Queue processing
php artisan queue:work --stop-when-empty || exit 1

# External service connectivity
php artisan askproai:test-connections || exit 1

echo "All tests passed!"
```

### Monitoring Alerts

```php
// app/Console/Commands/PostDeploymentCheck.php
class PostDeploymentCheck extends Command
{
    protected $signature = 'deploy:verify';
    
    public function handle()
    {
        $checks = [
            'Application Version' => config('app.version'),
            'Database Migrations' => $this->checkMigrations(),
            'Cache Status' => $this->checkCache(),
            'Queue Workers' => $this->checkQueues(),
            'External Services' => $this->checkServices(),
            'Error Rate' => $this->checkErrorRate(),
        ];
        
        $this->table(['Check', 'Status'], 
            collect($checks)->map(fn($status, $check) => [$check, $status])->toArray()
        );
        
        if (collect($checks)->contains('Failed')) {
            $this->error('Post-deployment verification failed!');
            return 1;
        }
        
        $this->info('Post-deployment verification passed!');
        return 0;
    }
}
```

## Disaster Recovery

### Recovery Plan

```yaml
# disaster-recovery-plan.yml
recovery_objectives:
  rto: 4 hours  # Recovery Time Objective
  rpo: 1 hour   # Recovery Point Objective

backup_locations:
  - type: local
    path: /var/backups/askproai
    retention: 7 days
  - type: s3
    bucket: askproai-backups
    retention: 30 days
  - type: glacier
    bucket: askproai-archive
    retention: 1 year

recovery_procedures:
  1_assess:
    - Identify scope of disaster
    - Activate incident response team
    - Communicate with stakeholders
  
  2_restore:
    - Provision new infrastructure if needed
    - Restore latest database backup
    - Restore application code
    - Restore configuration files
    
  3_verify:
    - Run system health checks
    - Verify data integrity
    - Test critical functionality
    
  4_resume:
    - Update DNS if needed
    - Enable application
    - Monitor closely for 24 hours
```

## Related Documentation

- [Installation Guide](installation.md)
- [Monitoring Setup](../operations/monitoring.md)
- [Backup Strategies](backup.md)
- [Performance Optimization](../operations/performance.md)
- [Security Best Practices](../configuration/security.md)