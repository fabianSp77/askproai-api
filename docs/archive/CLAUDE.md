# AskProAI - KI-Telefonassistenz System

**Version:** 1.2.0  
**Letzte Aktualisierung:** 14. August 2025

## 📋 Inhaltsverzeichnis

1. [Systemübersicht](#systemübersicht)
2. [Technologie-Stack](#technologie-stack)
3. [Systemarchitektur](#systemarchitektur)
4. [Datenbankstruktur](#datenbankstruktur)
5. [API-Dokumentation](#api-dokumentation)
6. [Komponenten](#komponenten)
7. [Installation & Setup](#installation--setup)
8. [Deployment](#deployment)
9. [Backup-Strategie](#backup-strategie)
10. [Monitoring & Wartung](#monitoring--wartung)
11. [Sicherheit](#sicherheit)
12. [Troubleshooting](#troubleshooting)
13. [Roadmap](#roadmap)
14. [Changelog](#changelog)

---

## 🏢 Systemübersicht

**AskProAI** ist ein vollautomatisiertes KI-Telefonassistenz-System für Dienstleistungsunternehmen (Praxen, Salons, Beratungen). Das System ermöglicht:

- 📞 **KI-gestützte Anrufbearbeitung** via RetellAI
- 📅 **Automatische Terminbuchung** via Cal.com Integration
- 📊 **Admin Dashboard** mit Filament
- 👥 **Multi-Tenant Architektur** für mehrere Kunden
- 🔄 **Webhook-basierte Synchronisation**

### Hauptfunktionen

- Intelligente Anrufbearbeitung mit KI
- Terminbuchung mit Event-Management
- Kundenmanagement und CRM
- Reporting und Analytics
- Multi-Mandanten-fähig

---

## 🛠 Technologie-Stack

### Backend
- **Laravel** 11.x (PHP Framework)
- **PHP** 8.3+
- **MySQL/MariaDB** 10.x
- **Redis** (Caching & Queues)
- **Laravel Horizon** (Queue Management)

### Frontend
- **Filament** 3.x (Admin Panel)
- **Tailwind CSS** 3.x
- **Alpine.js** (JavaScript Framework)
- **Vite** (Asset Building)

### Externe Services
- **RetellAI** - KI-Telefonie
- **Cal.com** - Terminbuchung
- **Stripe** - Payment Processing
- **Twilio** - SMS/Voice Backup
- **Resend** - E-Mail Versand

### Infrastructure
- **Nginx** - Webserver
- **Supervisor** - Process Management
- **Cron** - Scheduled Tasks

---

## 🏗 Systemarchitektur

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   RetellAI      │    │   Cal.com       │    │   Stripe        │
│   (KI-Calls)    │    │   (Termine)     │    │   (Payments)    │
└─────────┬───────┘    └─────────┬───────┘    └─────────┬───────┘
          │                      │                      │
          │ Webhook              │ Webhook              │ Webhook
          ▼                      ▼                      ▼
┌─────────────────────────────────────────────────────────────────┐
│                    Laravel Application                         │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐  │
│  │   Controllers   │  │    Services     │  │     Models      │  │
│  └─────────────────┘  └─────────────────┘  └─────────────────┘  │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐  │
│  │   Middleware    │  │     Jobs        │  │   Filament      │  │
│  └─────────────────┘  └─────────────────┘  └─────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
          │                      │                      │
          ▼                      ▼                      ▼
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│     MySQL       │    │     Redis       │    │     Files       │
│   (Database)    │    │   (Cache/Queue) │    │   (Storage)     │
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

### Datenfluss

1. **Eingehender Anruf** → RetellAI → Webhook → Laravel
2. **Terminbuchung** → Laravel → Cal.com API → Webhook → Laravel  
3. **Admin Interface** → Filament → Laravel → Database
4. **Background Jobs** → Redis Queue → Horizon → Laravel

---

## 🗃 Datenbankstruktur

### Core Tables

#### `tenants`
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| id | UUID | Primary Key |
| name | VARCHAR(255) | Mandanten-Name |
| slug | VARCHAR(100) | URL-Slug |
| api_key | VARCHAR(255) | API Schlüssel |
| balance_cents | INTEGER | Guthaben in Cents |
| calcom_team_slug | VARCHAR(100) | Cal.com Team |
| created_at | TIMESTAMP | Erstellungszeit |
| updated_at | TIMESTAMP | Änderungszeit |

#### `users`
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| id | BIGINT | Primary Key |
| name | VARCHAR(255) | Benutzername |
| email | VARCHAR(255) | E-Mail (unique) |
| password | VARCHAR(255) | Hash-Passwort |
| tenant_id | UUID | FK → tenants.id |
| created_at | TIMESTAMP | Erstellungszeit |
| updated_at | TIMESTAMP | Änderungszeit |

#### `calls`
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| id | BIGINT | Primary Key |
| tenant_id | UUID | FK → tenants.id |
| customer_id | BIGINT | FK → customers.id |
| agent_id | BIGINT | FK → agents.id |
| call_id | VARCHAR(255) | RetellAI Call ID |
| conversation_id | VARCHAR(255) | RetellAI Conversation ID |
| from_number | VARCHAR(20) | Anrufer-Nummer |
| to_number | VARCHAR(20) | Ziel-Nummer |
| start_timestamp | TIMESTAMP | Anruf-Start |
| end_timestamp | TIMESTAMP | Anruf-Ende |
| duration_sec | INTEGER | Dauer in Sekunden |
| call_successful | BOOLEAN | Anruf erfolgreich |
| transcript | TEXT | Gesprächsprotokoll |
| analysis | JSON | KI-Analyse |
| created_at | TIMESTAMP | Erstellungszeit |
| updated_at | TIMESTAMP | Änderungszeit |

#### `appointments`
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| id | BIGINT | Primary Key |
| tenant_id | UUID | FK → tenants.id |
| customer_id | BIGINT | FK → customers.id |
| call_id | BIGINT | FK → calls.id |
| staff_id | BIGINT | FK → staff.id |
| service_id | BIGINT | FK → services.id |
| branch_id | BIGINT | FK → branches.id |
| start_time | DATETIME | Termin-Start |
| end_time | DATETIME | Termin-Ende |
| status | ENUM | 'scheduled','completed','cancelled' |
| notes | TEXT | Notizen |
| calcom_booking_id | VARCHAR(255) | Cal.com Booking ID |
| created_at | TIMESTAMP | Erstellungszeit |
| updated_at | TIMESTAMP | Änderungszeit |

#### `customers`
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| id | BIGINT | Primary Key |
| tenant_id | UUID | FK → tenants.id |
| name | VARCHAR(255) | Kundenname |
| email | VARCHAR(255) | E-Mail |
| phone | VARCHAR(20) | Telefonnummer |
| birthdate | DATE | Geburtsdatum |
| created_at | TIMESTAMP | Erstellungszeit |
| updated_at | TIMESTAMP | Änderungszeit |

### Service Tables

#### `staff`
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| id | BIGINT | Primary Key |
| tenant_id | UUID | FK → tenants.id |
| name | VARCHAR(255) | Mitarbeitername |
| email | VARCHAR(255) | E-Mail |
| phone | VARCHAR(20) | Telefon |
| home_branch_id | BIGINT | FK → branches.id |
| created_at | TIMESTAMP | Erstellungszeit |
| updated_at | TIMESTAMP | Änderungszeit |

#### `services`
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| id | BIGINT | Primary Key |
| tenant_id | UUID | FK → tenants.id |
| name | VARCHAR(255) | Service-Name |
| description | TEXT | Beschreibung |
| duration_minutes | INTEGER | Dauer in Minuten |
| price_cents | INTEGER | Preis in Cents |
| created_at | TIMESTAMP | Erstellungszeit |
| updated_at | TIMESTAMP | Änderungszeit |

#### `branches`
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| id | BIGINT | Primary Key |
| tenant_id | UUID | FK → tenants.id |
| customer_id | BIGINT | FK → customers.id |
| name | VARCHAR(255) | Filial-Name |
| address | TEXT | Adresse |
| phone | VARCHAR(20) | Telefon |
| created_at | TIMESTAMP | Erstellungszeit |
| updated_at | TIMESTAMP | Änderungszeit |

### Integration Tables

#### `calcom_event_types`
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| id | BIGINT | Primary Key |
| tenant_id | UUID | FK → tenants.id |
| staff_id | BIGINT | FK → staff.id |
| calcom_id | INTEGER | Cal.com Event Type ID |
| title | VARCHAR(255) | Event-Titel |
| slug | VARCHAR(255) | URL-Slug |
| length | INTEGER | Dauer in Minuten |
| created_at | TIMESTAMP | Erstellungszeit |
| updated_at | TIMESTAMP | Änderungszeit |

#### `calcom_bookings`
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| id | BIGINT | Primary Key |
| tenant_id | UUID | FK → tenants.id |
| appointment_id | BIGINT | FK → appointments.id |
| calcom_booking_id | INTEGER | Cal.com Booking ID |
| start_time | DATETIME | Buchungs-Start |
| end_time | DATETIME | Buchungs-Ende |
| attendee_email | VARCHAR(255) | Teilnehmer E-Mail |
| attendee_name | VARCHAR(255) | Teilnehmer Name |
| created_at | TIMESTAMP | Erstellungszeit |
| updated_at | TIMESTAMP | Änderungszeit |

---

## 📡 API-Dokumentation

### Authentication
Alle API-Endpunkte erfordern einen `Bearer Token` oder `API Key` Header.

```bash
Authorization: Bearer YOUR_API_TOKEN
# oder
X-API-Key: YOUR_API_KEY
```

### Webhooks

#### RetellAI Webhook
**Endpunkt:** `POST /api/retell/webhook`

**Request:**
```json
{
  "event": "call_ended",
  "data": {
    "call_id": "call_12345",
    "conversation_id": "conv_67890",
    "from_number": "+491234567890",
    "to_number": "+491234567891",
    "start_timestamp": 1699123456,
    "end_timestamp": 1699123556,
    "transcript": "Hallo, ich möchte einen Termin buchen...",
    "call_analysis": {
      "intent": "appointment_booking",
      "sentiment": "positive"
    }
  }
}
```

**Response:**
```json
{
  "success": true,
  "message": "Webhook processed successfully",
  "call_id": 123
}
```

#### Cal.com Webhook
**Endpunkt:** `POST /api/calcom/webhook`

**Request:**
```json
{
  "triggerEvent": "BOOKING_CREATED",
  "createdAt": "2025-08-14T10:00:00.000Z",
  "payload": {
    "bookingId": 12345,
    "type": "Beratungsgespräch",
    "title": "Beratungsgespräch between John Doe and Jane Smith",
    "startTime": "2025-08-15T14:00:00.000Z",
    "endTime": "2025-08-15T15:00:00.000Z",
    "organizer": {
      "email": "jane.smith@example.com",
      "name": "Jane Smith"
    },
    "attendees": [
      {
        "email": "john.doe@example.com",
        "name": "John Doe"
      }
    ]
  }
}
```

**Response:**
```json
{
  "received": true,
  "status": "processed"
}
```

### REST API Endpunkte

#### Calls API

**GET /api/calls**
- Beschreibung: Alle Anrufe abrufen
- Parameter: `page`, `per_page`, `from_date`, `to_date`
- Response: Paginierte Liste von Anrufen

**GET /api/calls/{id}**
- Beschreibung: Einzelnen Anruf abrufen
- Response: Call-Details mit Transcript und Analysis

**POST /api/calls**
- Beschreibung: Neuen Anruf erstellen (für Tests)
- Body: Call-Daten als JSON

#### Appointments API

**GET /api/appointments**
- Beschreibung: Alle Termine abrufen
- Parameter: `status`, `from_date`, `to_date`, `staff_id`

**POST /api/appointments**
- Beschreibung: Neuen Termin erstellen
- Body: Termin-Details

**PUT /api/appointments/{id}**
- Beschreibung: Termin aktualisieren
- Body: Geänderte Termin-Daten

**DELETE /api/appointments/{id}**
- Beschreibung: Termin stornieren

#### Customers API

**GET /api/customers**
- Beschreibung: Alle Kunden abrufen
- Parameter: `search`, `page`, `per_page`

**POST /api/customers**
- Beschreibung: Neuen Kunden erstellen
- Body: Kundendaten

**GET /api/customers/{id}**
- Beschreibung: Kundendetails abrufen
- Response: Kunde mit Anrufen und Terminen

---

## 🔧 Komponenten

### Controllers
- `CalcomController` - Cal.com API Integration
- `RetellWebhookController` - RetellAI Webhook Verarbeitung
- `CalcomWebhookController` - Cal.com Webhook Verarbeitung
- `ApiController` - Haupt-API Endpunkte
- `DashboardController` - Admin Dashboard

### Services
- `CalcomService` - Cal.com API Client
- `RetellService` - RetellAI Integration
- `CallDataRefresher` - Anruf-Daten Synchronisation

### Models
- `Tenant` - Multi-Tenant Basis
- `Call` - Anruf-Daten
- `Appointment` - Termin-Management
- `Customer` - Kundenverwaltung
- `Staff` - Mitarbeiterverwaltung
- `Service` - Dienstleistungen

### Jobs
- `ProcessRetellCallJob` - Anruf-Verarbeitung
- `RefreshCallDataJob` - Daten-Synchronisation
- `HeartbeatJob` - System-Monitoring

### Filament Resources
- `CallResource` - Anruf-Verwaltung
- `AppointmentResource` - Termin-Verwaltung
- `CustomerResource` - Kunden-Verwaltung
- `StaffResource` - Mitarbeiter-Verwaltung

---

## ⚡ Installation & Setup

### Voraussetzungen
```bash
# System Requirements
PHP >= 8.3
MySQL >= 8.0 / MariaDB >= 10.4
Redis >= 6.0
Node.js >= 18
Composer >= 2.0
```

### Installation

1. **Repository klonen**
```bash
git clone https://github.com/your-org/askproai.git
cd askproai
```

2. **Dependencies installieren**
```bash
# PHP Dependencies
composer install --optimize-autoloader --no-dev

# Frontend Dependencies
npm ci
npm run build
```

3. **Umgebung konfigurieren**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Environment Variables setzen**
```bash
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=askproai_db
DB_USERNAME=askproai_user
DB_PASSWORD=your_password

# Cal.com Integration
CALCOM_API_KEY=cal_live_your_api_key_here
CALCOM_BASE_URL=https://api.cal.com/v2
CALCOM_WEBHOOK_SECRET=your_webhook_secret

# RetellAI Integration
RETELL_API_KEY=your_retell_api_key
RETELL_WEBHOOK_SECRET=your_webhook_secret

# Queue & Cache
REDIS_HOST=127.0.0.1
QUEUE_CONNECTION=redis
CACHE_DRIVER=redis
```

5. **Datenbank setup**
```bash
php artisan migrate
php artisan db:seed --class=AdminUserSeeder
```

6. **Permissions setzen**
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

7. **Services starten**
```bash
# Queue Worker
php artisan horizon

# Scheduler (in Cron)
* * * * * cd /var/www/askproai && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🚀 Deployment

### Production Deployment

#### Server Vorbereitung
```bash
# Install System Dependencies
apt update && apt upgrade -y
apt install -y nginx mysql-server redis-server supervisor php8.3-fpm php8.3-mysql php8.3-redis
```

#### Nginx Konfiguration
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/askproai/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

#### Supervisor Konfiguration
```ini
# /etc/supervisor/conf.d/askproai-horizon.conf
[program:askproai-horizon]
process_name=%(program_name)s
command=php /var/www/askproai/artisan horizon
directory=/var/www/askproai
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/askproai/storage/logs/horizon.log
```

#### Deployment Script
```bash
#!/bin/bash
set -e

echo "🚀 Starting AskProAI Deployment..."

# Update Code
git pull origin main

# Install Dependencies
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# Database Migrations
php artisan migrate --force

# Clear Caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart Services
supervisorctl restart askproai-horizon
systemctl reload nginx php8.3-fpm

echo "✅ Deployment completed successfully!"
```

### Deployment Checkliste

- [ ] **Environment Variables** validiert
- [ ] **Database Migrations** ausgeführt
- [ ] **File Permissions** korrekt gesetzt
- [ ] **SSL Certificate** installiert
- [ ] **Backup** vor Deployment erstellt
- [ ] **Queue Workers** laufen
- [ ] **Cron Jobs** konfiguriert
- [ ] **Log Rotation** eingerichtet
- [ ] **Monitoring** aktiv
- [ ] **Health Checks** erfolgreich
- [ ] **API Keys** rotiert (falls nötig)

---

## 💾 Backup-Strategie

### Automatische Backups

**Tägliche Backups um 03:00 Uhr**
```bash
# /etc/cron.d/askproai-backup
0 3 * * * root /var/www/askproai/scripts/backup.sh
```

### Backup Komponenten

1. **Database Backup**
```bash
mysqldump --single-transaction --routines --triggers askproai_db > backup_$(date +%Y%m%d_%H%M%S).sql
```

2. **File Backup**
```bash
tar -czf askproai_files_$(date +%Y%m%d_%H%M%S).tar.gz \
    --exclude='storage/logs' \
    --exclude='node_modules' \
    /var/www/askproai
```

3. **Configuration Backup**
```bash
tar -czf askproai_config_$(date +%Y%m%d_%H%M%S).tar.gz \
    /etc/nginx/sites-available/askproai \
    /etc/supervisor/conf.d/askproai-* \
    /var/www/askproai/.env
```

### Restore Prozess

```bash
# 1. Service stoppen
supervisorctl stop askproai-horizon
systemctl stop nginx

# 2. Database wiederherstellen
mysql askproai_db < backup_YYYYMMDD_HHMMSS.sql

# 3. Files wiederherstellen
tar -xzf askproai_files_YYYYMMDD_HHMMSS.tar.gz -C /

# 4. Permissions setzen
chown -R www-data:www-data /var/www/askproai

# 5. Services starten
systemctl start nginx
supervisorctl start askproai-horizon
```

### Offsite Backup

Backups werden automatisch zu AWS S3 übertragen:
```bash
aws s3 sync /var/backups/askproai/ s3://askproai-backups/$(date +%Y-%m)/
```

**Aufbewahrung:**
- Tägliche Backups: 14 Tage
- Wöchentliche Backups: 3 Monate  
- Monatliche Backups: 1 Jahr

---

## 📊 Monitoring & Wartung

### System Health Checks

```bash
# Service Status prüfen
systemctl status nginx php8.3-fpm redis-server mysql
supervisorctl status askproai-horizon

# Queue Status
php artisan horizon:status
php artisan queue:monitor redis:default --max=100

# Database Connections
php artisan db:monitor --databases=mysql --max=80
```

### Log Monitoring

**Laravel Logs**
```bash
tail -f storage/logs/laravel.log | grep ERROR
```

**Nginx Logs**
```bash  
tail -f /var/log/nginx/askproai_error.log
```

**Horizon Logs**
```bash
tail -f storage/logs/horizon.log
```

### Performance Monitoring

**Database Queries**
```bash
# Slow Query Log aktivieren
echo "slow_query_log = 1" >> /etc/mysql/my.cnf
echo "long_query_time = 2" >> /etc/mysql/my.cnf
```

**Redis Memory**
```bash
redis-cli info memory
```

**PHP-FPM Status**
```bash
curl http://localhost/php-fpm-status
```

### Automated Health Checks

```bash
#!/bin/bash
# /usr/local/bin/askproai-health-check.sh

ERRORS=0

# Check HTTP Response
if ! curl -sf http://localhost/api/health > /dev/null; then
    echo "❌ HTTP Health Check failed"
    ERRORS=$((ERRORS + 1))
fi

# Check Database
if ! php artisan db:monitor --databases=mysql >/dev/null 2>&1; then
    echo "❌ Database Health Check failed"  
    ERRORS=$((ERRORS + 1))
fi

# Check Horizon
if ! php artisan horizon:status | grep -q "running"; then
    echo "❌ Horizon not running"
    ERRORS=$((ERRORS + 1))
fi

if [ $ERRORS -eq 0 ]; then
    echo "✅ All systems operational"
    exit 0
else
    echo "⚠️  $ERRORS issues detected"
    exit 1
fi
```

### Wartungsaufgaben

**Tägliche Tasks**
```bash
# Log Cleanup
find storage/logs -name "*.log" -mtime +30 -delete

# Session Cleanup  
php artisan session:gc

# Cache Warmup
php artisan config:cache
php artisan route:cache
```

**Wöchentliche Tasks**
```bash
# Database Optimization
php artisan optimize:clear
mysql -e "OPTIMIZE TABLE calls, appointments, customers;"

# File Cleanup
php artisan telescope:prune --hours=168
```

**Monatliche Tasks**
```bash
# Security Updates
apt update && apt upgrade -y

# Certificate Renewal
certbot renew --nginx

# Performance Review
php artisan tinker --execute="DB::table('calls')->count()"
```

---

## 🔒 Sicherheit

### Environment Security

**Sichere .env Konfiguration:**
```bash
# NIEMALS in Git committen
echo ".env" >> .gitignore

# Sichere Permissions
chmod 600 .env
chown www-data:www-data .env

# APP_DEBUG in Production
APP_DEBUG=false
APP_ENV=production
```

### API Security

**Rate Limiting**
```php
// In routes/api.php
Route::middleware(['throttle:60,1'])->group(function () {
    // API Routes
});
```

**Request Validation**
```php
public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:customers',
        'phone' => 'required|regex:/^\+?[1-9]\d{1,14}$/',
    ]);
}
```

**CSRF Protection**
```php
// Alle Formulare mit CSRF Token
@csrf
```

### Webhook Security

**Signature Verification**
```php
// RetellAI Webhook Verification
public function verify(Request $request)
{
    $signature = $request->header('X-Retell-Signature');
    $payload = $request->getContent();
    $secret = config('services.retell.webhook_secret');
    
    $expectedSignature = hash_hmac('sha256', $payload, $secret);
    
    if (!hash_equals($signature, $expectedSignature)) {
        abort(401, 'Invalid signature');
    }
}
```

### Database Security

**Query Parameter Binding**
```php
// RICHTIG - Parameter Binding
$calls = DB::select('SELECT * FROM calls WHERE tenant_id = ?', [$tenantId]);

// FALSCH - SQL Injection möglich
$calls = DB::select("SELECT * FROM calls WHERE tenant_id = $tenantId");
```

**Mass Assignment Protection**
```php
class Customer extends Model
{
    // Nur diese Felder können mass-assigned werden
    protected $fillable = ['name', 'email', 'phone'];
}
```

### File Upload Security

```php
// Validator für File Uploads
$request->validate([
    'avatar' => 'required|image|mimes:jpeg,png,jpg|max:2048',
]);

// Sichere Storage
$path = $request->file('avatar')->store('avatars', 'private');
```

---

## 🔧 Troubleshooting

### Häufige Probleme

#### 1. Queue Jobs hängen

**Symptome:** Anrufe werden nicht verarbeitet
**Lösung:**
```bash
# Horizon neu starten
supervisorctl restart askproai-horizon

# Failed Jobs prüfen
php artisan queue:failed

# Failed Jobs wiederholen
php artisan queue:retry all
```

#### 2. Cal.com API Fehler

**Symptome:** Termine können nicht gebucht werden
**Lösung:**
```bash
# API Key prüfen
curl -H "Authorization: Bearer $CALCOM_API_KEY" https://api.cal.com/v2/me

# Webhook Status prüfen
tail -f storage/logs/laravel.log | grep "Cal.com"
```

#### 3. Database Connection Issues

**Symptome:** 500 Server Errors
**Lösung:**
```bash
# Connection testen
php artisan db:monitor --databases=mysql

# Pool Connections prüfen
mysql -e "SHOW STATUS LIKE 'Threads_%';"

# Connection Limit erhöhen
echo "max_connections = 200" >> /etc/mysql/my.cnf
```

#### 4. High Memory Usage

**Symptome:** Server läuft langsam
**Lösung:**
```bash
# Memory Usage prüfen
free -h
php artisan horizon:status

# Horizon Memory Limit
echo "memory_limit = 512M" >> /etc/php/8.3/fpm/pool.d/www.conf
```

#### 5. Permission Errors

**Symptome:** File write errors
**Lösung:**
```bash
# Permissions korrigieren
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# SELinux Context (falls aktiviert)
setsebool -P httpd_can_network_connect 1
```

### Debug Commands

```bash
# Laravel Debug Modus
php artisan route:list
php artisan config:show database
php artisan queue:monitor

# System Information
php artisan about
php artisan env
php artisan tinker

# Performance Debugging
php artisan optimize:clear
php artisan debugbar:clear
```

### Log Analysis

```bash
# Fehler der letzten Stunde
grep "$(date -d '1 hour ago' '+%Y-%m-%d %H')" storage/logs/laravel.log | grep ERROR

# Top Fehlerquellen
grep ERROR storage/logs/laravel.log | awk '{print $NF}' | sort | uniq -c | sort -nr

# API Response Times
grep "Duration:" storage/logs/laravel.log | awk '{print $NF}' | sort -n
```

---

## 🗺 Roadmap

### Q4 2025

**🔥 High Priority**
- [ ] **Multi-Channel Support** - WhatsApp & SMS Integration
- [ ] **Advanced Analytics** - Call Analytics Dashboard
- [ ] **Mobile App** - React Native Client App
- [ ] **API v2** - GraphQL API Implementation

**📈 Medium Priority**
- [ ] **CRM Integration** - Salesforce/HubSpot Connectors
- [ ] **Advanced Scheduling** - Recurring Appointments
- [ ] **Payment Integration** - Stripe Connect for Multi-Tenant
- [ ] **Reporting Suite** - Advanced Business Intelligence

**🔧 Technical Improvements**
- [ ] **Performance Optimization** - Query Optimization & Caching
- [ ] **Security Hardening** - 2FA, Advanced Rate Limiting
- [ ] **Docker Support** - Container-based Deployment
- [ ] **Test Coverage** - 80%+ Test Coverage

### Q1 2026

**🌟 New Features**
- [ ] **AI Voice Cloning** - Custom Voice Models per Tenant
- [ ] **Multi-Language** - i18n Support (EN, FR, ES)
- [ ] **Advanced Integrations** - Zapier/IFTTT Support
- [ ] **White Label** - Complete Branding Customization

**🔄 Technical Debt**
- [ ] **Laravel 12 Upgrade** - Framework Update
- [ ] **Database Sharding** - Horizontal Scaling
- [ ] **Microservices** - Service Decomposition
- [ ] **Event Sourcing** - Advanced Audit Trail

### Backlog

**📋 Feature Requests**
- Video Call Integration (Zoom/Teams)
- Advanced Role & Permission System
- Custom Workflow Builder
- Integration Testing Suite
- Advanced Monitoring (Prometheus/Grafana)

---

## 📝 Changelog

### v1.2.0 - 2025-08-14

#### 🔒 Security
- **FIXED:** Removed hardcoded API keys from codebase
- **FIXED:** Added proper environment variable configuration  
- **ADDED:** Comprehensive security documentation
- **ADDED:** API key rotation procedures

#### 📚 Documentation  
- **ADDED:** Complete CLAUDE.md with full system documentation
- **ADDED:** Unified /docs folder structure
- **ADDED:** Comprehensive API documentation with examples
- **ADDED:** Deployment guides and checklistes  
- **ADDED:** Backup and restore procedures
- **ADDED:** Monitoring and troubleshooting guides

#### 🧹 Code Quality
- **REFACTORED:** Controllers to use Services for business logic
- **IMPROVED:** Models with proper relationships and mass assignment protection
- **CLEANED:** Removed temporary files and backup duplicates
- **STANDARDIZED:** Code style and naming conventions

#### 🏗 Architecture
- **CONSOLIDATED:** Documentation into unified structure
- **IMPROVED:** Multi-tenant architecture documentation  
- **ENHANCED:** Database schema documentation
- **ADDED:** System architecture diagrams

### v1.1.0 - 2025-07-22

#### ✨ Features
- **ADDED:** Enhanced portal authentication with session persistence
- **ADDED:** Comprehensive API V2 controllers  
- **ADDED:** Improved Filament admin panel with KPI widgets
- **ADDED:** Enhanced service layer with MCP orchestration
- **ADDED:** Frontend improvements and responsive CSS
- **ADDED:** Query performance monitoring
- **ADDED:** MCP health monitoring and debug tools

#### 🔧 Technical
- **ADDED:** Performance indexes to key database tables
- **IMPROVED:** Query performance monitoring
- **ENHANCED:** Session handling middleware
- **ADDED:** CORS support for React portal
- **IMPROVED:** Authentication flow

#### 🐛 Bug Fixes  
- **FIXED:** Navigation overlap issues with CSS Grid layout
- **FIXED:** Session persistence problems in portal
- **FIXED:** API authentication edge cases
- **FIXED:** Mobile responsiveness issues

### v1.0.0 - 2025-05-01

#### 🎉 Initial Release
- **ADDED:** RetellAI integration for KI-powered calls
- **ADDED:** Cal.com integration for appointment booking  
- **ADDED:** Multi-tenant architecture
- **ADDED:** Filament admin panel
- **ADDED:** Webhook processing for external services
- **ADDED:** Basic reporting and analytics
- **ADDED:** Customer and staff management
- **ADDED:** Service and branch management

---

## 📞 Support & Kontakt

**Technischer Support:**
- 📧 E-Mail: support@askproai.de
- 📱 Telefon: +49 (0) 123 456 789
- 🌐 Website: https://askproai.de

**Entwicklung:**
- 🐛 Bug Reports: GitHub Issues
- 💡 Feature Requests: GitHub Discussions  
- 📖 Documentation: /docs Ordner

**Notfall-Support:**
- 🚨 24/7 Hotline: +49 (0) 123 456 999
- 📟 Status Page: https://status.askproai.de

---

*Letzte Aktualisierung: 14. August 2025*  
*Version: 1.2.0*  
*© 2025 AskProAI - Alle Rechte vorbehalten*