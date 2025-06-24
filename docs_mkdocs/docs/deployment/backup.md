# Backup and Recovery Guide

## Overview

This guide covers backup strategies, implementation, and recovery procedures for AskProAI. A robust backup strategy is critical for business continuity and disaster recovery.

## Backup Strategy

### 3-2-1 Rule
- **3** copies of important data
- **2** different storage media types  
- **1** off-site backup location

### Backup Types

```yaml
Full Backup:
  - Complete system snapshot
  - Weekly schedule
  - 4 weeks retention
  
Incremental Backup:
  - Changes since last backup
  - Daily schedule
  - 7 days retention
  
Continuous Backup:
  - Real-time replication
  - Critical data only
  - 24 hours retention
```

## Database Backups

### Automated MySQL Backups

```bash
#!/bin/bash
# /usr/local/bin/mysql-backup.sh

# Configuration
BACKUP_DIR="/var/backups/mysql"
MYSQL_USER="backup_user"
MYSQL_PASS="secure_password"
DATABASE="askproai_db"
DATE=$(date +%Y%m%d_%H%M%S)
RETENTION_DAYS=30

# Create backup directory
mkdir -p $BACKUP_DIR

# Perform backup
echo "Starting MySQL backup..."
mysqldump \
    --user=$MYSQL_USER \
    --password=$MYSQL_PASS \
    --single-transaction \
    --routines \
    --triggers \
    --events \
    --add-drop-table \
    --extended-insert \
    --lock-tables=false \
    $DATABASE | gzip > $BACKUP_DIR/mysql_${DATABASE}_${DATE}.sql.gz

# Verify backup
if [ ${PIPESTATUS[0]} -eq 0 ]; then
    echo "Backup successful: mysql_${DATABASE}_${DATE}.sql.gz"
    
    # Test backup integrity
    gunzip -t $BACKUP_DIR/mysql_${DATABASE}_${DATE}.sql.gz
    if [ $? -eq 0 ]; then
        echo "Backup integrity verified"
    else
        echo "ERROR: Backup file is corrupted!"
        exit 1
    fi
else
    echo "ERROR: Backup failed!"
    exit 1
fi

# Clean old backups
find $BACKUP_DIR -name "mysql_${DATABASE}_*.sql.gz" -mtime +$RETENTION_DAYS -delete

echo "Backup completed"
```

### Point-in-Time Recovery Setup

```sql
-- Enable binary logging for PITR
-- /etc/mysql/mysql.conf.d/mysqld.cnf
[mysqld]
log_bin = /var/log/mysql/mysql-bin
binlog_format = ROW
expire_logs_days = 7
max_binlog_size = 100M
binlog_cache_size = 4M
```

```bash
#!/bin/bash
# Binary log backup script
BINLOG_DIR="/var/log/mysql"
BACKUP_DIR="/var/backups/mysql/binlogs"
DATE=$(date +%Y%m%d_%H%M%S)

# Copy binary logs
mkdir -p $BACKUP_DIR
cp $BINLOG_DIR/mysql-bin.* $BACKUP_DIR/

# Archive binary logs
tar -czf $BACKUP_DIR/binlogs_${DATE}.tar.gz -C $BACKUP_DIR mysql-bin.*
rm $BACKUP_DIR/mysql-bin.*
```

### Continuous Database Replication

```php
// config/database.php
'mysql' => [
    'driver' => 'mysql',
    'read' => [
        'host' => [env('DB_READ_HOST', '127.0.0.1')],
    ],
    'write' => [
        'host' => [env('DB_WRITE_HOST', '127.0.0.1')],
    ],
    'backup' => [
        'host' => env('DB_BACKUP_HOST', '127.0.0.1'),
        'database' => env('DB_DATABASE', 'askproai_db'),
        'username' => env('DB_BACKUP_USERNAME', 'replication'),
        'password' => env('DB_BACKUP_PASSWORD'),
    ],
];
```

## Application Backups

### File System Backup

