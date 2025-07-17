# Security Hardening Guide

## Firewall Configuration

```bash
# UFW Rules
ufw default deny incoming
ufw default allow outgoing
ufw allow 22/tcp     # SSH
ufw allow 80/tcp     # HTTP
ufw allow 443/tcp    # HTTPS
ufw enable
```

## SSH Hardening

```bash
# /etc/ssh/sshd_config
PermitRootLogin no
PasswordAuthentication no
PubkeyAuthentication yes
Port 22222  # Non-standard port
```

## Application Security

### Environment Variables
```bash
# Secure .env file
chmod 600 .env
chown www-data:www-data .env
```

### Directory Permissions
```bash
# Set proper permissions
chown -R www-data:www-data /var/www/api-gateway
find /var/www/api-gateway -type f -exec chmod 644 {} \;
find /var/www/api-gateway -type d -exec chmod 755 {} \;
chmod -R 775 storage bootstrap/cache
```

## Monitoring

### Fail2ban
```bash
# Install and configure
apt install fail2ban
cp /etc/fail2ban/jail.conf /etc/fail2ban/jail.local
# Configure for SSH, Nginx
```

### Log Monitoring
```bash
# Key logs to monitor
/var/log/nginx/error.log
/var/log/php8.3-fpm.log
/var/www/api-gateway/storage/logs/laravel.log
```