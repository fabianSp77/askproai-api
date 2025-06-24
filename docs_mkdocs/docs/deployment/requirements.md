# System Requirements

## Overview

This document outlines the hardware, software, and service requirements for deploying AskProAI in production.

## Hardware Requirements

### Minimum Requirements (Up to 100 concurrent users)
```yaml
CPU: 4 cores (2.4 GHz or higher)
RAM: 8 GB
Storage: 50 GB SSD
Network: 100 Mbps
```

### Recommended Requirements (Up to 500 concurrent users)
```yaml
CPU: 8 cores (3.0 GHz or higher)
RAM: 16 GB
Storage: 200 GB SSD
Network: 1 Gbps
Database: Dedicated server with 16 GB RAM
```

### Enterprise Requirements (500+ concurrent users)
```yaml
Web Servers: 2+ instances with 8 cores, 16 GB RAM each
Database: Dedicated cluster with primary + read replicas
Cache: Dedicated Redis cluster (16 GB RAM)
Storage: 500 GB+ SSD with automatic scaling
Network: 10 Gbps with DDoS protection
Load Balancer: Hardware or cloud-based (e.g., AWS ALB)
```

## Software Requirements

### Operating System
```bash
# Supported OS
- Ubuntu 20.04 LTS or 22.04 LTS (recommended)
- Debian 11 or 12
- CentOS 8 Stream / Rocky Linux 8
- Amazon Linux 2

# Required packages
apt-get update
apt-get install -y \
    curl \
    git \
    unzip \
    software-properties-common \
    supervisor \
    nginx \
    certbot \
    python3-certbot-nginx
```

### PHP Requirements
```bash
# PHP 8.1+ with required extensions
PHP Version: 8.1 or higher (8.2 recommended)

Required Extensions:
- BCMath
- Ctype
- cURL
- DOM
- Fileinfo
- JSON
- Mbstring
- OpenSSL
- PCRE
- PDO
- PDO_MySQL
- Tokenizer
- XML
- Zip
- Redis
- GD or Imagick
- Intl

# Installation on Ubuntu/Debian
apt-get install -y \
    php8.2-fpm \
    php8.2-cli \
    php8.2-common \
    php8.2-mysql \
    php8.2-zip \
    php8.2-gd \
    php8.2-mbstring \
    php8.2-curl \
    php8.2-xml \
    php8.2-bcmath \
    php8.2-intl \
    php8.2-redis
```

### Database Requirements
```yaml
MySQL: 8.0+ or MariaDB 10.5+
Character Set: utf8mb4
Collation: utf8mb4_unicode_ci
Storage Engine: InnoDB

# Required settings (my.cnf)
[mysqld]
innodb_buffer_pool_size = 4G  # 70% of available RAM
innodb_file_per_table = 1
innodb_flush_log_at_trx_commit = 2
max_connections = 500
query_cache_size = 0  # Disabled in MySQL 8.0
slow_query_log = 1
long_query_time = 1
```

### Redis Requirements
```yaml
Version: 6.0+
Memory: Minimum 2GB, recommended 4GB+
Persistence: AOF enabled for critical data

# redis.conf
maxmemory 4gb
maxmemory-policy allkeys-lru
appendonly yes
appendfsync everysec
```

### Web Server
```nginx
# Nginx 1.18+
server {
    listen 80;
    server_name api.askproai.de;
    root /var/www/api-gateway/public;
    
    index index.php;
    
    charset utf-8;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }
    
    error_page 404 /index.php;
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## Node.js Requirements
```bash
# For asset compilation and real-time features
Node.js: 18.x LTS or 20.x LTS
NPM: 8.x or higher
Yarn: 1.22+ (optional)

# Installation
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
apt-get install -y nodejs
```

## External Service Requirements

### Required Services
```yaml
Retell.ai:
  - API Key
  - Agent ID
  - Webhook URL accessible from internet
  
Cal.com:
  - API Key (v2)
  - Team/Organization ID
  - Event Type IDs
  - Webhook secret

Email Service (one of):
  - SMTP credentials
  - Mailgun API key
  - SendGrid API key
  - Amazon SES credentials

SMS Service (one of):
  - Twilio account SID and auth token
  - MessageBird access key
  - Vonage API credentials
```

### Optional Services
```yaml
Stripe:
  - Publishable key
  - Secret key
  - Webhook endpoint secret
  - Product/Price IDs

Monitoring:
  - Sentry DSN
  - New Relic license key
  - Datadog API key

Storage:
  - AWS S3 or compatible (for backups)
  - CDN for static assets
```

## SSL/TLS Requirements
```bash
# SSL Certificate (one of)
- Let's Encrypt (free, auto-renewal)
- Commercial SSL certificate
- Cloudflare SSL (if using Cloudflare)

# Required for:
- Main application domain
- API endpoints
- Webhook endpoints
- Admin panel subdomain
```

## Network Requirements

### Firewall Rules
```bash
# Inbound
80/tcp    # HTTP (redirect to HTTPS)
443/tcp   # HTTPS
22/tcp    # SSH (restrict to management IPs)

# Outbound
443/tcp   # HTTPS (for API calls)
587/tcp   # SMTP (for email)
3306/tcp  # MySQL (if using remote database)
6379/tcp  # Redis (if using remote cache)
```

### Domain Configuration
```yaml
Required Domains:
  - api.askproai.de      # API endpoint
  - app.askproai.de      # Web application
  - admin.askproai.de    # Admin panel
  