```bash
#!/bin/bash
# /usr/local/bin/files-backup.sh

# Configuration
BACKUP_DIR="/var/backups/files"
APP_DIR="/var/www/api-gateway"
DATE=$(date +%Y%m%d_%H%M%S)
RETENTION_DAYS=14

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup application files
echo "Backing up application files..."
tar -czf $BACKUP_DIR/app_files_${DATE}.tar.gz \
    -C $APP_DIR \
    storage/app \
    storage/logs \
    public/uploads \
    .env \
    --exclude='storage/app/public/cache' \
    --exclude='storage/logs/*.log'

# Backup user uploads separately (for faster restore)
tar -czf $BACKUP_DIR/uploads_${DATE}.tar.gz \
    -C $APP_DIR/public \
    uploads

# Clean old backups
find $BACKUP_DIR -name "app_files_*.tar.gz" -mtime +$RETENTION_DAYS -delete
find $BACKUP_DIR -name "uploads_*.tar.gz" -mtime +$RETENTION_DAYS -delete

echo "File backup completed"
```

### Configuration Backup

```bash
#!/bin/bash
# /usr/local/bin/config-backup.sh

# Configuration
BACKUP_DIR="/var/backups/config"
DATE=$(date +%Y%m%d_%H%M%S)

mkdir -p $BACKUP_DIR

# Backup all configuration files
tar -czf $BACKUP_DIR/config_${DATE}.tar.gz \
    /etc/nginx/sites-available/ \
    /etc/php/8.2/fpm/pool.d/ \
    /etc/mysql/mysql.conf.d/ \
    /etc/redis/redis.conf \
    /etc/supervisor/conf.d/ \
    /var/www/api-gateway/.env \
    /var/www/api-gateway/config/

# Backup crontabs
crontab -l > $BACKUP_DIR/crontab_root_${DATE}.txt
crontab -u askproai -l > $BACKUP_DIR/crontab_askproai_${DATE}.txt

# Keep only last 30 days
find $BACKUP_DIR -type f -mtime +30 -delete
```

## Cloud Backup

### AWS S3 Backup

```bash
#!/bin/bash
# /usr/local/bin/s3-backup.sh

# Configuration
LOCAL_BACKUP_DIR="/var/backups"
S3_BUCKET="s3://askproai-backups"
DATE=$(date +%Y%m%d)
RETENTION_DAYS=90

# Sync to S3
echo "Syncing backups to S3..."
aws s3 sync $LOCAL_BACKUP_DIR $S3_BUCKET/daily/$DATE/ \
    --exclude "*.tmp" \
    --exclude "*.log"

# Copy critical backups to glacier
aws s3 cp $LOCAL_BACKUP_DIR/mysql/mysql_askproai_db_${DATE}*.sql.gz \
    $S3_BUCKET/glacier/ \
    --storage-class GLACIER

# Clean old S3 backups
aws s3 ls $S3_BUCKET/daily/ | while read -r line; do
    createDate=$(echo $line | awk '{print $1}')
    createDate=$(date -d "$createDate" +%s)
    olderThan=$(date -d "$RETENTION_DAYS days ago" +%s)
    if [[ $createDate -lt $olderThan ]]; then
        folder=$(echo $line | awk '{print $2}')
        aws s3 rm --recursive $S3_BUCKET/daily/$folder
    fi
done

echo "S3 backup completed"
```

### Backup Encryption

```bash
#!/bin/bash
# Encrypt sensitive backups

# Generate encryption key (one time)
openssl rand -base64 32 > /root/.backup-encryption-key
chmod 600 /root/.backup-encryption-key

# Encrypt backup function
encrypt_backup() {
    local input_file=$1
    local output_file="${input_file}.enc"
    
    openssl enc -aes-256-cbc -salt \
        -in "$input_file" \
        -out "$output_file" \
        -pass file:/root/.backup-encryption-key
    
    # Remove unencrypted file
    rm "$input_file"
    
    echo "Encrypted: $output_file"
}

# Usage in backup script
mysqldump askproai_db | gzip > backup.sql.gz
encrypt_backup backup.sql.gz
```

## Backup Monitoring

### Backup Verification

