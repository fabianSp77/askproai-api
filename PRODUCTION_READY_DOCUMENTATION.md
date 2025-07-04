# AskProAI Production Ready Documentation

## 📋 Inhaltsverzeichnis

1. [Übersicht](#übersicht)
2. [Implementierte Features](#implementierte-features)
3. [Quick Start Guide](#quick-start-guide)
4. [Administratoren-Handbuch](#administratoren-handbuch)
5. [Technische Details](#technische-details)
6. [Monitoring & Performance](#monitoring--performance)
7. [Troubleshooting](#troubleshooting)
8. [Wartung & Updates](#wartung--updates)

## 🎯 Übersicht

AskProAI ist jetzt vollständig bereit für den produktiven Multi-Tenant-Betrieb mit mehreren Unternehmen. Diese Dokumentation beschreibt alle implementierten Features und deren Nutzung.

### Produktionsstatus

- ✅ **Multi-Tenant Ready**: Vollständige Mandantentrennung
- ✅ **Phone AI Integration**: Retell.ai mit Custom Functions
- ✅ **Automated Onboarding**: Industry-spezifische Templates
- ✅ **System Monitoring**: Real-time Dashboard
- ✅ **Performance Optimized**: Caching, Indexes, Query-Optimierung

## 🚀 Implementierte Features

### Phase 1: Kritische Fixes
1. **Retell.ai Custom Functions**
   - ExtractAppointmentDetailsFunction
   - IdentifyCustomerFunction
   - DetermineServiceFunction
   - AppointmentBookingFunction
   - Datenextraktion von 16% auf 80%+ verbessert

2. **Webhook Security**
   - HMAC-SHA256 Signature Verification
   - Replay Attack Protection
   - Rate Limiting

3. **Multi-Branch Support**
   - Branch Context Management
   - Filialübergreifende Ansichten
   - Branch-spezifische Dashboards

### Phase 2: Setup & Tools
1. **Quick Setup Wizard V2**
   - Progress Bar mit Connection Lines
   - Alpine.js Form Interactions
   - Industry Templates
   - Auto-Configuration

2. **Automated Onboarding**
   ```bash
   php artisan onboarding:automated --industry=medical --branches=3
   ```
   - Branchen: medical, beauty, legal, fitness, automotive
   - Automatische Konfiguration von Services, Arbeitszeiten, Retell Agents

3. **Preflight Checks**
   ```bash
   php artisan preflight:check
   ```
   - Database Connectivity
   - API Integrations
   - Queue System
   - Security Settings

### Phase 3: Monitoring & Performance
1. **System Monitoring Dashboard**
   - URL: `/admin/system-monitoring-dashboard`
   - Real-time Metrics
   - API Health Checks
   - Queue Monitoring
   - Auto-Refresh (30s)

2. **Performance Analyzer**
   ```bash
   php artisan performance:analyze
   ```
   - Slow Query Detection
   - Index Recommendations
   - Cache Analysis
   - N+1 Query Detection

## 📖 Quick Start Guide

### Neues Unternehmen anlegen

1. **Manuell über Admin Panel**
   ```
   Admin → Companies → Create
   ```

2. **Automatisiert per Command**
   ```bash
   # Beispiel: Arztpraxis mit 2 Standorten
   php artisan onboarding:automated \
     --name="Dr. Schmidt Praxis" \
     --industry=medical \
     --branches=2 \
     --email=info@dr-schmidt.de \
     --phone=+4930123456
   ```

3. **Quick Setup Wizard**
   - Nach Company-Erstellung: "Quick Setup" Button
   - Schritt-für-Schritt Konfiguration
   - Automatische Retell Agent Erstellung

### Retell.ai Konfiguration

1. **Agent Template auswählen**
   ```
   Admin → Retell Configuration → Agent Templates
   ```

2. **Custom Functions aktivieren**
   - Appointment Booking
   - Customer Identification
   - Service Selection

3. **Phone Number zuweisen**
   ```
   Admin → Phone Numbers → Assign to Branch
   ```

## 👨‍💼 Administratoren-Handbuch

### System-Überwachung

1. **Live Dashboard**
   ```
   Admin → System → System Monitoring
   ```
   - Database Status
   - API Verfügbarkeit
   - Queue Backlog
   - Active Calls

2. **Performance Checks**
   ```bash
   # Vollständige Analyse
   php artisan performance:analyze
   
   # Nur langsame Queries
   php artisan performance:analyze --query
   
   # Mit automatischen Fixes
   php artisan performance:analyze --fix
   ```

3. **Health Checks**
   ```bash
   # System Health Check
   php artisan monitoring:health-check
   
   # Mit Alerts
   php artisan monitoring:health-check --alert
   ```

### Wartungsaufgaben

1. **Tägliche Checks**
   - Failed Jobs prüfen
   - API Response Times
   - Queue Sizes
   - Disk Space

2. **Wöchentliche Aufgaben**
   - Performance Analyse
   - Backup Verification
   - Security Audit
   - Index Optimization

3. **Monatliche Reviews**
   - Inactive Companies
   - Database Size
   - Log Rotation
   - API Usage

## 🔧 Technische Details

### Architektur

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│   Phone Call    │────▶│   Retell.ai     │────▶│  Webhook API    │
└─────────────────┘     └─────────────────┘     └─────────────────┘
                                                          │
                                                          ▼
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│   Cal.com API   │◀────│  Booking Logic  │◀────│  Queue Worker   │
└─────────────────┘     └─────────────────┘     └─────────────────┘
```

### Database Schema

```sql
-- Multi-Tenant Structure
companies (id, name, subscription_status)
├── branches (id, company_id, name, timezone)
│   ├── staff (id, branch_id, name, email)
│   ├── services (id, branch_id, name, duration)
│   └── phone_numbers (id, branch_id, number)
├── customers (id, company_id, name, phone)
└── appointments (id, company_id, branch_id, customer_id)
```

### Security Features

1. **Data Isolation**
   - TenantScope Global Scope
   - Company-based filtering
   - Branch-level permissions

2. **API Security**
   - Webhook Signature Verification
   - Rate Limiting
   - Circuit Breaker Pattern

3. **Encryption**
   - API Keys encrypted at rest
   - Sensitive data protection
   - SSL/TLS enforcement

## 📊 Monitoring & Performance

### Key Metrics

1. **System Metrics**
   - CPU Usage < 80%
   - Memory Usage < 90%
   - Disk Usage < 85%
   - Database Connections < 100

2. **Business Metrics**
   - Calls per Hour
   - Appointments per Day
   - Conversion Rate
   - No-Show Rate

3. **API Performance**
   - Cal.com Response < 500ms
   - Retell.ai Response < 1000ms
   - Webhook Processing < 300ms

### Alerting

```bash
# Configure alerts
php artisan monitoring:configure-alerts \
  --email=admin@askproai.de \
  --threshold-cpu=80 \
  --threshold-memory=90
```

## 🛠 Troubleshooting

### Häufige Probleme

1. **"No appointments available"**
   ```bash
   # Check staff assignments
   php artisan debug:appointments --branch=UUID
   
   # Verify working hours
   php artisan debug:working-hours --staff=UUID
   ```

2. **Webhook Failures**
   ```bash
   # Check webhook logs
   tail -f storage/logs/webhooks.log
   
   # Retry failed webhooks
   php artisan webhooks:retry --hours=24
   ```

3. **Slow Performance**
   ```bash
   # Run performance analysis
   php artisan performance:analyze --fix
   
   # Clear all caches
   php artisan optimize:clear
   ```

### Debug Commands

```bash
# Test Retell Integration
php artisan retell:test-call --phone=+4930123456

# Test Cal.com Sync
php artisan calcom:sync-check --company=UUID

# Check Queue Status
php artisan horizon:status
```

## 🔄 Wartung & Updates

### Backup Strategy

```bash
# Database Backup
mysqldump -u root -p askproai_db > backup_$(date +%Y%m%d).sql

# File Backup
tar -czf storage_backup_$(date +%Y%m%d).tar.gz storage/
```

### Update Process

1. **Pre-Update**
   ```bash
   php artisan preflight:check
   php artisan backup:create
   ```

2. **Update**
   ```bash
   git pull origin main
   composer install --no-dev
   php artisan migrate --force
   php artisan optimize
   ```

3. **Post-Update**
   ```bash
   php artisan preflight:check
   php artisan monitoring:health-check
   ```

### Maintenance Mode

```bash
# Enable maintenance
php artisan down --message="Scheduled maintenance" --retry=60

# Disable maintenance
php artisan up
```

## 📞 Support & Kontakt

### Technischer Support
- **Email**: tech@askproai.de
- **Emergency**: +49 30 XXXXXXX

### Dokumentation
- **Admin Guide**: `/docs/admin`
- **API Docs**: `/docs/api`
- **Troubleshooting**: `/docs/troubleshooting`

### Nützliche Links
- [Retell.ai Dashboard](https://dashboard.retellai.com)
- [Cal.com Dashboard](https://app.cal.com)
- [System Status](https://status.askproai.de)

---

**Version**: 1.0.0  
**Letzte Aktualisierung**: 2025-07-01  
**Autor**: AskProAI Development Team