DNS Records:
  - A record for each subdomain
  - SPF record for email sending
  - DKIM records (if using email service)
```

## Development Tools

### Required Tools
```bash
# Composer (PHP dependency manager)
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

# Git
apt-get install git

# Redis CLI
apt-get install redis-tools

# MySQL client
apt-get install mysql-client

# Process manager
apt-get install supervisor
```

### Optional Tools
```bash
# Laravel Envoy (for deployment)
composer global require laravel/envoy

# PHPUnit (for testing)
composer require --dev phpunit/phpunit

# Laravel Telescope (for debugging)
composer require laravel/telescope --dev

# PHP CS Fixer
composer require --dev friendsofphp/php-cs-fixer
```

## Performance Requirements

### Response Times
```yaml
API Endpoints:
  - 95th percentile: < 200ms
  - 99th percentile: < 500ms
  
Webhook Processing:
  - Acknowledgment: < 3 seconds
  - Full processing: < 30 seconds
  
Page Load:
  - First contentful paint: < 1.5s
  - Time to interactive: < 3s
```

### Throughput
```yaml
Minimum Capacity:
  - API requests: 1000 req/min
  - Concurrent calls: 100
  - Webhook processing: 500/min
  - Email sending: 100/min
  
Recommended Capacity:
  - API requests: 5000 req/min
  - Concurrent calls: 500
  - Webhook processing: 2000/min
  - Email sending: 1000/min
```

## Backup Requirements

### Backup Storage
```yaml
Local Backups:
  - Minimum: 7 days retention
  - Storage: 3x database size
  
Remote Backups:
  - Cloud storage (S3, B2, etc.)
  - Minimum: 30 days retention
  - Encrypted at rest
```

### Backup Schedule
```yaml
Database:
  - Full backup: Daily
  - Incremental: Hourly
  - Transaction logs: Continuous
  
Files:
  - User uploads: Daily
  - Application code: On deployment
  - Configuration: On change
```

## Monitoring Requirements

### System Monitoring
```yaml
Metrics to Monitor:
  - CPU usage (< 80%)
  - Memory usage (< 85%)
  - Disk usage (< 80%)
  - Network throughput
  - Database connections
  - Redis memory usage
  - Queue sizes
  - Error rates
```

### Application Monitoring
```yaml
Key Metrics:
  - Request rate
  - Response times
  - Error rates
  - Queue processing times
  - External API response times
  - Active user sessions
  - Failed job rate
```

## Security Requirements

### System Security
```bash
# Required security measures
- Firewall (ufw or iptables)
- Fail2ban for SSH protection
- Regular security updates
- SELinux or AppArmor (optional)
- File integrity monitoring

# User permissions
- Separate user for web server
- Restricted database user
- No root access for application
```

### Application Security
```yaml
Required:
  - HTTPS everywhere
  - CSRF protection
  - XSS protection
  - SQL injection prevention
  - Rate limiting
  - Input validation
  - Secure session handling
  
Recommended:
  - Web Application Firewall (WAF)
  - DDoS protection
  - Regular security audits
  - Penetration testing
```

## Scaling Considerations

### Horizontal Scaling
```yaml
Web Servers:
  - Load balancer required
  - Shared session storage (Redis)
  - Shared file storage (NFS/S3)
  
Database:
  - Read replicas for scaling reads
  - Primary-secondary replication
  - Connection pooling
  
Cache:
  - Redis Cluster or Sentinel
  - Memcached cluster (alternative)
```

### Vertical Scaling
```yaml
Upgrade Path:
  1. Increase RAM (easiest)
  2. Add CPU cores
  3. Upgrade to faster storage (NVMe)
  4. Increase network bandwidth
```

## Compliance Requirements

### GDPR Compliance
```yaml
Technical Requirements:
  - Data encryption at rest
  - Data encryption in transit
  - Access logging
  - Data retention policies
  - Right to deletion implementation
  - Data export capabilities
```

### Backup Compliance
```yaml
Requirements:
  - Encrypted backups
  - Access control
  - Audit logging
  - Retention policies
  - Secure disposal
```

## Pre-Installation Checklist

```markdown
## Infrastructure
- [ ] Server provisioned with required specifications
- [ ] Operating system installed and updated
- [ ] Network configured with static IP
- [ ] Domain names configured with DNS
- [ ] SSL certificates obtained

## Software
- [ ] PHP 8.1+ installed with required extensions
- [ ] MySQL/MariaDB installed and configured
- [ ] Redis installed and configured
- [ ] Nginx installed and configured
- [ ] Node.js installed
- [ ] Composer installed

## External Services
- [ ] Retell.ai account created and configured
- [ ] Cal.com account created and configured
- [ ] Email service configured
- [ ] SMS service configured (optional)
- [ ] Payment processing configured (optional)

## Security
- [ ] Firewall configured
- [ ] SSH key authentication enabled
- [ ] Fail2ban installed
- [ ] Backup strategy defined
- [ ] Monitoring solution chosen
```

## Related Documentation
- [Installation Guide](installation.md)
- [Production Deployment](production.md)
- [Performance Optimization](../operations/performance.md)