```php
// app/Console/Commands/VerifyBackups.php
class VerifyBackups extends Command
{
    protected $signature = 'backup:verify';
    
    public function handle()
    {
        $this->info('Verifying backups...');
        
        $checks = [
            'database' => $this->verifyDatabaseBackup(),
            'files' => $this->verifyFileBackup(),
            's3' => $this->verifyS3Backup(),
            'replication' => $this->verifyReplication(),
        ];
        
        $failed = collect($checks)->filter(fn($check) => !$check['success']);
        
        if ($failed->isNotEmpty()) {
            $this->error('Backup verification failed!');
            
            // Send alert
            Notification::route('mail', config('backup.notification_email'))
                ->notify(new BackupVerificationFailed($failed));
            
            return 1;
        }
        
        $this->info('All backups verified successfully');
        return 0;
    }
    
    private function verifyDatabaseBackup(): array
    {
        $latestBackup = collect(File::glob('/var/backups/mysql/*.sql.gz'))
            ->sortByDesc(fn($file) => filemtime($file))
            ->first();
            
        if (!$latestBackup) {
            return ['success' => false, 'error' => 'No database backup found'];
        }
        
        $age = (time() - filemtime($latestBackup)) / 3600; // hours
        
        if ($age > 25) { // More than 25 hours old
            return ['success' => false, 'error' => 'Database backup is too old'];
        }
        
        // Test restore to temporary database
        $testDb = 'askproai_backup_test';
        
        exec("mysql -e 'CREATE DATABASE IF NOT EXISTS $testDb'");
        exec("gunzip < $latestBackup | mysql $testDb 2>&1", $output, $returnCode);
        exec("mysql -e 'DROP DATABASE $testDb'");
        
        if ($returnCode !== 0) {
            return ['success' => false, 'error' => 'Failed to restore backup: ' . implode("\n", $output)];
        }
        
        return ['success' => true, 'file' => $latestBackup, 'age_hours' => round($age, 1)];
    }
}
```

### Backup Status Dashboard

```php
// app/Http/Controllers/Admin/BackupStatusController.php
class BackupStatusController extends Controller
{
    public function index()
    {
        $backups = [
            'database' => $this->getDatabaseBackups(),
            'files' => $this->getFileBackups(),
            'cloud' => $this->getCloudBackups(),
        ];
        
        $metrics = [
            'last_backup' => $this->getLastBackupTime(),
            'total_size' => $this->getTotalBackupSize(),
            'success_rate' => $this->getBackupSuccessRate(),
            'next_scheduled' => $this->getNextScheduledBackup(),
        ];
        
        return view('admin.backup-status', compact('backups', 'metrics'));
    }
    
    private function getDatabaseBackups()
    {
        return collect(File::glob('/var/backups/mysql/*.sql.gz'))
            ->map(fn($file) => [
                'name' => basename($file),
                'size' => $this->humanFilesize(filesize($file)),
                'created' => Carbon::createFromTimestamp(filemtime($file)),
                'type' => 'database',
            ])
            ->sortByDesc('created')
            ->take(10);
    }
}
```

## Recovery Procedures

### Database Recovery

```bash
#!/bin/bash
# /usr/local/bin/mysql-restore.sh

# Configuration
BACKUP_FILE=$1
DATABASE="askproai_db"

if [ -z "$BACKUP_FILE" ]; then
    echo "Usage: $0 <backup_file>"
    exit 1
fi

# Verify backup file exists
if [ ! -f "$BACKUP_FILE" ]; then
    echo "ERROR: Backup file not found: $BACKUP_FILE"
    exit 1
fi

# Create restore point
echo "Creating restore point..."
mysqldump $DATABASE | gzip > /tmp/restore_point_$(date +%Y%m%d_%H%M%S).sql.gz

# Drop and recreate database
echo "Preparing database..."
mysql -e "DROP DATABASE IF EXISTS $DATABASE"
mysql -e "CREATE DATABASE $DATABASE CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"

# Restore backup
echo "Restoring database from $BACKUP_FILE..."
gunzip < $BACKUP_FILE | mysql $DATABASE

if [ $? -eq 0 ]; then
    echo "Database restored successfully"
    
    # Run migrations to ensure schema is up to date
    cd /var/www/api-gateway
    php artisan migrate --force
    
    echo "Recovery completed"
else
    echo "ERROR: Database restore failed!"
    echo "Restore point saved at: /tmp/restore_point_*.sql.gz"
    exit 1
fi
```

### Point-in-Time Recovery

```bash
#!/bin/bash
# PITR to specific timestamp

# Configuration
TARGET_TIME="2025-06-23 14:30:00"
FULL_BACKUP="/var/backups/mysql/mysql_askproai_db_20250623_020000.sql.gz"
BINLOG_DIR="/var/log/mysql"

# Restore full backup
echo "Restoring full backup..."
gunzip < $FULL_BACKUP | mysql askproai_db

# Apply binary logs up to target time
echo "Applying binary logs..."
mysqlbinlog \
    --stop-datetime="$TARGET_TIME" \
    $BINLOG_DIR/mysql-bin.[0-9]* | mysql askproai_db

echo "Point-in-time recovery completed to: $TARGET_TIME"
```

