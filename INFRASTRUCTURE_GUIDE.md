# 🏗️ AskProAI Infrastructure Guide

> **Complete Infrastructure Documentation for Production-Scale AI SaaS Platform**  
> Last Updated: 2025-07-10

## 📑 Table of Contents

1. [Quick Start](#quick-start)
2. [Architecture Overview](#architecture-overview)
3. [Server Specifications](#server-specifications)
4. [Network Architecture](#network-architecture)
5. [Database Infrastructure](#database-infrastructure)
6. [Redis Configuration](#redis-configuration)
7. [Load Balancing](#load-balancing)
8. [SSL/TLS Management](#ssltls-management)
9. [CDN Configuration](#cdn-configuration)
10. [Backup Strategies](#backup-strategies)
11. [Security Hardening](#security-hardening)
12. [Performance Optimization](#performance-optimization)
13. [Monitoring Setup](#monitoring-setup)
14. [Disaster Recovery](#disaster-recovery)
15. [Scaling Strategies](#scaling-strategies)
16. [Cost Optimization](#cost-optimization)
17. [Troubleshooting Guide](#troubleshooting-guide)
18. [Scripts & Automation](#scripts--automation)

---

## 🚀 Quick Start

### Production Deployment in 10 Minutes
```bash
# 1. Clone and setup
git clone https://github.com/askproai/api-gateway.git
cd api-gateway
./scripts/quick-setup.sh production

# 2. Configure environment
cp .env.production.template .env
nano .env  # Update with your credentials

# 3. Deploy with zero downtime
./deploy/zero-downtime-deploy.sh production

# 4. Verify deployment
curl -f https://api.askproai.de/health
```

### Essential Commands
```bash
# Health check
php artisan health:check

# Performance status
php artisan performance:analyze

# Backup database
php artisan backup:run --only-db

# Monitor resources
htop  # CPU/Memory
iotop  # Disk I/O
iftop  # Network
```

---

## 🏛️ Architecture Overview

### High-Level Architecture
```
┌─────────────────────────────────────────────────────────────────┐
│                         CloudFlare CDN                           │
│                    (DDoS Protection & Caching)                   │
└─────────────────────┬───────────────────────────────────────────┘
                      │
┌─────────────────────▼───────────────────────────────────────────┐
│                      Load Balancer                               │
│                 (HAProxy / Nginx / AWS ALB)                      │
└─────────────────────┬───────────────────────────────────────────┘
                      │
        ┌─────────────┴─────────────┬─────────────────────┐
        │                           │                      │
┌───────▼────────┐         ┌───────▼────────┐    ┌───────▼────────┐
│   Web Server 1 │         │   Web Server 2 │    │   Web Server N │
│  Nginx + PHP   │         │  Nginx + PHP   │    │  Nginx + PHP   │
└───────┬────────┘         └───────┬────────┘    └───────┬────────┘
        │                           │                      │
        └───────────────┬───────────┴──────────────────────┘
                        │
                ┌───────▼────────┐
                │  Redis Cluster │
                │ (Session/Cache)│
                └───────┬────────┘
                        │
                ┌───────▼────────┐
                │ MySQL Primary  │
                │   + Replicas   │
                └────────────────┘
```

### Component Stack
- **Web Server**: Nginx 1.24+ with PHP-FPM 8.3
- **Application**: Laravel 11.x with Filament Admin
- **Database**: MySQL 8.0 / MariaDB 10.11
- **Cache**: Redis 7.0+ (Sessions, Cache, Queues)
- **Queue**: Laravel Horizon with Redis backend
- **Search**: MySQL Full-Text (ElasticSearch optional)
- **Monitoring**: Prometheus + Grafana + Alertmanager
- **Backups**: Local + S3 compatible storage

---

## 💻 Server Specifications

### Scaling Tiers

#### 🟢 **Starter** (100-500 calls/day)
```yaml
CPU: 2 vCPUs (2.4 GHz+)
RAM: 4 GB
Storage: 40 GB SSD
Network: 100 Mbps
Monthly Cost: ~€20-30
```

#### 🔵 **Growth** (500-2,000 calls/day) - **Current Production**
```yaml
CPU: 4 vCPUs (2.6 GHz+)
RAM: 8 GB
Storage: 100 GB NVMe SSD
Network: 1 Gbps
Monthly Cost: ~€50-80
Provider: Netcup VPS 2000 G10
```

#### 🟣 **Business** (2,000-10,000 calls/day)
```yaml
CPU: 8 vCPUs (3.0 GHz+)
RAM: 16 GB
Storage: 250 GB NVMe SSD
Network: 1 Gbps
Database: Separate instance
Monthly Cost: ~€150-200
```

#### 🔴 **Enterprise** (10,000+ calls/day)
```yaml
# Application Servers (3x)
CPU: 16 vCPUs per server
RAM: 32 GB per server
Storage: 500 GB NVMe RAID 10

# Database Cluster
Primary: 32 GB RAM, 16 vCPUs
Replicas: 2x 16 GB RAM, 8 vCPUs

# Redis Cluster
3x nodes with 8 GB RAM each

# Load Balancer
2x HA instances

Monthly Cost: ~€800-1,200
```

### Current Production Setup (Netcup)
```bash
# Server Details
Provider: Netcup
Product: VPS 2000 G10
Location: Nuremberg, Germany
IPv4: 185.233.106.155
IPv6: 2a03:4000:46:5d6::1

# Specifications
CPU: 4 vCore (AMD EPYC)
RAM: 8 GB DDR4 ECC
Storage: 160 GB SSD (RAID 10)
Traffic: 80 TB/month included
Uptime SLA: 99.9%
```

---

## 🌐 Network Architecture

### DNS Configuration
```dns
# A Records
api.askproai.de.        A     185.233.106.155
www.askproai.de.        A     185.233.106.155

# AAAA Records (IPv6)
api.askproai.de.        AAAA  2a03:4000:46:5d6::1
www.askproai.de.        AAAA  2a03:4000:46:5d6::1

# MX Records
askproai.de.            MX    10 mail.askproai.de.

# TXT Records
askproai.de.            TXT   "v=spf1 ip4:185.233.106.155 ~all"
_dmarc.askproai.de.     TXT   "v=DMARC1; p=quarantine; rua=mailto:admin@askproai.de"
```

### Firewall Rules (iptables)
```bash
#!/bin/bash
# /etc/iptables/rules.v4

# Basic security
iptables -P INPUT DROP
iptables -P FORWARD DROP
iptables -P OUTPUT ACCEPT

# Allow established connections
iptables -A INPUT -m state --state ESTABLISHED,RELATED -j ACCEPT

# Allow loopback
iptables -A INPUT -i lo -j ACCEPT

# Allow SSH (rate limited)
iptables -A INPUT -p tcp --dport 22 -m state --state NEW -m recent --set
iptables -A INPUT -p tcp --dport 22 -m state --state NEW -m recent --update --seconds 60 --hitcount 4 -j DROP
iptables -A INPUT -p tcp --dport 22 -j ACCEPT

# Allow HTTP/HTTPS
iptables -A INPUT -p tcp --dport 80 -j ACCEPT
iptables -A INPUT -p tcp --dport 443 -j ACCEPT

# Allow monitoring (internal only)
iptables -A INPUT -s 10.0.0.0/8 -p tcp --dport 9090 -j ACCEPT  # Prometheus
iptables -A INPUT -s 10.0.0.0/8 -p tcp --dport 3000 -j ACCEPT  # Grafana

# DDoS Protection
iptables -A INPUT -p tcp --tcp-flags ALL NONE -j DROP
iptables -A INPUT -p tcp --tcp-flags SYN,FIN SYN,FIN -j DROP
iptables -A INPUT -p tcp --tcp-flags SYN,RST SYN,RST -j DROP
iptables -A INPUT -p tcp --syn -m limit --limit 25/s --limit-burst 50 -j ACCEPT
```

### Nginx Configuration
```nginx
# /etc/nginx/sites-available/askproai
server {
    listen 80;
    server_name api.askproai.de www.askproai.de;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name api.askproai.de;
    
    root /var/www/api-gateway/public;
    index index.php;
    
    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/api.askproai.de/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.askproai.de/privkey.pem;
    ssl_trusted_certificate /etc/letsencrypt/live/api.askproai.de/chain.pem;
    
    # SSL Security
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;
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
    add_header Content-Security-Policy "default-src 'self' https:; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com;" always;
    
    # Performance
    client_max_body_size 50M;
    client_body_buffer_size 128k;
    client_header_buffer_size 1k;
    large_client_header_buffers 4 16k;
    
    # Gzip
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css text/xml text/javascript application/json application/javascript application/xml+rss application/rss+xml application/atom+xml image/svg+xml;
    
    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2|ttf|svg|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_buffer_size 32k;
        fastcgi_buffers 8 16k;
        fastcgi_connect_timeout 60;
        fastcgi_send_timeout 300;
        fastcgi_read_timeout 300;
    }
    
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

---

## 🗄️ Database Infrastructure

### MySQL/MariaDB Configuration
```ini
# /etc/mysql/mysql.conf.d/askproai.cnf
[mysqld]
# Basic Settings
user            = mysql
bind-address    = 127.0.0.1
port            = 3306
datadir         = /var/lib/mysql
socket          = /var/run/mysqld/mysqld.sock

# Performance Tuning
key_buffer_size = 256M
max_allowed_packet = 256M
thread_stack = 512K
thread_cache_size = 50
max_connections = 500
table_open_cache = 4000
table_definition_cache = 2000

# InnoDB Settings (for 8GB RAM server)
innodb_buffer_pool_size = 4G
innodb_buffer_pool_instances = 4
innodb_log_file_size = 512M
innodb_log_buffer_size = 64M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT
innodb_file_per_table = 1
innodb_io_capacity = 2000
innodb_io_capacity_max = 4000
innodb_read_io_threads = 4
innodb_write_io_threads = 4

# Query Cache (deprecated in MySQL 8.0)
query_cache_type = 0
query_cache_size = 0

# Logging
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2
log_queries_not_using_indexes = 1

# Binary Log (for replication)
server-id = 1
log_bin = /var/log/mysql/mysql-bin.log
binlog_format = ROW
expire_logs_days = 7
max_binlog_size = 100M
sync_binlog = 1

# Character Set
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci

[mysql]
default-character-set = utf8mb4

[client]
default-character-set = utf8mb4
```

### Database Optimization Indexes
```sql
-- Critical Performance Indexes
CREATE INDEX idx_appointments_lookup ON appointments(company_id, date, status);
CREATE INDEX idx_calls_company_date ON calls(company_id, created_at);
CREATE INDEX idx_customers_phone ON customers(phone_number, company_id);
CREATE INDEX idx_staff_branch ON staff(branch_id, active);

-- Full-text search indexes
ALTER TABLE customers ADD FULLTEXT(name, email, notes);
ALTER TABLE appointments ADD FULLTEXT(notes, internal_notes);

-- Monitoring query performance
SELECT 
    digest_text,
    count_star,
    sum_timer_wait/1000000000000 as total_latency_sec,
    avg_timer_wait/1000000000 as avg_latency_ms
FROM performance_schema.events_statements_summary_by_digest
ORDER BY sum_timer_wait DESC
LIMIT 10;
```

### Backup Script
```bash
#!/bin/bash
# /usr/local/bin/backup-database.sh

# Configuration
BACKUP_DIR="/var/backups/mysql"
S3_BUCKET="s3://askproai-backups/database"
RETENTION_DAYS=30
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="askproai_db"
DB_USER="askproai_user"
DB_PASS="your_password"

# Create backup directory
mkdir -p $BACKUP_DIR

# Perform backup with compression
echo "Starting database backup..."
mysqldump \
    --single-transaction \
    --routines \
    --triggers \
    --events \
    --quick \
    --lock-tables=false \
    --user=$DB_USER \
    --password=$DB_PASS \
    $DB_NAME | gzip > $BACKUP_DIR/backup_$DATE.sql.gz

# Upload to S3
echo "Uploading to S3..."
aws s3 cp $BACKUP_DIR/backup_$DATE.sql.gz $S3_BUCKET/

# Clean old local backups
find $BACKUP_DIR -name "backup_*.sql.gz" -mtime +7 -delete

# Clean old S3 backups
aws s3 ls $S3_BUCKET/ | while read -r line; do
    createDate=$(echo $line | awk '{print $1" "$2}')
    createDate=$(date -d "$createDate" +%s)
    olderThan=$(date -d "$RETENTION_DAYS days ago" +%s)
    if [[ $createDate -lt $olderThan ]]; then
        fileName=$(echo $line | awk '{print $4}')
        aws s3 rm $S3_BUCKET/$fileName
    fi
done

echo "Backup completed: backup_$DATE.sql.gz"
```

---

## 🚀 Redis Configuration

### Redis Setup for Production
```bash
# /etc/redis/redis.conf

# Network
bind 127.0.0.1 ::1
protected-mode yes
port 6379
tcp-backlog 511
timeout 0
tcp-keepalive 300

# General
daemonize yes
supervised systemd
pidfile /var/run/redis/redis-server.pid
loglevel notice
logfile /var/log/redis/redis-server.log
databases 16

# Persistence
save 900 1
save 300 10
save 60 10000
stop-writes-on-bgsave-error yes
rdbcompression yes
rdbchecksum yes
dbfilename dump.rdb
dir /var/lib/redis

# AOF
appendonly yes
appendfilename "appendonly.aof"
appendfsync everysec
no-appendfsync-on-rewrite no
auto-aof-rewrite-percentage 100
auto-aof-rewrite-min-size 64mb

# Memory Management
maxmemory 2gb
maxmemory-policy allkeys-lru
maxmemory-samples 5

# Performance
lazyfree-lazy-eviction no
lazyfree-lazy-expire no
lazyfree-lazy-server-del no
replica-lazy-flush no

# Security
requirepass your_redis_password_here

# Clients
maxclients 10000

# Memory optimization
hash-max-ziplist-entries 512
hash-max-ziplist-value 64
list-max-ziplist-size -2
list-compress-depth 0
set-max-intset-entries 512
zset-max-ziplist-entries 128
zset-max-ziplist-value 64
```

### Redis Sentinel for HA
```bash
# /etc/redis/sentinel.conf
port 26379
daemonize yes
pidfile /var/run/redis/redis-sentinel.pid
logfile /var/log/redis/redis-sentinel.log

sentinel monitor mymaster 127.0.0.1 6379 2
sentinel auth-pass mymaster your_redis_password_here
sentinel down-after-milliseconds mymaster 5000
sentinel parallel-syncs mymaster 1
sentinel failover-timeout mymaster 10000
```

---

## ⚖️ Load Balancing

### HAProxy Configuration
```haproxy
# /etc/haproxy/haproxy.cfg
global
    log /dev/log local0
    log /dev/log local1 notice
    chroot /var/lib/haproxy
    stats socket /run/haproxy/admin.sock mode 660 level admin
    stats timeout 30s
    user haproxy
    group haproxy
    daemon
    
    # Performance tuning
    maxconn 4096
    nbproc 1
    nbthread 4
    cpu-map auto:1/1-4 0-3
    
    # SSL tuning
    tune.ssl.default-dh-param 2048
    ssl-default-bind-ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256
    ssl-default-bind-options ssl-min-ver TLSv1.2

defaults
    log     global
    mode    http
    option  httplog
    option  dontlognull
    option  forwardfor
    option  http-server-close
    timeout connect 5000
    timeout client  50000
    timeout server  50000
    errorfile 400 /etc/haproxy/errors/400.http
    errorfile 403 /etc/haproxy/errors/403.http
    errorfile 408 /etc/haproxy/errors/408.http
    errorfile 500 /etc/haproxy/errors/500.http
    errorfile 502 /etc/haproxy/errors/502.http
    errorfile 503 /etc/haproxy/errors/503.http
    errorfile 504 /etc/haproxy/errors/504.http

# Statistics
stats enable
stats uri /haproxy?stats
stats realm Haproxy\ Statistics
stats auth admin:secure_password_here

# Frontend
frontend askproai_frontend
    bind *:80
    bind *:443 ssl crt /etc/ssl/certs/askproai.pem
    redirect scheme https if !{ ssl_fc }
    
    # Rate limiting
    stick-table type ip size 100k expire 30s store http_req_rate(10s)
    http-request track-sc0 src
    http-request deny if { sc_http_req_rate(0) gt 100 }
    
    # HSTS
    http-response set-header Strict-Transport-Security "max-age=63072000; includeSubDomains; preload"
    
    # ACLs
    acl is_websocket hdr(Upgrade) -i WebSocket
    acl is_api path_beg /api
    acl is_admin path_beg /admin
    
    # Use backends
    use_backend websocket_backend if is_websocket
    use_backend api_backend if is_api
    default_backend web_backend

# Backend - Web
backend web_backend
    balance roundrobin
    option httpchk GET /health
    http-check expect status 200
    
    # Session persistence
    cookie SERVERID insert indirect nocache
    
    server web1 10.0.1.10:80 check cookie web1 weight 100
    server web2 10.0.1.11:80 check cookie web2 weight 100
    server web3 10.0.1.12:80 check cookie web3 weight 100 backup

# Backend - API
backend api_backend
    balance leastconn
    option httpchk GET /api/health
    
    # Retry policy
    retry-on all-retryable-errors
    retries 3
    
    server api1 10.0.1.20:80 check weight 100
    server api2 10.0.1.21:80 check weight 100

# Backend - WebSocket
backend websocket_backend
    balance source
    option http-server-close
    option forceclose
    
    server ws1 10.0.1.30:6001 check weight 100
    server ws2 10.0.1.31:6001 check weight 100
```

---

## 🔒 SSL/TLS Management

### Let's Encrypt with Certbot
```bash
#!/bin/bash
# /usr/local/bin/ssl-management.sh

# Install certbot
apt-get update
apt-get install -y certbot python3-certbot-nginx

# Obtain certificate
certbot certonly \
    --nginx \
    --non-interactive \
    --agree-tos \
    --email admin@askproai.de \
    -d api.askproai.de \
    -d www.askproai.de \
    -d monitoring.askproai.de

# Auto-renewal cron
echo "0 0,12 * * * root certbot renew --quiet --post-hook 'systemctl reload nginx'" >> /etc/crontab

# Certificate monitoring
cat > /usr/local/bin/check-ssl-expiry.sh << 'EOF'
#!/bin/bash
DOMAINS=("api.askproai.de" "www.askproai.de")
WARNING_DAYS=30

for domain in "${DOMAINS[@]}"; do
    expiry_date=$(echo | openssl s_client -servername $domain -connect $domain:443 2>/dev/null | openssl x509 -noout -dates | grep notAfter | cut -d= -f2)
    expiry_epoch=$(date -d "$expiry_date" +%s)
    current_epoch=$(date +%s)
    days_left=$(( ($expiry_epoch - $current_epoch) / 86400 ))
    
    if [ $days_left -lt $WARNING_DAYS ]; then
        echo "WARNING: SSL certificate for $domain expires in $days_left days"
        # Send alert
        php /var/www/api-gateway/artisan alerts:send \
            --severity=warning \
            --title="SSL Certificate Expiry Warning" \
            --message="Certificate for $domain expires in $days_left days"
    fi
done
EOF

chmod +x /usr/local/bin/check-ssl-expiry.sh
echo "0 9 * * * root /usr/local/bin/check-ssl-expiry.sh" >> /etc/crontab
```

### SSL Security Headers
```nginx
# Strong SSL Security Configuration
ssl_protocols TLSv1.2 TLSv1.3;
ssl_ciphers 'ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305:DHE-RSA-AES128-GCM-SHA256:DHE-RSA-AES256-GCM-SHA384';
ssl_prefer_server_ciphers off;
ssl_dhparam /etc/nginx/dhparam.pem;
ssl_ecdh_curve secp384r1;
ssl_session_timeout 1d;
ssl_session_cache shared:SSL:50m;
ssl_session_tickets off;
ssl_stapling on;
ssl_stapling_verify on;
resolver 8.8.8.8 8.8.4.4 valid=300s;
resolver_timeout 5s;
```

---

## 🌍 CDN Configuration

### CloudFlare Setup
```yaml
# CloudFlare Configuration
Zone Settings:
  SSL/TLS:
    Mode: Full (strict)
    Min TLS Version: 1.2
    Opportunistic Encryption: On
    TLS 1.3: On
    Automatic HTTPS Rewrites: On
    
  Security:
    Security Level: Medium
    Challenge Passage: 30 minutes
    Browser Integrity Check: On
    
  Performance:
    Auto Minify: HTML, CSS, JS
    Brotli: On
    Rocket Loader: Off  # Can break Laravel
    
  Caching:
    Caching Level: Standard
    Browser Cache TTL: 4 hours
    
Page Rules:
  # API endpoints - no cache
  - URL: api.askproai.de/api/*
    Settings:
      Cache Level: Bypass
      Disable Performance
      
  # Static assets - aggressive cache
  - URL: api.askproai.de/build/*
    Settings:
      Cache Level: Cache Everything
      Edge Cache TTL: 1 month
      Browser Cache TTL: 1 month
      
  # Admin panel - no cache
  - URL: api.askproai.de/admin/*
    Settings:
      Cache Level: Bypass
      Disable Performance
      Security Level: High
```

### CDN Cache Headers
```php
// app/Http/Middleware/CdnHeaders.php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CdnHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        // Versioned assets can be cached forever
        if (preg_match('/\.(js|css|woff2?|ttf|eot|svg|png|jpg|jpeg|gif|ico)$/', $request->path())) {
            $response->header('Cache-Control', 'public, max-age=31536000, immutable');
            $response->header('CDN-Cache-Control', 'max-age=31536000');
        }
        
        // API responses should not be cached by CDN
        if ($request->is('api/*')) {
            $response->header('Cache-Control', 'no-cache, private');
            $response->header('CDN-Cache-Control', 'no-cache');
        }
        
        // HTML should be cached briefly
        if ($response->headers->get('Content-Type') === 'text/html') {
            $response->header('Cache-Control', 'public, max-age=300, must-revalidate');
            $response->header('CDN-Cache-Control', 'max-age=3600');
        }
        
        return $response;
    }
}
```

---

## 💾 Backup Strategies

### Comprehensive Backup Solution
```bash
#!/bin/bash
# /usr/local/bin/askproai-backup.sh

# Configuration
BACKUP_ROOT="/var/backups/askproai"
S3_BUCKET="s3://askproai-backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="$BACKUP_ROOT/$TIMESTAMP"
RETENTION_LOCAL=7    # days
RETENTION_S3=30      # days
RETENTION_GLACIER=365 # days

# Notification settings
SLACK_WEBHOOK="https://hooks.slack.com/services/YOUR/WEBHOOK/URL"
ADMIN_EMAIL="admin@askproai.de"

# Create backup directory
mkdir -p "$BACKUP_DIR"

# Function to send notifications
notify() {
    local status=$1
    local message=$2
    
    # Slack notification
    curl -X POST -H 'Content-type: application/json' \
        --data "{\"text\":\"Backup $status: $message\"}" \
        "$SLACK_WEBHOOK" 2>/dev/null || true
    
    # Email notification
    echo "$message" | mail -s "AskProAI Backup $status" "$ADMIN_EMAIL"
}

# Start backup
echo "Starting AskProAI backup at $TIMESTAMP"
notify "Started" "Backup process initiated at $TIMESTAMP"

# 1. Database Backup
echo "Backing up database..."
mysqldump \
    --single-transaction \
    --routines \
    --triggers \
    --events \
    --quick \
    --lock-tables=false \
    --user=askproai_user \
    --password='your_password' \
    askproai_db | gzip -9 > "$BACKUP_DIR/database.sql.gz"

# Verify database backup
if ! gunzip -t "$BACKUP_DIR/database.sql.gz" 2>/dev/null; then
    notify "Failed" "Database backup verification failed"
    exit 1
fi

# 2. Application Files Backup
echo "Backing up application files..."
tar -czf "$BACKUP_DIR/application.tar.gz" \
    -C /var/www/api-gateway \
    --exclude='vendor' \
    --exclude='node_modules' \
    --exclude='storage/framework/cache/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/logs/*' \
    --exclude='storage/debugbar/*' \
    --exclude='bootstrap/cache/*' \
    .

# 3. Upload to S3
echo "Uploading to S3..."
aws s3 cp "$BACKUP_DIR" "$S3_BUCKET/daily/$TIMESTAMP" --recursive

# 4. Create weekly backup on Sundays
if [ $(date +%w) -eq 0 ]; then
    echo "Creating weekly backup..."
    aws s3 cp "$S3_BUCKET/daily/$TIMESTAMP" "$S3_BUCKET/weekly/$TIMESTAMP" --recursive
fi

# 5. Create monthly backup on 1st
if [ $(date +%d) -eq 01 ]; then
    echo "Creating monthly backup..."
    aws s3 cp "$S3_BUCKET/daily/$TIMESTAMP" "$S3_BUCKET/monthly/$TIMESTAMP" --recursive
    
    # Move to Glacier after 30 days
    aws s3api put-bucket-lifecycle-configuration \
        --bucket askproai-backups \
        --lifecycle-configuration file:///etc/aws/glacier-lifecycle.json
fi

# 6. Cleanup old backups
echo "Cleaning up old backups..."

# Local cleanup
find "$BACKUP_ROOT" -type d -mtime +$RETENTION_LOCAL -exec rm -rf {} +

# S3 cleanup
aws s3 ls "$S3_BUCKET/daily/" | while read -r line; do
    createDate=$(echo $line | awk '{print $1" "$2}')
    createDate=$(date -d "$createDate" +%s)
    olderThan=$(date -d "$RETENTION_S3 days ago" +%s)
    if [[ $createDate -lt $olderThan ]]; then
        folderName=$(echo $line | awk '{print $4}')
        aws s3 rm "$S3_BUCKET/daily/$folderName" --recursive
    fi
done

# 7. Backup verification
echo "Verifying backup..."
BACKUP_SIZE=$(du -sh "$BACKUP_DIR" | cut -f1)
DB_SIZE=$(stat -c%s "$BACKUP_DIR/database.sql.gz")
APP_SIZE=$(stat -c%s "$BACKUP_DIR/application.tar.gz")

if [ $DB_SIZE -lt 1000000 ]; then  # Less than 1MB
    notify "Warning" "Database backup suspiciously small: $DB_SIZE bytes"
fi

# 8. Generate backup report
cat > "$BACKUP_DIR/backup-report.txt" << EOF
AskProAI Backup Report
=====================
Timestamp: $TIMESTAMP
Total Size: $BACKUP_SIZE
Database Size: $(numfmt --to=iec-i --suffix=B $DB_SIZE)
Application Size: $(numfmt --to=iec-i --suffix=B $APP_SIZE)
S3 Upload: Success
Retention: Local=$RETENTION_LOCAL days, S3=$RETENTION_S3 days, Glacier=$RETENTION_GLACIER days
EOF

# Upload report
aws s3 cp "$BACKUP_DIR/backup-report.txt" "$S3_BUCKET/daily/$TIMESTAMP/"

# Success notification
notify "Completed" "Backup successful: $BACKUP_SIZE backed up to S3"

echo "Backup completed successfully!"
```

### Disaster Recovery Testing
```bash
#!/bin/bash
# /usr/local/bin/dr-test.sh

# Monthly disaster recovery test
echo "Starting disaster recovery test..."

# 1. Create test database
mysql -u root -p -e "CREATE DATABASE askproai_dr_test;"

# 2. Restore latest backup
LATEST_BACKUP=$(aws s3 ls s3://askproai-backups/daily/ | tail -1 | awk '{print $4}')
aws s3 cp "s3://askproai-backups/daily/$LATEST_BACKUP/database.sql.gz" /tmp/

# 3. Import to test database
gunzip < /tmp/database.sql.gz | mysql -u root -p askproai_dr_test

# 4. Run verification queries
mysql -u root -p askproai_dr_test -e "
    SELECT COUNT(*) as appointments FROM appointments;
    SELECT COUNT(*) as customers FROM customers;
    SELECT COUNT(*) as calls FROM calls;
    SELECT MAX(created_at) as latest_activity FROM appointments;
"

# 5. Cleanup
mysql -u root -p -e "DROP DATABASE askproai_dr_test;"
rm -f /tmp/database.sql.gz

echo "Disaster recovery test completed"
```

---

## 🛡️ Security Hardening

### System Security Checklist
```bash
#!/bin/bash
# /usr/local/bin/security-hardening.sh

# 1. OS Updates
apt-get update && apt-get upgrade -y
apt-get install -y unattended-upgrades fail2ban ufw

# 2. Configure automatic updates
dpkg-reconfigure -plow unattended-upgrades

# 3. SSH Hardening
cat >> /etc/ssh/sshd_config << EOF
# Security hardening
PermitRootLogin no
PasswordAuthentication no
PubkeyAuthentication yes
MaxAuthTries 3
MaxSessions 3
ClientAliveInterval 300
ClientAliveCountMax 2
X11Forwarding no
AllowUsers deploy admin
Protocol 2
EOF

systemctl restart sshd

# 4. Firewall Setup
ufw default deny incoming
ufw default allow outgoing
ufw allow 22/tcp comment 'SSH'
ufw allow 80/tcp comment 'HTTP'
ufw allow 443/tcp comment 'HTTPS'
ufw --force enable

# 5. Fail2Ban Configuration
cat > /etc/fail2ban/jail.local << EOF
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 5

[sshd]
enabled = true
port = ssh
filter = sshd
logpath = /var/log/auth.log

[nginx-http-auth]
enabled = true
filter = nginx-http-auth
port = http,https
logpath = /var/log/nginx/error.log

[nginx-noscript]
enabled = true
port = http,https
filter = nginx-noscript
logpath = /var/log/nginx/access.log
maxretry = 6

[nginx-badbots]
enabled = true
port = http,https
filter = nginx-badbots
logpath = /var/log/nginx/access.log
maxretry = 2

[nginx-noproxy]
enabled = true
port = http,https
filter = nginx-noproxy
logpath = /var/log/nginx/access.log
maxretry = 2
EOF

systemctl restart fail2ban

# 6. System Hardening
# Disable unused network protocols
echo "install dccp /bin/true" >> /etc/modprobe.d/blacklist-rare-network.conf
echo "install sctp /bin/true" >> /etc/modprobe.d/blacklist-rare-network.conf
echo "install rds /bin/true" >> /etc/modprobe.d/blacklist-rare-network.conf
echo "install tipc /bin/true" >> /etc/modprobe.d/blacklist-rare-network.conf

# Kernel hardening
cat >> /etc/sysctl.d/99-security.conf << EOF
# IP Spoofing protection
net.ipv4.conf.all.rp_filter = 1
net.ipv4.conf.default.rp_filter = 1

# Ignore ICMP redirects
net.ipv4.conf.all.accept_redirects = 0
net.ipv6.conf.all.accept_redirects = 0

# Ignore send redirects
net.ipv4.conf.all.send_redirects = 0

# Disable source packet routing
net.ipv4.conf.all.accept_source_route = 0
net.ipv6.conf.all.accept_source_route = 0

# Log Martians
net.ipv4.conf.all.log_martians = 1

# Ignore ICMP ping requests
net.ipv4.icmp_echo_ignore_broadcasts = 1

# Ignore Directed pings
net.ipv4.icmp_ignore_bogus_error_responses = 1

# Enable TCP/IP SYN cookies
net.ipv4.tcp_syncookies = 1
net.ipv4.tcp_max_syn_backlog = 2048
net.ipv4.tcp_synack_retries = 2

# Disable IPv6 if not used
net.ipv6.conf.all.disable_ipv6 = 1
net.ipv6.conf.default.disable_ipv6 = 1
EOF

sysctl -p /etc/sysctl.d/99-security.conf

# 7. File System Security
# Secure shared memory
echo "tmpfs /run/shm tmpfs defaults,noexec,nosuid 0 0" >> /etc/fstab

# Set proper permissions
find /var/www/api-gateway -type d -exec chmod 755 {} \;
find /var/www/api-gateway -type f -exec chmod 644 {} \;
chown -R www-data:www-data /var/www/api-gateway
chmod -R 775 /var/www/api-gateway/storage
chmod -R 775 /var/www/api-gateway/bootstrap/cache

# 8. Install security tools
apt-get install -y \
    rkhunter \
    chkrootkit \
    aide \
    auditd \
    apparmor \
    apparmor-utils

# Initialize AIDE
aideinit
mv /var/lib/aide/aide.db.new /var/lib/aide/aide.db

# 9. Setup audit logging
cat > /etc/audit/rules.d/askproai.rules << EOF
# Monitor authentication
-w /var/log/auth.log -p wa -k authentication
-w /etc/passwd -p wa -k passwd_changes
-w /etc/group -p wa -k group_changes
-w /etc/shadow -p wa -k shadow_changes

# Monitor system calls
-a always,exit -F arch=b64 -S execve -k commands
-a always,exit -F arch=b64 -S socket -S connect -k network

# Monitor file operations
-w /var/www/api-gateway/.env -p rwa -k env_changes
-w /var/www/api-gateway/config/ -p wa -k config_changes
EOF

systemctl restart auditd

echo "Security hardening completed!"
```

### Application Security
```php
// config/security.php
<?php

return [
    // Security headers
    'headers' => [
        'X-Frame-Options' => 'DENY',
        'X-Content-Type-Options' => 'nosniff',
        'X-XSS-Protection' => '1; mode=block',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com;",
        'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
        'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains; preload',
    ],
    
    // Rate limiting
    'rate_limits' => [
        'api' => '60:1',           // 60 requests per minute
        'auth' => '5:1',           // 5 auth attempts per minute
        'webhooks' => '1000:1',    // 1000 webhook requests per minute
    ],
    
    // IP Whitelist for admin
    'admin_whitelist' => env('ADMIN_IP_WHITELIST', ''),
    
    // Session security
    'session' => [
        'secure' => true,
        'httponly' => true,
        'same_site' => 'strict',
        'lifetime' => 120,  // minutes
        'expire_on_close' => true,
    ],
    
    // Password policy
    'password' => [
        'min_length' => 12,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_numbers' => true,
        'require_symbols' => true,
        'check_pwned' => true,
    ],
];
```

---

## 🚀 Performance Optimization

### PHP-FPM Optimization
```ini
; /etc/php/8.3/fpm/pool.d/www.conf

[www]
user = www-data
group = www-data
listen = /run/php/php8.3-fpm.sock
listen.owner = www-data
listen.group = www-data

; Process Management
pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests = 1000
pm.process_idle_timeout = 10s

; Performance tuning
request_terminate_timeout = 300
request_slowlog_timeout = 5s
slowlog = /var/log/php/slow.log

; PHP settings
php_admin_value[memory_limit] = 256M
php_admin_value[max_execution_time] = 60
php_admin_value[post_max_size] = 50M
php_admin_value[upload_max_filesize] = 50M
php_admin_value[max_input_vars] = 5000
php_admin_value[max_input_time] = 60

; OPcache settings
php_admin_value[opcache.enable] = 1
php_admin_value[opcache.memory_consumption] = 256
php_admin_value[opcache.interned_strings_buffer] = 64
php_admin_value[opcache.max_accelerated_files] = 32531
php_admin_value[opcache.revalidate_freq] = 0
php_admin_value[opcache.fast_shutdown] = 1
php_admin_value[opcache.enable_cli] = 1
php_admin_value[opcache.validate_timestamps] = 0
```

### Laravel Performance Commands
```bash
#!/bin/bash
# /usr/local/bin/optimize-laravel.sh

cd /var/www/api-gateway

# Clear everything first
php artisan optimize:clear

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Optimize composer autoloader
composer dump-autoload -o

# Optimize database
php artisan db:optimize

# Preload frequently used data
php artisan cache:warm

# Generate optimized class map
php artisan optimize

# Compile assets
npm run production

# Clear OPcache
if command -v cachetool &> /dev/null; then
    cachetool opcache:reset --fcgi=/var/run/php/php8.3-fpm.sock
fi

echo "Laravel optimization completed!"
```

### Database Query Optimization
```sql
-- Find slow queries
SELECT 
    query_time,
    lock_time,
    rows_sent,
    rows_examined,
    sql_text
FROM mysql.slow_log
WHERE query_time > 1
ORDER BY query_time DESC
LIMIT 20;

-- Check missing indexes
SELECT 
    t.TABLE_SCHEMA,
    t.TABLE_NAME,
    s.INDEX_NAME,
    s.COLUMN_NAME,
    s.SEQ_IN_INDEX,
    s.CARDINALITY
FROM INFORMATION_SCHEMA.TABLES t
LEFT JOIN INFORMATION_SCHEMA.STATISTICS s 
    ON t.TABLE_SCHEMA = s.TABLE_SCHEMA 
    AND t.TABLE_NAME = s.TABLE_NAME
WHERE t.TABLE_SCHEMA = 'askproai_db'
    AND t.TABLE_ROWS > 1000
    AND s.INDEX_NAME IS NULL
ORDER BY t.TABLE_ROWS DESC;

-- Optimize tables
OPTIMIZE TABLE appointments;
OPTIMIZE TABLE calls;
OPTIMIZE TABLE customers;
ANALYZE TABLE appointments;
ANALYZE TABLE calls;
ANALYZE TABLE customers;
```

---

## 📊 Monitoring Setup

### Prometheus Configuration
```yaml
# /etc/prometheus/prometheus.yml
global:
  scrape_interval: 15s
  evaluation_interval: 15s
  external_labels:
    monitor: 'askproai-prod'

alerting:
  alertmanagers:
    - static_configs:
        - targets: ['localhost:9093']

rule_files:
  - "alerts/*.yml"

scrape_configs:
  # Application metrics
  - job_name: 'askproai'
    static_configs:
      - targets: ['localhost:80']
    metrics_path: '/api/metrics'
    bearer_token: 'your-metrics-token'
    
  # Node exporter
  - job_name: 'node'
    static_configs:
      - targets: ['localhost:9100']
      
  # MySQL exporter
  - job_name: 'mysql'
    static_configs:
      - targets: ['localhost:9104']
      
  # Redis exporter
  - job_name: 'redis'
    static_configs:
      - targets: ['localhost:9121']
      
  # Nginx exporter
  - job_name: 'nginx'
    static_configs:
      - targets: ['localhost:9113']
```

### Alert Rules
```yaml
# /etc/prometheus/alerts/askproai.yml
groups:
  - name: askproai_alerts
    rules:
      # High error rate
      - alert: HighErrorRate
        expr: rate(askproai_http_requests_total{status=~"5.."}[5m]) > 0.05
        for: 5m
        labels:
          severity: critical
        annotations:
          summary: "High error rate detected"
          description: "Error rate is {{ $value | humanizePercentage }} for the last 5 minutes"
          
      # Slow response time
      - alert: SlowResponseTime
        expr: histogram_quantile(0.95, rate(askproai_http_request_duration_seconds_bucket[5m])) > 1
        for: 10m
        labels:
          severity: warning
        annotations:
          summary: "Slow response times"
          description: "95th percentile response time is {{ $value }}s"
          
      # Queue backlog
      - alert: QueueBacklog
        expr: askproai_queue_size{queue="default"} > 1000
        for: 5m
        labels:
          severity: warning
        annotations:
          summary: "Large queue backlog"
          description: "Queue {{ $labels.queue }} has {{ $value }} jobs pending"
          
      # Database connections
      - alert: DatabaseConnectionsHigh
        expr: mysql_global_status_threads_connected / mysql_global_variables_max_connections > 0.8
        for: 5m
        labels:
          severity: warning
        annotations:
          summary: "Database connections near limit"
          description: "{{ $value | humanizePercentage }} of max connections in use"
          
      # Disk space
      - alert: DiskSpaceLow
        expr: (node_filesystem_avail_bytes{mountpoint="/"} / node_filesystem_size_bytes{mountpoint="/"}) < 0.15
        for: 5m
        labels:
          severity: critical
        annotations:
          summary: "Low disk space"
          description: "Only {{ $value | humanizePercentage }} disk space remaining"
          
      # Memory usage
      - alert: HighMemoryUsage
        expr: (1 - (node_memory_MemAvailable_bytes / node_memory_MemTotal_bytes)) > 0.85
        for: 10m
        labels:
          severity: warning
        annotations:
          summary: "High memory usage"
          description: "Memory usage is {{ $value | humanizePercentage }}"
          
      # SSL certificate expiry
      - alert: SSLCertificateExpiringSoon
        expr: probe_ssl_earliest_cert_expiry - time() < 86400 * 30
        for: 1h
        labels:
          severity: warning
        annotations:
          summary: "SSL certificate expiring soon"
          description: "SSL certificate expires in {{ $value | humanizeDuration }}"
```

### Grafana Dashboard
```json
{
  "dashboard": {
    "title": "AskProAI Production Dashboard",
    "panels": [
      {
        "title": "Request Rate",
        "targets": [
          {
            "expr": "rate(askproai_http_requests_total[5m])",
            "legendFormat": "{{method}} {{route}}"
          }
        ]
      },
      {
        "title": "Response Time (95th percentile)",
        "targets": [
          {
            "expr": "histogram_quantile(0.95, rate(askproai_http_request_duration_seconds_bucket[5m]))"
          }
        ]
      },
      {
        "title": "Error Rate",
        "targets": [
          {
            "expr": "rate(askproai_http_requests_total{status=~\"5..\"}[5m])"
          }
        ]
      },
      {
        "title": "Active Calls",
        "targets": [
          {
            "expr": "askproai_active_calls"
          }
        ]
      },
      {
        "title": "Queue Sizes",
        "targets": [
          {
            "expr": "askproai_queue_size",
            "legendFormat": "{{queue}}"
          }
        ]
      },
      {
        "title": "Database Queries/sec",
        "targets": [
          {
            "expr": "rate(mysql_global_status_queries[5m])"
          }
        ]
      }
    ]
  }
}
```

---

## 🔥 Disaster Recovery

### Recovery Procedures
```bash
#!/bin/bash
# /usr/local/bin/disaster-recovery.sh

# Disaster Recovery Runbook
# Time to Recovery Target: 4 hours

case "$1" in
    "database-failure")
        echo "=== Database Failure Recovery ==="
        
        # 1. Stop application to prevent data corruption
        systemctl stop php8.3-fpm
        php artisan down
        
        # 2. Attempt to repair database
        mysqlcheck -u root -p --auto-repair --all-databases
        
        # 3. If repair fails, restore from backup
        if [ $? -ne 0 ]; then
            echo "Database repair failed, restoring from backup..."
            
            # Find latest backup
            LATEST_BACKUP=$(aws s3 ls s3://askproai-backups/daily/ --recursive | grep database.sql.gz | sort | tail -1 | awk '{print $4}')
            
            # Download and restore
            aws s3 cp "s3://askproai-backups/$LATEST_BACKUP" /tmp/restore.sql.gz
            gunzip < /tmp/restore.sql.gz | mysql -u root -p askproai_db
            
            # Replay binary logs if available
            LAST_BINLOG=$(mysql -u root -p -e "SHOW MASTER STATUS\G" | grep File | awk '{print $2}')
            mysqlbinlog /var/log/mysql/mysql-bin.* | mysql -u root -p askproai_db
        fi
        
        # 4. Verify database
        php artisan db:check
        
        # 5. Restart services
        systemctl start php8.3-fpm
        php artisan up
        ;;
        
    "server-failure")
        echo "=== Server Failure Recovery ==="
        
        # This would be run on a new server
        
        # 1. Provision new server
        echo "Provisioning new server..."
        
        # 2. Install dependencies
        apt-get update
        apt-get install -y nginx php8.3-fpm mysql-server redis-server
        
        # 3. Restore application
        aws s3 cp s3://askproai-backups/daily/latest/application.tar.gz /tmp/
        tar -xzf /tmp/application.tar.gz -C /var/www/api-gateway
        
        # 4. Restore database
        aws s3 cp s3://askproai-backups/daily/latest/database.sql.gz /tmp/
        gunzip < /tmp/database.sql.gz | mysql -u root -p askproai_db
        
        # 5. Update DNS
        echo "Update DNS records to point to new server IP"
        
        # 6. Install SSL certificates
        certbot certonly --nginx -d api.askproai.de -d www.askproai.de
        
        # 7. Start services
        systemctl start nginx php8.3-fpm redis-server mysql
        ;;
        
    "data-corruption")
        echo "=== Data Corruption Recovery ==="
        
        # 1. Identify corruption scope
        php artisan data:integrity-check
        
        # 2. Restore specific tables
        TABLE=$2
        if [ -n "$TABLE" ]; then
            # Extract specific table from backup
            aws s3 cp s3://askproai-backups/daily/latest/database.sql.gz /tmp/
            gunzip < /tmp/database.sql.gz | sed -n "/^-- Table structure for table \`$TABLE\`/,/^-- Table structure for table/p" | mysql -u root -p askproai_db
        fi
        
        # 3. Verify data integrity
        php artisan data:verify --table=$TABLE
        ;;
        
    "ransomware")
        echo "=== Ransomware Recovery ==="
        
        # 1. Isolate affected systems
        iptables -I INPUT -j DROP
        iptables -I OUTPUT -j DROP
        iptables -I INPUT -s 127.0.0.1 -j ACCEPT
        iptables -I OUTPUT -d 127.0.0.1 -j ACCEPT
        
        # 2. Preserve evidence
        dd if=/dev/sda of=/external/evidence.img bs=4M
        
        # 3. Wipe and reinstall
        echo "Manual intervention required: Reinstall OS from trusted media"
        
        # 4. Restore from clean backup
        echo "Restore from backup dated before infection"
        ;;
        
    *)
        echo "Usage: $0 {database-failure|server-failure|data-corruption|ransomware}"
        exit 1
        ;;
esac

# Send recovery notification
curl -X POST -H 'Content-type: application/json' \
    --data "{\"text\":\"Disaster recovery completed for: $1\"}" \
    "$SLACK_WEBHOOK"
```

### Backup Verification
```bash
#!/bin/bash
# /usr/local/bin/verify-backups.sh

# Weekly backup verification
echo "Starting backup verification..."

# Test database restore
TEMP_DB="askproai_backup_test"
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS $TEMP_DB;"

# Download latest backup
LATEST=$(aws s3 ls s3://askproai-backups/daily/ --recursive | grep database.sql.gz | sort | tail -1 | awk '{print $4}')
aws s3 cp "s3://askproai-backups/$LATEST" /tmp/test-restore.sql.gz

# Restore to test database
gunzip < /tmp/test-restore.sql.gz | mysql -u root -p $TEMP_DB

# Verify data
RESULT=$(mysql -u root -p $TEMP_DB -e "
    SELECT 
        (SELECT COUNT(*) FROM appointments) as appointments,
        (SELECT COUNT(*) FROM customers) as customers,
        (SELECT COUNT(*) FROM calls) as calls,
        (SELECT MAX(created_at) FROM appointments) as latest_appointment
" -s -N)

echo "Verification results: $RESULT"

# Cleanup
mysql -u root -p -e "DROP DATABASE $TEMP_DB;"
rm -f /tmp/test-restore.sql.gz

# Alert if issues found
if [ -z "$RESULT" ]; then
    echo "CRITICAL: Backup verification failed!" | mail -s "Backup Verification Failed" admin@askproai.de
fi
```

---

## 📈 Scaling Strategies

### Vertical Scaling Plan
```yaml
Current (4 vCPU, 8GB RAM):
  Capacity: 500-1000 concurrent users
  Calls/day: 2,000
  Database: Same server
  
Next Step (8 vCPU, 16GB RAM):
  Capacity: 1000-2500 concurrent users
  Calls/day: 5,000
  Database: Same server (optimized)
  Cost increase: +€50/month
  
Scale Trigger Points:
  - CPU usage > 70% sustained
  - Memory usage > 85%
  - Response time > 500ms (p95)
  - Queue backlog > 1000 jobs
```

### Horizontal Scaling Architecture
```yaml
Phase 1 - Database Separation:
  - Move database to dedicated server
  - Add read replica for reports
  - Cost: +€80/month
  
Phase 2 - Load Balancing:
  - Add HAProxy load balancer
  - 2x application servers
  - Shared Redis for sessions
  - Cost: +€150/month
  
Phase 3 - Full HA Setup:
  - 3x application servers
  - MySQL primary + 2 replicas
  - Redis Sentinel cluster
  - Dedicated queue workers
  - Cost: +€400/month
  
Phase 4 - Multi-Region:
  - Geographic distribution
  - CDN for all regions
  - Database replication across regions
  - Cost: +€800/month
```

### Auto-Scaling Configuration
```yaml
# Kubernetes HPA Example
apiVersion: autoscaling/v2
kind: HorizontalPodAutoscaler
metadata:
  name: askproai-hpa
spec:
  scaleTargetRef:
    apiVersion: apps/v1
    kind: Deployment
    name: askproai-web
  minReplicas: 2
  maxReplicas: 10
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
  behavior:
    scaleDown:
      stabilizationWindowSeconds: 300
      policies:
      - type: Percent
        value: 50
        periodSeconds: 60
    scaleUp:
      stabilizationWindowSeconds: 60
      policies:
      - type: Percent
        value: 100
        periodSeconds: 60
```

---

## 💰 Cost Optimization

### Current Infrastructure Costs
```yaml
Monthly Breakdown:
  Server (Netcup VPS 2000 G10): €13.19
  Domain: €1.00
  SSL: €0 (Let's Encrypt)
  Backup Storage (S3): ~€5
  Monitoring: €0 (Self-hosted)
  Total: ~€20/month
  
Annual: ~€240
```

### Cost Optimization Strategies
```bash
#!/bin/bash
# /usr/local/bin/cost-optimize.sh

# 1. Analyze resource usage
echo "=== Resource Usage Analysis ==="
echo "CPU Average: $(mpstat 1 5 | awk '/Average/ {print 100-$NF"%"}')"
echo "Memory Used: $(free -m | awk '/^Mem:/ {print $3"MB of "$2"MB"}')"
echo "Disk Used: $(df -h / | awk 'NR==2 {print $3" of "$2}')"

# 2. Identify oversized resources
if [ $(free -m | awk '/^Mem:/ {print ($3/$2)*100}' | cut -d. -f1) -lt 50 ]; then
    echo "WARNING: Memory usage below 50% - consider downsizing"
fi

# 3. Cleanup unnecessary files
find /var/log -name "*.gz" -mtime +30 -delete
find /tmp -type f -mtime +7 -delete
docker system prune -af --volumes

# 4. Optimize database
mysql -u root -p -e "
    SELECT 
        table_schema,
        SUM(data_length + index_length) / 1024 / 1024 AS 'Size (MB)'
    FROM information_schema.tables
    GROUP BY table_schema
    HAVING SUM(data_length + index_length) > 10 * 1024 * 1024
    ORDER BY SUM(data_length + index_length) DESC;
"

# 5. S3 lifecycle optimization
cat > /tmp/s3-lifecycle.json << EOF
{
    "Rules": [
        {
            "ID": "Archive old backups",
            "Status": "Enabled",
            "Transitions": [
                {
                    "Days": 30,
                    "StorageClass": "STANDARD_IA"
                },
                {
                    "Days": 90,
                    "StorageClass": "GLACIER"
                }
            ]
        }
    ]
}
EOF

aws s3api put-bucket-lifecycle-configuration \
    --bucket askproai-backups \
    --lifecycle-configuration file:///tmp/s3-lifecycle.json
```

### Reserved Instance Planning
```yaml
Provider Comparison:
  Netcup:
    Current: €13.19/month (no commitment)
    Annual: €131.88/year (12 month commitment, 20% discount)
    
  Hetzner Cloud:
    CX21: €5.83/month (2 vCPU, 4GB RAM)
    CX31: €10.59/month (4 vCPU, 8GB RAM)
    
  DigitalOcean:
    Basic Droplet: $24/month (4 vCPU, 8GB RAM)
    Reserved: $19.20/month (20% discount)
    
  AWS:
    t3.medium: ~$38/month on-demand
    Reserved (1 year): ~$27/month
    Reserved (3 year): ~$19/month
```

---

## 🔧 Troubleshooting Guide

### Common Issues and Solutions

#### High CPU Usage
```bash
# Identify CPU consumers
top -b -n 1 | head -20
ps aux --sort=-%cpu | head -10

# Check PHP-FPM processes
ps aux | grep php-fpm | wc -l

# Laravel specific
php artisan queue:monitor
php artisan horizon:status

# Fix: Increase PHP-FPM workers or optimize code
```

#### Memory Leaks
```bash
# Monitor memory usage
watch -n 5 'free -m'

# Find memory hungry processes
ps aux --sort=-%mem | head -10

# PHP memory usage
php -r "echo 'Memory limit: ' . ini_get('memory_limit') . PHP_EOL;"

# Laravel memory profiling
php artisan tinker
>>> memory_get_usage(true) / 1024 / 1024
>>> memory_get_peak_usage(true) / 1024 / 1024
```

#### Slow Database Queries
```sql
-- Enable slow query log
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 1;

-- Find slow queries
SELECT * FROM mysql.slow_log ORDER BY query_time DESC LIMIT 10;

-- Check table locks
SHOW OPEN TABLES WHERE In_use > 0;

-- Current processes
SHOW PROCESSLIST;

-- Kill long running query
KILL [process_id];
```

#### Queue Processing Issues
```bash
# Check queue status
php artisan queue:monitor

# Retry failed jobs
php artisan queue:retry all

# Clear failed jobs
php artisan queue:flush

# Check Redis
redis-cli ping
redis-cli INFO stats

# Monitor queue in real-time
php artisan queue:listen --tries=1 --timeout=30
```

#### 502 Bad Gateway
```bash
# Check PHP-FPM
systemctl status php8.3-fpm
journalctl -u php8.3-fpm -n 50

# Check Nginx
nginx -t
systemctl status nginx
tail -f /var/log/nginx/error.log

# Common fixes
systemctl restart php8.3-fpm
systemctl restart nginx

# Increase timeouts in Nginx
# Edit /etc/nginx/sites-available/askproai
fastcgi_read_timeout 300;
proxy_read_timeout 300;
```

#### SSL Certificate Issues
```bash
# Check certificate expiry
echo | openssl s_client -servername api.askproai.de -connect api.askproai.de:443 2>/dev/null | openssl x509 -noout -dates

# Renew certificate
certbot renew --dry-run  # Test first
certbot renew

# Force renewal
certbot renew --force-renewal

# Check Nginx SSL config
nginx -t
```

---

## 📜 Scripts & Automation

### Health Check Script
```bash
#!/bin/bash
# /usr/local/bin/health-check.sh

# AskProAI Comprehensive Health Check
# Run every 5 minutes via cron

WEBHOOK_URL="https://hooks.slack.com/services/YOUR/WEBHOOK"
ALERT_EMAIL="admin@askproai.de"

# Function to send alerts
send_alert() {
    local level=$1
    local message=$2
    
    # Slack
    curl -X POST -H 'Content-type: application/json' \
        --data "{\"text\":\"[$level] $message\"}" \
        "$WEBHOOK_URL" 2>/dev/null
    
    # Email for critical
    if [ "$level" == "CRITICAL" ]; then
        echo "$message" | mail -s "AskProAI Critical Alert" "$ALERT_EMAIL"
    fi
}

# Check web server
if ! curl -sf http://localhost/api/health > /dev/null; then
    send_alert "CRITICAL" "Web server health check failed"
fi

# Check database
if ! mysql -u askproai_user -p'password' -e "SELECT 1" > /dev/null 2>&1; then
    send_alert "CRITICAL" "Database connection failed"
fi

# Check Redis
if ! redis-cli ping > /dev/null 2>&1; then
    send_alert "CRITICAL" "Redis connection failed"
fi

# Check disk space
DISK_USAGE=$(df -h / | awk 'NR==2 {print $5}' | sed 's/%//')
if [ $DISK_USAGE -gt 85 ]; then
    send_alert "WARNING" "Disk usage high: ${DISK_USAGE}%"
fi

# Check memory
MEM_USAGE=$(free | awk 'NR==2 {print ($3/$2) * 100}' | cut -d. -f1)
if [ $MEM_USAGE -gt 85 ]; then
    send_alert "WARNING" "Memory usage high: ${MEM_USAGE}%"
fi

# Check CPU
CPU_USAGE=$(top -bn1 | grep "Cpu(s)" | awk '{print $2}' | cut -d'%' -f1)
if (( $(echo "$CPU_USAGE > 80" | bc -l) )); then
    send_alert "WARNING" "CPU usage high: ${CPU_USAGE}%"
fi

# Check queue size
QUEUE_SIZE=$(redis-cli llen queues:default 2>/dev/null || echo 0)
if [ $QUEUE_SIZE -gt 1000 ]; then
    send_alert "WARNING" "Queue backlog high: $QUEUE_SIZE jobs"
fi

# Check SSL expiry
DAYS_LEFT=$(echo | openssl s_client -servername api.askproai.de -connect api.askproai.de:443 2>/dev/null | openssl x509 -noout -dates | grep notAfter | cut -d= -f2 | xargs -I {} date -d {} +%s | xargs -I {} expr {} - $(date +%s) | xargs -I {} expr {} / 86400)
if [ $DAYS_LEFT -lt 30 ]; then
    send_alert "WARNING" "SSL certificate expires in $DAYS_LEFT days"
fi

echo "Health check completed at $(date)"
```

### Automated Deployment Script
```bash
#!/bin/bash
# /usr/local/bin/deploy-production.sh

set -e

# Configuration
APP_DIR="/var/www/api-gateway"
BACKUP_DIR="/var/backups/deployments"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

echo "Starting deployment at $TIMESTAMP"

# 1. Backup current state
mkdir -p "$BACKUP_DIR/$TIMESTAMP"
cp -r "$APP_DIR/.env" "$BACKUP_DIR/$TIMESTAMP/"
mysqldump -u askproai_user -p'password' askproai_db | gzip > "$BACKUP_DIR/$TIMESTAMP/database.sql.gz"

# 2. Pull latest code
cd "$APP_DIR"
git fetch origin
git reset --hard origin/main

# 3. Install dependencies
composer install --no-dev --optimize-autoloader
npm ci --production

# 4. Build assets
npm run production

# 5. Run migrations
php artisan migrate --force

# 6. Clear and rebuild caches
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 7. Restart services
sudo systemctl reload php8.3-fpm
php artisan queue:restart
php artisan horizon:terminate

# 8. Health check
sleep 5
if curl -sf http://localhost/api/health > /dev/null; then
    echo "Deployment successful!"
else
    echo "Health check failed! Rolling back..."
    # Rollback logic here
    exit 1
fi

# 9. Notify
curl -X POST -H 'Content-type: application/json' \
    --data '{"text":"Production deployment completed successfully"}' \
    "https://hooks.slack.com/services/YOUR/WEBHOOK"
```

### Performance Monitoring Script
```bash
#!/bin/bash
# /usr/local/bin/monitor-performance.sh

# Log file
LOG_FILE="/var/log/askproai/performance.log"
mkdir -p $(dirname $LOG_FILE)

# Collect metrics
{
    echo "=== Performance Report: $(date) ==="
    
    # Response times
    echo "Average response time:"
    tail -1000 /var/log/nginx/access.log | \
        awk '{sum+=$NF; count++} END {print sum/count " ms"}'
    
    # Request rate
    echo "Requests per minute:"
    tail -1000 /var/log/nginx/access.log | \
        awk '{print $4}' | \
        awk -F: '{print $1":"$2":"$3}' | \
        sort | uniq -c | tail -5
    
    # Database queries
    echo "Slow queries (last hour):"
    mysql -u root -p'password' -e "
        SELECT COUNT(*) as count, 
               AVG(query_time) as avg_time,
               MAX(query_time) as max_time
        FROM mysql.slow_log 
        WHERE start_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)
    "
    
    # Queue metrics
    echo "Queue sizes:"
    for queue in default webhooks emails; do
        size=$(redis-cli llen "queues:$queue" 2>/dev/null || echo 0)
        echo "  $queue: $size"
    done
    
    # Resource usage
    echo "Resource usage:"
    echo "  CPU: $(top -bn1 | grep "Cpu(s)" | awk '{print $2}')%"
    echo "  Memory: $(free -m | awk 'NR==2{printf "%s/%sMB (%.2f%%)\n", $3,$2,$3*100/$2}')"
    echo "  Disk: $(df -h / | awk 'NR==2 {print $3"/"$2" ("$5")"}')"
    
} >> $LOG_FILE

# Rotate log if too large
if [ $(stat -c%s "$LOG_FILE") -gt 10485760 ]; then  # 10MB
    mv "$LOG_FILE" "$LOG_FILE.$(date +%Y%m%d)"
    gzip "$LOG_FILE.$(date +%Y%m%d)"
fi
```

---

## 📚 Additional Resources

### Important Configuration Files
- `/etc/nginx/sites-available/askproai` - Nginx configuration
- `/etc/php/8.3/fpm/pool.d/www.conf` - PHP-FPM pool configuration
- `/etc/mysql/mysql.conf.d/mysqld.cnf` - MySQL configuration
- `/etc/redis/redis.conf` - Redis configuration
- `/var/www/api-gateway/.env` - Application environment

### Log File Locations
- Application logs: `/var/www/api-gateway/storage/logs/`
- Nginx logs: `/var/log/nginx/`
- PHP logs: `/var/log/php/`
- MySQL logs: `/var/log/mysql/`
- System logs: `/var/log/syslog`

### Monitoring URLs
- Health check: https://api.askproai.de/api/health
- Metrics: https://api.askproai.de/api/metrics (requires token)
- Horizon: https://api.askproai.de/horizon (requires auth)

### Emergency Contacts
- Server Provider: Netcup Support
- Domain Registrar: [Your registrar]
- SSL Issues: Let's Encrypt Community
- Application Support: admin@askproai.de

---

> **Note**: This infrastructure guide is a living document. Update it regularly as the infrastructure evolves.

**Last Infrastructure Audit**: 2025-07-10  
**Next Scheduled Review**: 2025-08-10  
**Document Version**: 1.0.0