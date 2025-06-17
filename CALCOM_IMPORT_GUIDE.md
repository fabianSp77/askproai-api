# 📅 Cal.com Event Types & Personen Import Guide

## 🎯 Aktueller Status für AskProAI Berlin

### ✅ Was bereits importiert ist:
1. **Event Types:**
   - "30 Minuten Termin mit Fabian Spitzer" (ID: 2026360)
   - "AskProAI 30% mehr Umsatz..." (ID: 2026361)

2. **Personen:**
   - Fabian Spitzer (Cal.com User ID: 1414768)
   - Zuordnung zu beiden Event Types vorhanden

### ⚠️ Was fehlt/zu tun ist:
- Sync Status ist noch "pending" (nicht vollständig synchronisiert)
- Möglicherweise weitere Event Types auf Cal.com vorhanden

## 🔄 Import-Prozess über Admin Panel

### 1. **Event Type Import Wizard** (Empfohlen!)
```
Admin Panel → Kalender & Events → Event-Type Import
```

**Schritte:**
1. Wähle Company: "AskProAI"
2. Wähle Branch: "AskProAI – Berlin"
3. System lädt alle Event Types von Cal.com
4. Vorschau zeigt:
   - Name des Event Types
   - Personen-Zuordnung (automatisch erkannt)
   - Team/Personal Event
5. Import ausführen

### 2. **Staff-Event Zuordnung**
```
Admin Panel → Personal & Teams → Mitarbeiter-Event-Zuordnung
```

**Features:**
- Matrix-Ansicht aller Mitarbeiter und Event Types
- Performance-Daten (wie viele Termine pro Person)
- KI-basierte Vorschläge für optimale Zuordnung
- Bulk-Operationen möglich

### 3. **Alternative: Unified Event Types**
```
Admin Panel → Kalender & Events → Vereinheitlichte Event-Typen
```

Zeigt alle importierten Event Types mit:
- Sync Status
- Zugeordnete Mitarbeiter
- Letzte Synchronisation

## 🛠️ Manuelle Import-Commands

### Wenn UI nicht funktioniert:

1. **Fix für Type Error in CacheService:**
```bash
# Temporärer Fix - Company ID als Integer casten
php artisan tinker
>>> $branch = \App\Models\Branch::find('7362c5a9-7d2b-46cd-9bcb-d69f6a60c73b');
>>> $companyId = (int) $branch->company_id;
>>> echo $companyId; // Sollte 85 sein
```

2. **Direkter API Test:**
```php
// Test Cal.com API direkt
$service = new \App\Services\CalcomV2Service();
$eventTypes = $service->getEventTypes();
print_r($eventTypes);
```

## 📊 Datenbank-Struktur

### Event Types werden gespeichert in:
- `calcom_event_types` - Haupttabelle
- `staff_event_types` - Zuordnungen zu Mitarbeitern
- `unified_event_types` - Vereinheitlichte Ansicht

### Wichtige Felder:
- `calcom_event_type_id` - ID von Cal.com
- `slug` - URL-Slug für Buchungen
- `is_team_event` - Team oder Personal Event
- `sync_status` - pending/synced/failed

## 🔍 Debugging

### Prüfen ob alle Event Types da sind:
```sql
SELECT id, name, slug, is_team_event, sync_status 
FROM calcom_event_types 
WHERE company_id = 85;
```

### Prüfen ob Personen-Zuordnung korrekt:
```sql
SELECT s.name, cet.name, set.is_primary 
FROM staff_event_types set
JOIN staff s ON s.id = set.staff_id
JOIN calcom_event_types cet ON cet.id = set.event_type_id
WHERE s.company_id = 85;
```

## ✅ Best Practice Workflow

1. **Import über EventTypeImportWizard** (UI)
2. **Überprüfung in "Vereinheitlichte Event-Typen"**
3. **Feinabstimmung in "Mitarbeiter-Event-Zuordnung"**
4. **Test-Buchung durchführen**

## 🚨 Wichtige Hinweise

- **Cal.com User ID ist essentiell!** Ohne diese kann keine Buchung erfolgen
- **Team Events** benötigen mindestens einen zugeordneten Mitarbeiter
- **Sync Status** sollte "synced" sein, nicht "pending"
- Bei Änderungen in Cal.com muss Re-Import erfolgen

## 🎯 Nächste Schritte für Sie:

1. Gehen Sie zu: **Admin Panel → Kalender & Events → Event-Type Import**
2. Führen Sie den Import-Wizard aus
3. Prüfen Sie die Zuordnungen
4. Machen Sie einen Test-Anruf

Der Import-Wizard ist die beste Methode, da er:
- Automatisch Personen erkennt
- Validierungen durchführt
- Import-Logs erstellt
- Fehler anzeigt