### Application Recovery

```bash
#!/bin/bash
# /usr/local/bin/app-restore.sh

# Configuration
BACKUP_FILE=$1
APP_DIR="/var/www/api-gateway"

if [ -z "$BACKUP_FILE" ]; then
    echo "Usage: $0 <backup_file>"
    exit 1
fi

# Create restore point
echo "Creating restore point..."
tar -czf /tmp/app_restore_point_$(date +%Y%m%d_%H%M%S).tar.gz -C $APP_DIR .

# Extract backup
echo "Restoring application files..."
tar -xzf $BACKUP_FILE -C $APP_DIR

# Fix permissions
chown -R askproai:www-data $APP_DIR
chmod -R 755 $APP_DIR
chmod -R 775 $APP_DIR/storage
chmod -R 775 $APP_DIR/bootstrap/cache

# Clear caches
cd $APP_DIR
php artisan optimize:clear
php artisan optimize

# Restart services
systemctl reload php8.2-fpm
supervisorctl restart all

echo "Application restored successfully"
```

## Disaster Recovery Plan

### Recovery Time Objectives

```yaml
Critical Systems:
  Database: 
    RTO: 1 hour
    RPO: 15 minutes
  Application:
    RTO: 30 minutes
    RPO: 1 hour
  User Uploads:
    RTO: 2 hours
    RPO: 24 hours

Non-Critical Systems:
  Logs:
    RTO: 24 hours
    RPO: 7 days
  Analytics:
    RTO: 48 hours
    RPO: 24 hours
```

### Disaster Recovery Runbook

```markdown
# Disaster Recovery Runbook

## 1. Assessment (0-15 minutes)
- [ ] Identify scope of disaster
- [ ] Determine affected systems
- [ ] Activate incident response team
- [ ] Communicate with stakeholders

## 2. Infrastructure Recovery (15-45 minutes)
- [ ] Provision new servers if needed
- [ ] Restore network configuration
- [ ] Install required software packages
- [ ] Configure security groups/firewall

## 3. Database Recovery (45-90 minutes)
- [ ] Locate latest viable backup
- [ ] Restore database from backup
- [ ] Apply binary logs if PITR needed
- [ ] Verify data integrity
- [ ] Test database connectivity

## 4. Application Recovery (90-120 minutes)
- [ ] Deploy application code
- [ ] Restore configuration files
- [ ] Restore user uploads
- [ ] Configure environment variables
- [ ] Start application services

## 5. Verification (120-150 minutes)
- [ ] Run health checks
- [ ] Test critical functionality
- [ ] Verify external integrations
- [ ] Check monitoring systems
- [ ] Test user authentication

## 6. Communication (150-180 minutes)
- [ ] Update status page
- [ ] Notify customers
- [ ] Document incident
- [ ] Schedule post-mortem
```

### Automated Recovery

```php
// app/Console/Commands/DisasterRecovery.php
class DisasterRecovery extends Command
{
    protected $signature = 'dr:execute {--scenario=full}';
    
    public function handle()
    {
        $scenario = $this->option('scenario');
        
        $this->info("Executing disaster recovery: $scenario");
        
        try {
            match($scenario) {
                'database' => $this->recoverDatabase(),
                'application' => $this->recoverApplication(),
                'full' => $this->fullRecovery(),
                default => throw new \InvalidArgumentException("Unknown scenario: $scenario"),
            };
            
            $this->info('Disaster recovery completed successfully');
            
            // Send notification
            Notification::route('mail', config('dr.notification_email'))
                ->notify(new DisasterRecoveryCompleted($scenario));
                
        } catch (\Exception $e) {
            $this->error('Disaster recovery failed: ' . $e->getMessage());
            
            // Alert on-call team
            Notification::route('sms', config('dr.oncall_phone'))
                ->notify(new DisasterRecoveryFailed($scenario, $e));
                
            return 1;
        }
    }
    
    private function fullRecovery()
    {
        $steps = [
            'Checking prerequisites' => fn() => $this->checkPrerequisites(),
            'Recovering database' => fn() => $this->recoverDatabase(),
            'Recovering application' => fn() => $this->recoverApplication(),
            'Verifying services' => fn() => $this->verifyServices(),
            'Running smoke tests' => fn() => $this->runSmokeTests(),
        ];
        
        foreach ($steps as $step => $action) {
            $this->info($step . '...');
            $action();
            $this->info('✓ ' . $step . ' completed');
        }
    }
}
```

