# Nginx Security Hardening

## Overview
This document describes the security hardening measures implemented for the AskProAI nginx configuration.

## Enhanced Deny Rules

### Sensitive Files Protection
- **Environment files**: `.env`, `.env.local`, etc.
- **Log files**: `.log`, `.log.*`
- **Database dumps**: `.sql`, `.sql.gz`
- **Backup files**: `.bak`, `.backup`, `.tar`, `.zip`, `.gz`

```nginx
location ~ /\.(env|log|sql|bak|backup|gz|tar|zip)$ {
    deny all;
}
```

### Version Control Protection
Blocks access to version control directories:
- `.git`, `.svn`, `.hg`, `.bzr`

```nginx
location ~ /\.(git|svn|hg|bzr) {
    deny all;
}
```

### Dependency Directory Protection
Blocks access to package manager directories:
- `vendor/`, `node_modules/`, `.composer/`, `.npm/`

```nginx
location ~ /(vendor|node_modules|\.composer|\.npm) {
    deny all;
}
```

### Laravel Framework Protection
Blocks direct access to Laravel internal directories:
- `storage/`, `bootstrap/cache/`, `app/`, `config/`, `database/`, `resources/`, `routes/`

```nginx
location ~ /(storage|bootstrap/cache|app|config|database|resources|routes)/ {
    deny all;
}
```

### Documentation Protection
Blocks access to documentation files:
- `.md`, `.txt`, `.rst` files

```nginx
location ~ /\.(md|txt|rst)$ {
    deny all;
}
```

### Configuration Files Protection
Blocks access to build and configuration files:
- `composer.json`, `composer.lock`
- `package.json`, `package.lock`
- `webpack.config.js`, `vite.config.js`
- `.editorconfig`, `.gitignore`
- `artisan` command

```nginx
location ~ /(composer\.(json|lock)|package\.(json|lock)|webpack\.config\.js|vite\.config\.js|\.editorconfig|\.gitignore|artisan)$ {
    deny all;
}
```

### Development Files Protection
Blocks access to test and development files:
- `tests/`, `phpunit.xml`, `.phpunit`
- `jest.config`, `cypress/`

```nginx
location ~ /(tests|phpunit\.xml|\.phpunit|jest\.config|cypress)/ {
    deny all;
}
```

## Attack Pattern Blocking

### Common CMS/Admin Panel Probes
Returns 444 (connection closed) for common attack paths:
- WordPress: `wp-admin`, `wp-content`, `wordpress`
- Database admin: `phpmyadmin`, `myadmin`, `pma`, `dbadmin`
- Generic admin: `admin`

```nginx
location ~ /(wp-admin|wp-content|wordpress|admin|phpmyadmin|myadmin|pma|dbadmin) {
    return 444;
}
```

### Malicious User Agent Blocking
Blocks known vulnerability scanners and attack tools:
- `acunetix`, `sqlmap`, `fimap`
- `nessus`, `whatweb`, `nikto`
- `wfuzz`, `wpscan`, `dirbuster`, `gobuster`

```nginx
if ($http_user_agent ~* "(?:acunetix|sqlmap|fimap|nessus|whatweb|Nikto|Wfuzz|Wpscan|Dirbuster|Gobuster|nikto)" ) {
    return 444;
}
```

## Security Benefits

### 1. Information Disclosure Prevention
- Prevents access to sensitive configuration files
- Blocks directory structure enumeration
- Hides framework-specific files

### 2. Attack Surface Reduction
- Eliminates common attack vectors
- Blocks automated vulnerability scanners
- Prevents brute-force admin panel discovery

### 3. Compliance Enhancement
- Reduces OWASP Top 10 vulnerabilities
- Implements security by default principle
- Supports DSGVO compliance through data protection

## Testing Security Rules

### Verify Sensitive Files are Blocked
```bash
# Test environment file access (should return 403)
curl -I http://your-domain/.env

# Test backup file access (should return 403)  
curl -I http://your-domain/backup.sql

# Test vendor directory (should return 403)
curl -I http://your-domain/vendor/
```

### Verify Attack Pattern Blocking
```bash
# Test WordPress probe (should return 444 or timeout)
curl -I http://your-domain/wp-admin/

# Test scanner user agent (should return 444)
curl -H "User-Agent: sqlmap" -I http://your-domain/
```

## Monitoring and Logging

Security events are logged to:
- `/var/log/nginx/api-gateway-access.log`
- `/var/log/nginx/api-gateway-error.log`

Monitor for:
- 403 errors (blocked file access attempts)
- 444 responses (blocked attack patterns)
- Unusual user agents in access logs

## Maintenance

### Regular Updates Required
1. **User Agent Patterns**: Update scanner detection patterns
2. **Attack Vectors**: Add new threat patterns as discovered
3. **File Extensions**: Review and add new sensitive file types
4. **Log Analysis**: Regular review of blocked attempts

### Configuration Validation
```bash
# Test nginx configuration
nginx -t

# Reload nginx if valid
systemctl reload nginx
```