# Service Creation Scripts

**Purpose**: Wiederverwendbare Scripts für Service-Erstellung mit Cal.com Integration

**Last Updated**: 2025-10-23
**Success Rate**: 100% (Friseur 1, 16 Services)

---

## 📁 Quick Reference

```
scripts/services/
├── create_services_template.php          ← TEMPLATE für neue Services
└── create_services_friseur1_example.php  ← Beispiel (Friseur 1, 16 Services)
```

---

## 🚀 Quick Start

```bash
# 1. Template kopieren
cp scripts/services/create_services_template.php \
   scripts/services/create_services_YOUR_COMPANY.php

# 2. Configuration anpassen (siehe Template)
# - Company ID, Branch ID, Cal.com Team ID
# - Services Array definieren

# 3. Script ausführen
php scripts/services/create_services_YOUR_COMPANY.php
```

**Erwartetes Ergebnis:**
```
=== ZUSAMMENFASSUNG ===
✅ Erfolgreich: 16
❌ Fehler: 0
```

---

## 📚 Vollständige Anleitung

**Runbook**: `claudedocs/09_RUNBOOKS/SERVICE_CREATION_RUNBOOK.md`

Enthält:
- Step-by-Step Anleitung
- Company/Branch/Team ID Identifikation
- Testing & Verification
- Troubleshooting Guide
- Success Metrics

---

## 📋 Was das Script macht

1. **Cal.com Event Types erstellen** im Team
2. **Services in Datenbank anlegen** mit Event Type Verknüpfung
3. **Event Mappings erstellen** für Synchronisation
4. **Verification** mit vollständigem Report

**Alle Schritte atomar** - bei Fehler wird kein teilweiser Zustand hinterlassen.

---

## 🧪 Nach der Erstellung testen

### 1. Admin Portal
```
https://api.askproai.de/admin/services
```
→ Services sichtbar mit Cal.com Event Type IDs

### 2. ServiceNameExtractor
```bash
php -r "
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Retell\ServiceNameExtractor;
\$result = (new ServiceNameExtractor())->extractService('SERVICE_NAME', COMPANY_ID);
echo 'Confidence: ' . \$result['confidence'] . '%' . PHP_EOL;
"
```
→ Erwartung: ≥95% Confidence

### 3. Voice AI End-to-End
```bash
# Terminal 1: Monitoring
./scripts/monitoring/voice_call_monitoring.sh

# Terminal 2: Test-Anruf durchführen
```
→ Erwartung: Buchung erfolgreich ohne SAGA Compensation

---

## 📖 Dokumentation

- **Runbook**: `claudedocs/09_RUNBOOKS/SERVICE_CREATION_RUNBOOK.md`
- **Success Story**: `SERVICE_CREATION_SUCCESS_2025-10-23.md` (Friseur 1)
- **Scripts README**: `scripts/README.md`

---

**Version**: 1.0 | **Tested**: ✅ Friseur 1 (16 Services, 100% Success)
