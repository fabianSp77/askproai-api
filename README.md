# 🤖 AskProAI - KI-Telefonassistenz

<p align="center">
  <img src="https://img.shields.io/badge/Version-1.2.0-blue.svg" alt="Version">
  <img src="https://img.shields.io/badge/Laravel-11.x-red.svg" alt="Laravel">
  <img src="https://img.shields.io/badge/PHP-8.3+-purple.svg" alt="PHP">
  <img src="https://img.shields.io/badge/License-Proprietary-yellow.svg" alt="License">
</p>

**Intelligente KI-Telefonassistenz für Dienstleistungsunternehmen**

AskProAI automatisiert Anrufbearbeitung und Terminbuchung für Praxen, Salons, Beratungsunternehmen und andere Dienstleister. Das System nutzt fortschrittliche KI-Technologie für natürliche Gesprächsführung und nahtlose Integration in bestehende Buchungssysteme.

## 🌟 Key Features

- 🤖 **KI-gestützte Anrufbearbeitung** mit natürlicher Spracherkennung
- 📅 **Automatische Terminbuchung** via Cal.com Integration
- 🏢 **Multi-Tenant Architektur** für mehrere Kunden
- 📊 **Umfassendes Admin Dashboard** mit Filament
- 📞 **Echtzeit-Anrufprotokollierung** und Transkription
- 💳 **Payment Processing** mit Stripe Integration
- 🔄 **Webhook-basierte Synchronisation** mit externen Services
- 📈 **Analytics & Reporting** für Business Intelligence

## 🏗 Architektur

```
┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│   RetellAI  │    │   Cal.com   │    │   Stripe    │
│  (KI-Calls) │◄──►│ (Termine)   │◄──►│ (Payment)   │
└─────────────┘    └─────────────┘    └─────────────┘
       │                   │                   │
       │ Webhooks          │ API               │ API
       ▼                   ▼                   ▼
┌────────────────────────────────────────────────────┐
│              Laravel Application                   │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐           │
│  │Controllers│ │ Services │ │  Models  │           │
│  └──────────┘ └──────────┘ └──────────┘           │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐           │
│  │   Jobs   │ │Filament  │ │   APIs   │           │
│  └──────────┘ └──────────┘ └──────────┘           │
└────────────────────────────────────────────────────┘
       │                   │                   │
       ▼                   ▼                   ▼
┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│   MySQL     │    │    Redis    │    │   Storage   │
│ (Database)  │    │(Cache/Queue)│    │   (Files)   │
└─────────────┘    └─────────────┘    └─────────────┘
```

## 🚀 Quick Start

### Voraussetzungen

```bash
# System Requirements
PHP >= 8.3
MySQL >= 8.0 / MariaDB >= 10.4
Redis >= 6.0
Node.js >= 18
Composer >= 2.0
Nginx/Apache
```

### Installation

```bash
# 1. Repository klonen
git clone https://github.com/your-org/askproai.git
cd askproai

# 2. Dependencies installieren
composer install --optimize-autoloader --no-dev
npm ci && npm run build

# 3. Environment Setup
cp .env.example .env
php artisan key:generate

# 4. Database Setup
php artisan migrate
php artisan db:seed --class=AdminUserSeeder

# 5. File Permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# 6. Queue Worker starten
php artisan horizon
```

### Konfiguration

Wichtige Environment Variables in `.env`:

```bash
# Application
APP_NAME="AskProAI"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=askproai_db
DB_USERNAME=askproai_user
DB_PASSWORD=your_secure_password

# Cal.com Integration
CALCOM_API_KEY=your_calcom_api_key
CALCOM_BASE_URL=https://api.cal.com/v1
CALCOM_WEBHOOK_SECRET=your_webhook_secret

# RetellAI Integration  
RETELL_API_KEY=your_retell_api_key
RETELL_WEBHOOK_SECRET=your_retell_webhook_secret

# Queue & Cache
REDIS_HOST=127.0.0.1
QUEUE_CONNECTION=redis
CACHE_DRIVER=redis
```

## 🛠 Technologie-Stack

### Backend
- **Laravel 11.x** - PHP Framework
- **PHP 8.3+** - Programming Language
- **MySQL/MariaDB** - Primary Database
- **Redis** - Caching & Queue Management
- **Laravel Horizon** - Queue Monitoring

### Frontend
- **Filament 3.x** - Admin Panel Framework
- **Tailwind CSS 3.x** - Utility-First CSS
- **Alpine.js** - Lightweight JS Framework
- **Vite** - Frontend Build Tool

### Externe Services
- **RetellAI** - AI-Powered Phone System
- **Cal.com** - Appointment Scheduling
- **Stripe** - Payment Processing
- **Twilio** - SMS/Voice Services
- **Resend** - Transactional Email

## 📖 Dokumentation

📚 **Vollständige Dokumentation:** [CLAUDE.md](CLAUDE.md)

