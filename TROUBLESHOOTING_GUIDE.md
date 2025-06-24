# AskProAI Troubleshooting Guide

## 🚨 Kritische Fehler & Lösungen

### 1. HTTP 500 Error beim Login (Livewire Update Failed)

**Symptome:**
- Login-Seite lädt, aber nach Eingabe der Credentials: HTTP 500
- Browser Console: `POST https://api.askproai.de/livewire/update 500 (Internal Server Error)`
- Filament Admin Panel nicht erreichbar

**Root Causes:**
1. **PHP Memory Exhaustion**
   - Standard 128MB/256MB reicht nicht für Filament mit vielen Resources
   - Große Datensätze in Tables/Forms verbrauchen viel Memory

2. **Recursion Bugs in Traits**
   - Zirkuläre Methodenaufrufe führen zu Stack Overflow
   - Besonders in Navigation/Label Methoden

**Lösung:**
```bash
# 1. PHP Memory erhöhen
sudo nano /etc/php/8.3/fpm/php.ini
# Ändern: memory_limit = 1024M
# Ändern: max_execution_time = 300

# 2. PHP-FPM neustarten
sudo systemctl restart php8.3-fpm

# 3. Recursion Bugs fixen (siehe Code-Beispiel unten)

# 4. Caches leeren
php artisan optimize:clear
php artisan filament:clear-cached-components
```

**Code-Fix für Recursion:**
```php
// FALSCH (Recursion):
public static function getModelLabel(): string {
    return static::getNavigationMapping()[...] // Methode existiert nicht!
}

// RICHTIG:
public static function getModelLabel(): string {
    $config = UnifiedNavigationService::RESOURCE_CONFIG[static::getResourceKey()] ?? null;
    return $config['label'] ?? parent::getModelLabel();
}
```

---

### 2. "Es werden keine Anrufe eingespielt" (Retell Integration)

**Symptome:**
- Keine Calls in der Datenbank
- Webhook kommt an, aber wird nicht verarbeitet
- Queue Jobs bleiben hängen

**Root Causes:**
1. Horizon nicht gestartet
2. Falsche Webhook URL in Retell.ai
3. API Keys nicht korrekt

**Lösung:**
```bash
# 1. Horizon starten
php artisan horizon

# 2. Webhook URL prüfen in Retell.ai Dashboard:
# https://api.askproai.de/api/retell/webhook

# 3. Manueller Import
php artisan calls:import --days=7
```

---

### 3. Memory Issues bei großen Datensätzen

**Symptome:**
- Seiten laden sehr langsam
- Timeout Errors
- "Allowed memory size exhausted"

**Präventive Maßnahmen:**
1. **Pagination implementieren**
2. **Eager Loading verwenden**
3. **Query Optimization**

---

## 📊 Performance Monitoring

### Quick Health Check Script
```bash
#!/bin/bash
# health-check.sh
echo "=== AskProAI Health Check ==="
echo "1. PHP Memory: $(php -r 'echo ini_get("memory_limit");')"
echo "2. PHP-FPM Status:"
sudo systemctl status php8.3-fpm --no-pager | grep Active
echo "3. Horizon Status:"
php artisan horizon:status
echo "4. Redis:"
redis-cli ping
echo "5. Recent Errors:"
tail -5 /var/www/api-gateway/storage/logs/laravel.log | grep ERROR
```

### Memory Usage Monitor
```php
// In AppServiceProvider::boot()
if (config('app.debug')) {
    \Event::listen(RequestHandled::class, function ($event) {
        if ($event->request->is('admin/*')) {
            Log::debug('Memory Usage', [
                'url' => $event->request->url(),
                'memory' => round(memory_get_peak_usage() / 1024 / 1024, 2) . 'MB',
                'time' => round(microtime(true) - LARAVEL_START, 2) . 's'
            ]);
        }
    });
}
```

---

## 🛠️ Debugging Tools

### 1. Livewire Debug Mode
```php
// config/livewire.php
'debug' => env('APP_DEBUG', false),
```

### 2. Query Debugging
```php
// Temporär in Controller/Resource
\DB::enableQueryLog();
// ... code ...
dd(\DB::getQueryLog());
```

### 3. Component Analysis
```bash
php artisan livewire:discover
php artisan filament:components
```

---

## ⚡ Optimierungen

### 1. Database Indexes
```sql
-- Wichtige Indexes für Performance
CREATE INDEX idx_calls_company_created ON calls(company_id, created_at);
CREATE INDEX idx_appointments_branch_starts ON appointments(branch_id, starts_at);
CREATE INDEX idx_customers_phone ON customers(phone);
```

### 2. Cache Strategy
```php
// In Resources für statische Daten
public static function getNavigationItems(): array
{
    return Cache::remember('navigation_items_' . auth()->id(), 3600, function () {
        // Heavy computation here
    });
}
```

### 3. Lazy Loading für große Tables
```php
// In Resource Table
->lazy() // Für unendliches Scrollen
->deferLoading() // Lädt erst nach User-Interaktion
```

---

## 📝 Best Practices

### 1. Memory Management
- **Development**: memory_limit = 512M
- **Production**: memory_limit = 1024M
- **Heavy Operations**: Batch Processing mit Chunks

### 2. Error Handling
```php
try {
    // Risky operation
} catch (\Exception $e) {
    Log::error('Operation failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'user_id' => auth()->id(),
        'url' => request()->url(),
    ]);
    
    // User-friendly error
    throw ValidationException::withMessages([
        'error' => 'Ein Fehler ist aufgetreten. Bitte später erneut versuchen.'
    ]);
}
```

### 3. Monitoring
- Verwende Laravel Telescope in Development
- Sentry in Production für Error Tracking
- Custom Logging für kritische Operationen

---

## 🚀 Quick Fixes Cheatsheet

```bash
# Cache Problems
php artisan optimize:clear && php artisan config:cache

# Livewire Issues  
php artisan livewire:discover && php artisan filament:clear-cached-components

# Memory Issues
sudo nano /etc/php/8.3/fpm/php.ini # memory_limit erhöhen
sudo systemctl restart php8.3-fpm

# Queue Problems
php artisan horizon:terminate && php artisan horizon

# Permission Issues
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Database Issues
php artisan migrate:fresh --seed # NUR in Development!
```

---

## 📞 Support Kontakte

- **Technischer Notfall**: [Deine Kontaktdaten]
- **Retell.ai Support**: support@retell.ai
- **Cal.com Support**: support@cal.com

---

Letzte Aktualisierung: 2025-06-24