## Backup Testing

### Monthly Restore Test

```bash
#!/bin/bash
# /usr/local/bin/test-restore.sh

# Configuration
TEST_SERVER="10.0.2.100"
TEST_DB="askproai_test"

echo "Starting monthly restore test..."

# Get latest backups
LATEST_DB_BACKUP=$(ls -t /var/backups/mysql/*.sql.gz | head -1)
LATEST_FILE_BACKUP=$(ls -t /var/backups/files/*.tar.gz | head -1)

# Test database restore
echo "Testing database restore..."
scp $LATEST_DB_BACKUP $TEST_SERVER:/tmp/
ssh $TEST_SERVER "
    mysql -e 'DROP DATABASE IF EXISTS $TEST_DB'
    mysql -e 'CREATE DATABASE $TEST_DB'
    gunzip < /tmp/$(basename $LATEST_DB_BACKUP) | mysql $TEST_DB
    mysql $TEST_DB -e 'SELECT COUNT(*) FROM appointments'
"

# Test file restore
echo "Testing file restore..."
scp $LATEST_FILE_BACKUP $TEST_SERVER:/tmp/
ssh $TEST_SERVER "
    mkdir -p /tmp/restore_test
    tar -xzf /tmp/$(basename $LATEST_FILE_BACKUP) -C /tmp/restore_test
    ls -la /tmp/restore_test/storage/app
"

echo "Restore test completed"
```

### Chaos Engineering

```php
// app/Console/Commands/ChaosTest.php
class ChaosTest extends Command
{
    protected $signature = 'chaos:test {--component=database}';
    
    public function handle()
    {
        if (app()->environment('production')) {
            $this->error('Cannot run chaos tests in production!');
            return 1;
        }
        
        $component = $this->option('component');
        
        $this->warn("Starting chaos test for: $component");
        $this->warn('This will simulate a failure. Continue? (yes/no)');
        
        if ($this->ask('Continue?') !== 'yes') {
            return 0;
        }
        
        match($component) {
            'database' => $this->simulateDatabaseFailure(),
            'redis' => $this->simulateRedisFailure(),
            'storage' => $this->simulateStorageFailure(),
            'network' => $this->simulateNetworkFailure(),
        };
        
        $this->info('Chaos test completed. Check logs for recovery behavior.');
    }
}
```

## Compliance and Retention

### Data Retention Policy

```php
// config/backup.php
return [
    'retention' => [
        'database' => [
            'daily' => 7,      // Keep daily backups for 7 days
            'weekly' => 4,     // Keep weekly backups for 4 weeks
            'monthly' => 12,   // Keep monthly backups for 12 months
            'yearly' => 7,     // Keep yearly backups for 7 years
        ],
        'files' => [
            'daily' => 3,
            'weekly' => 2,
            'monthly' => 6,
        ],
        'logs' => [
            'application' => 30,    // 30 days
            'access' => 90,         // 90 days
            'audit' => 365 * 2,     // 2 years
        ],
    ],
    
    'compliance' => [
        'gdpr' => [
            'personal_data_retention' => 365 * 5, // 5 years
            'deletion_backup_retention' => 30,     // 30 days after deletion
        ],
        'financial' => [
            'invoice_retention' => 365 * 10,      // 10 years
            'transaction_retention' => 365 * 7,    // 7 years
        ],
    ],
];
```

### Automated Cleanup

```php
// app/Console/Commands/BackupCleanup.php
class BackupCleanup extends Command
{
    protected $signature = 'backup:cleanup {--dry-run}';
    
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        $this->info('Starting backup cleanup...');
        
        $deleted = [
            'database' => $this->cleanupDatabaseBackups($dryRun),
            'files' => $this->cleanupFileBackups($dryRun),
            'cloud' => $this->cleanupCloudBackups($dryRun),
        ];
        
        $this->table(
            ['Type', 'Files Deleted', 'Space Freed'],
            collect($deleted)->map(fn($info, $type) => [
                $type,
                $info['count'],
                $this->humanFilesize($info['size']),
            ])->toArray()
        );
        
        if ($dryRun) {
            $this->warn('This was a dry run. No files were actually deleted.');
        }
    }
}
```

## Related Documentation

- [Disaster Recovery Plan](../operations/disaster-recovery.md)
- [Security Best Practices](../configuration/security.md)
- [Database Configuration](../configuration/database.md)
- [Monitoring Setup](monitoring.md)