# Server Configuration

## Nginx Configuration

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name api.askproai.de;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name api.askproai.de;
    root /var/www/api-gateway/public;

    ssl_certificate /etc/letsencrypt/live/api.askproai.de/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.askproai.de/privkey.pem;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## PHP Configuration

```ini
; /etc/php/8.3/fpm/php.ini
memory_limit = 512M
max_execution_time = 300
upload_max_filesize = 50M
post_max_size = 50M
```

## System Services

```bash
# Key services
systemctl status nginx
systemctl status php8.3-fpm
systemctl status mariadb
systemctl status redis
```