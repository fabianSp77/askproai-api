# Service Creation - Dokumentation & Scripts Index

**Purpose**: Zentrale Übersicht für Service-Erstellung mit Cal.com Integration
**Created**: 2025-10-23
**Status**: ✅ Production Ready (16 Services erfolgreich erstellt)

---

## 🎯 Für zukünftige Service-Erstellung

### Schnellstart (5 Minuten)

```bash
# 1. Template kopieren
cp scripts/services/create_services_template.php \
   scripts/services/create_services_NEW_COMPANY.php

# 2. Template anpassen (siehe Anleitung unten)

# 3. Ausführen
php scripts/services/create_services_NEW_COMPANY.php

# 4. Testen
./scripts/monitoring/voice_call_monitoring.sh
```

### Vollständige Anleitung

📖 **Runbook**: `claudedocs/09_RUNBOOKS/SERVICE_CREATION_RUNBOOK.md`

Enthält:
- ✅ Step-by-Step Anleitung
- ✅ Company/Branch/Team ID Identifikation
- ✅ Script Configuration Guide
- ✅ Testing & Verification
- ✅ Troubleshooting
- ✅ Success Metrics

---

## 📁 Dateien & Locations

### Scripts (wiederverwendbar)

| Datei | Location | Purpose |
|-------|----------|---------|
| **Template** | `scripts/services/create_services_template.php` | Kopieren & anpassen |
| **Beispiel** | `scripts/services/create_services_friseur1_example.php` | Referenz (Friseur 1) |
| **Monitoring** | `scripts/monitoring/voice_call_monitoring.sh` | Live Voice AI Monitoring |

### Dokumentation

| Datei | Location | Purpose |
|-------|----------|---------|
| **Runbook** | `claudedocs/09_RUNBOOKS/SERVICE_CREATION_RUNBOOK.md` | Vollständige Anleitung |
| **Success Story** | `SERVICE_CREATION_SUCCESS_2025-10-23.md` | Beispiel Friseur 1 |
| **Scripts README** | `scripts/services/README.md` | Scripts Übersicht |
| **Dieser Index** | `SERVICE_CREATION_INDEX.md` | Zentrale Übersicht |

---

## 🔍 Was brauche ich für neue Services?

### Vor dem Start ermitteln:

1. **Company ID**
   ```bash
   mysql -u root -p -e "SELECT id, name FROM api_gateway.companies;"
   ```

2. **Branch ID**
   ```bash
   mysql -u root -p -e "SELECT id, name FROM api_gateway.branches WHERE company_id = ?;"
   ```

3. **Cal.com Team ID**
   ```bash
   mysql -u root -p -e "SELECT calcom_team_id, team_name FROM api_gateway.calcom_host_mappings WHERE company_id = ?;"
   ```

### Im Template anpassen:

```php
$companyId = ?;           // ← Company ID
$branchId = '?';          // ← Branch ID (UUID)
$calcomTeamId = ?;        // ← Cal.com Team ID

$services = [
    [
        'name' => 'Service Name',
        'duration' => 30,      // Minuten
        'price' => 25.00,      // EUR
        'category' => 'Schnitt',
        'description' => 'Beschreibung',
        'notes' => null,       // Optional
    ],
    // Weitere Services...
];
```

---

## ✅ Checkliste

**Vorbereitung:**
- [ ] Company ID bekannt
- [ ] Branch ID bekannt
- [ ] Cal.com Team ID bekannt
- [ ] Service-Liste vorbereitet (Name, Dauer, Preis, Kategorie)

**Ausführung:**
- [ ] Template kopiert & angepasst
- [ ] Script ausgeführt: `✅ Erfolgreich: N, ❌ Fehler: 0`
- [ ] Services in DB vorhanden
- [ ] Event Mappings erstellt

**Verification:**
- [ ] Admin Portal: Services sichtbar
- [ ] Cal.com: Event Types im Team
- [ ] ServiceNameExtractor: ≥95% Confidence
- [ ] Voice AI: End-to-End Test erfolgreich

---

## 🚨 Quick Troubleshooting

**Problem: "Column not found: ai_prompt_context"**
→ Template verwendet korrektes Feld (`assignment_notes`)

**Problem: "schedulingType must be one of..."**
→ Template enthält bereits `'schedulingType' => 'COLLECTIVE'`

**Problem: "Cannot POST /v2/event-types"**
→ Template verwendet korrekten Endpoint `/v2/teams/{id}/event-types`

**Siehe vollständiges Troubleshooting:**
`claudedocs/09_RUNBOOKS/SERVICE_CREATION_RUNBOOK.md` → Abschnitt "Troubleshooting"

---

## 📊 Erfolgsbeispiel: Friseur 1 (2025-10-23)

- **Services erstellt**: 16
- **Erfolgsrate**: 100%
- **Dauer**: ~3 Minuten
- **Service Extraction**: 100% Confidence bei Tests
- **Voice AI Test**: ✅ Buchung erfolgreich

**Details**: `SERVICE_CREATION_SUCCESS_2025-10-23.md`

---

## 🔗 Related Documentation

**P0 Fixes (bereits deployed):**
- Deployment Summary: `/tmp/deployment_summary.txt`
- Detaillierte Verification: `DETAILLIERTE_VERIFIZIERUNG_2025-10-23.md`

**Service Name Extraction:**
- ServiceNameExtractor: `app/Services/Retell/ServiceNameExtractor.php`
- Fuzzy Matching mit 60% Confidence Threshold

**Cal.com Integration:**
- CalcomService: `app/Services/CalcomService.php`
- API v2 mit Team-based Event Types

---

## 📞 Support & Nächste Schritte

**Bei Fragen:**
1. Runbook konsultieren: `claudedocs/09_RUNBOOKS/SERVICE_CREATION_RUNBOOK.md`
2. Beispiel ansehen: `scripts/services/create_services_friseur1_example.php`
3. Logs prüfen: `tail -f storage/logs/laravel.log`

**Nächste Schritte nach Service-Erstellung:**
1. Voice AI Test mit Monitoring
2. 24h Performance Monitoring
3. Service Analytics auswerten

---

**Version**: 1.0
**Last Updated**: 2025-10-23
**Status**: ✅ Production Ready
