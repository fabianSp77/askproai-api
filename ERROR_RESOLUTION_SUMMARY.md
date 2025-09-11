# Fehlerbehebung - View Cache Problem

## Aktueller Status
**Problem**: `filemtime(): stat failed for storage/framework/views/[hash].php` Fehler tritt wiederholt auf, was zu HTTP 500 Fehlern führt.

## Durchgeführte Maßnahmen

### 1. Cache-Bereinigung (Mehrfach durchgeführt)
```bash
php artisan view:clear
php artisan cache:clear  
php artisan config:clear
php artisan optimize:clear
```

### 2. Verzeichnis-Bereinigung
```bash
rm -rf storage/framework/views/*
rm -rf bootstrap/cache/*
```

### 3. Berechtigungen korrigiert
```bash
chown -R www-data:www-data storage
chmod -R 775 storage/framework
```

### 4. PHP-Services neugestartet
```bash
systemctl restart php8.3-fpm
php -r "opcache_reset();"
```

### 5. Automatisiertes Fix-Script erstellt
- `/var/www/api-gateway/scripts/auto-fix-cache.sh`
- Führt alle oben genannten Schritte automatisch aus

### 6. Laravel neu konfiguriert
```bash
composer dump-autoload
php artisan config:cache
php artisan route:cache
```

## Mögliche Ursachen

### 1. **Race Condition**
Mehrere PHP-Prozesse greifen gleichzeitig auf View-Cache zu

### 2. **Deployment-Konflikt**
Neue Code-Änderungen während laufendem Betrieb

### 3. **Speicher-Problem**
- Disk Space: 12% verwendet (54G von 504G)
- Inodes: 1% verwendet
- **Kein Speicherproblem erkennbar**

### 4. **Konfigurationsproblem**
- VIEW_COMPILED_PATH ist bereits deaktiviert in .env
- config/view.php nutzt Standard-Pfad

## Temporäre Lösung

Bei erneutem Auftreten des Fehlers:
```bash
/var/www/api-gateway/scripts/auto-fix-cache.sh
```

## Permanente Lösung (Empfehlung)

### Option 1: View-Caching deaktivieren (Development)
In `.env`:
```
APP_ENV=local
APP_DEBUG=true
```

### Option 2: Redis für View-Cache nutzen
```php
// config/view.php
'compiled' => env('VIEW_COMPILED_PATH', storage_path('framework/views')),
'cache' => env('VIEW_CACHE_DRIVER', 'redis'),
```

### Option 3: Deployment-Prozess verbessern
1. Maintenance Mode aktivieren
2. Cache leeren
3. Code deployen  
4. Cache neu aufbauen
5. Maintenance Mode deaktivieren

## Stripe-Menu Status

Die Stripe-Menu-Implementierung ist vollständig erstellt aber noch nicht sichtbar:
- Alle Dateien erstellt ✅
- Assets kompiliert ✅
- Integration in Filament ausstehend ⏳

Der View-Cache-Fehler verhindert momentan die Überprüfung der Implementierung.

## Nächste Schritte

1. **Sofort**: Script ausführen bei Fehler
2. **Kurzfristig**: Redis-Cache implementieren
3. **Langfristig**: Deployment-Prozess optimieren