### Wichtige Links
- 🏠 **Dashboard:** `https://your-domain.com/admin`
- 📞 **API Docs:** `https://your-domain.com/docs/api`
- 🔧 **Setup Guide:** [docs/guides/installation.md](docs/guides/installation.md)
- 🚀 **Deployment:** [docs/deployment/production.md](docs/deployment/production.md)
- 🔒 **Security:** [docs/security/overview.md](docs/security/overview.md)
- 🛡️ **DSGVO-Compliance:** [docs/compliance/dsgvo-compliance.md](docs/compliance/dsgvo-compliance.md)

## 🔌 API Endpunkte

### Webhooks
```bash
# RetellAI Webhook
POST /api/retell/webhook

# Cal.com Webhook  
POST /api/calcom/webhook
```

### REST API
```bash
# Calls Management
GET    /api/calls           # List all calls
GET    /api/calls/{id}      # Get specific call
POST   /api/calls           # Create new call

# Appointments
GET    /api/appointments    # List appointments
POST   /api/appointments    # Create appointment
PUT    /api/appointments/{id} # Update appointment
DELETE /api/appointments/{id} # Cancel appointment

# Customers
GET    /api/customers       # List customers
POST   /api/customers       # Create customer
GET    /api/customers/{id}  # Get customer details
```

Vollständige API-Dokumentation: [CLAUDE.md#api-dokumentation](CLAUDE.md#api-dokumentation)

## 🚀 Deployment

### Production Deployment

```bash
# 1. Server vorbereiten
apt update && apt upgrade -y
apt install -y nginx mysql-server redis-server supervisor php8.3-fpm

# 2. SSL Certificate
certbot --nginx -d your-domain.com

# 3. Deployment Script ausführen
./scripts/deploy.sh

# 4. Services starten
systemctl enable nginx mysql redis-server
supervisorctl start askproai-horizon
```

### Docker Deployment (Coming Soon)

```bash
# Quick start with Docker
docker-compose up -d
```

## 🔒 Sicherheit

- ✅ **API Rate Limiting** 
- ✅ **CSRF Protection**
- ✅ **SQL Injection Prevention**
- ✅ **Webhook Signature Verification**
- ✅ **Environment Variable Security**
- ✅ **File Upload Validation**

**Security Audit:** Alle API-Keys wurden aus dem Code entfernt und in sichere Environment Variables migriert.

## 📊 Monitoring

### Health Checks
```bash
# System Status
curl https://your-domain.com/api/health

# Service Status
php artisan horizon:status
php artisan queue:monitor
```

### Performance Monitoring
- **Horizon Dashboard:** `/horizon`
- **Database Monitor:** `php artisan db:monitor`
- **Queue Monitor:** `php artisan queue:monitor`

## 🧪 Testing

```bash
# Unit Tests
php artisan test

# Feature Tests
php artisan test --testsuite=Feature

# Browser Tests (Coming Soon)
php artisan dusk
```

## 📈 Roadmap

### Q4 2025
- [ ] Multi-Channel Support (WhatsApp, SMS)
- [ ] Advanced Analytics Dashboard  
- [ ] Mobile App (React Native)
- [ ] GraphQL API v2

### Q1 2026
- [ ] AI Voice Cloning
- [ ] Multi-Language Support
- [ ] Advanced Integrations (Zapier)
- [ ] White Label Solution

Vollständige Roadmap: [CLAUDE.md#roadmap](CLAUDE.md#roadmap)

## 🆘 Support

### Hilfe & Dokumentation
- 📚 **Vollständige Docs:** [CLAUDE.md](CLAUDE.md)
- 🔧 **Troubleshooting:** [docs/troubleshooting.md](docs/troubleshooting.md)
- 🚀 **Deployment Guide:** [docs/deployment/](docs/deployment/)

### Technischer Support
- 📧 **E-Mail:** support@askproai.de
- 🐛 **Bug Reports:** [GitHub Issues](https://github.com/your-org/askproai/issues)
- 💬 **Discussions:** [GitHub Discussions](https://github.com/your-org/askproai/discussions)

### Notfall-Support
- 🚨 **24/7 Hotline:** +49 (0) 123 456 999
- 📊 **Status Page:** https://status.askproai.de

## 🤝 Contributing

Beiträge sind willkommen! Bitte lesen Sie unsere [Contributing Guidelines](CONTRIBUTING.md).

```bash
# Development Setup
git clone https://github.com/your-org/askproai.git
cd askproai
cp .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate:fresh --seed
```

## 📄 Lizenz

**Proprietary License** - © 2025 AskProAI. Alle Rechte vorbehalten.

Dieses Projekt ist proprietäre Software und nur für autorisierte Benutzer zugänglich.

## 📊 Projektstatistiken

- **Lines of Code:** ~50,000
- **Test Coverage:** 65%+ (Ziel: 80%)
- **Active Tenants:** 100+
- **Calls Processed:** 10,000+ per month
- **Uptime:** 99.9%

---

<p align="center">
  <strong>Entwickelt mit ❤️ von AskProAI Team</strong><br>
  <em>Letzte Aktualisierung: August 2025</em>
</p>
