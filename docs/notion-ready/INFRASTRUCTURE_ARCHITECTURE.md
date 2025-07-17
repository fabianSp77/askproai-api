# Infrastructure Architecture

## Server Setup

- **Provider**: Netcup
- **Server**: VPS 2000 G10
- **OS**: Ubuntu 22.04 LTS
- **Web Server**: Nginx + PHP-FPM 8.3
- **Database**: MariaDB 10.6
- **Cache**: Redis 7.0
- **Queue**: Laravel Horizon

## Directory Structure

```
/var/www/api-gateway/          # Main application
├── app/                       # Application code
├── config/                    # Configuration files
├── database/                  # Migrations and seeds
├── public/                    # Web root
├── resources/                 # Views and assets
├── routes/                    # Application routes
├── storage/                   # Application storage
└── vendor/                    # Composer dependencies
```

## Network Architecture

```
[Internet]
    |
[Cloudflare]
    |
[Nginx]
    |
[PHP-FPM]
    |
[Laravel App]
    |     |
[Redis] [MariaDB]
```

## Security Layers

1. **Cloudflare**: DDoS protection, WAF
2. **Firewall**: UFW with strict rules
3. **SSL**: Let's Encrypt certificates
4. **Application**: Laravel security middleware