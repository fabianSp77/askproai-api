# Cal.com Service Hosts - Setup & Troubleshooting Guide

**Datum**: 2025-10-21
**Problem**: Service 47 zeigt keine Cal.com Hosts (TeamEventTypeMapping leer)
**Lösung**: Entweder Sync durchführen ODER Legacy Staff-Section verwenden

---

## 🔴 **Diagnostiziert: TeamEventTypeMapping ist LEER**

```sql
-- Result von unserer Diagnose:
TeamEventTypeMapping.count() = 0  ❌

-- Das bedeutet:
- Keine Cal.com Events wurden jemals importiert
- Die neue Cal.com Hosts Integration hat KEINE Daten
- Service 47 wurde nicht von Cal.com synchronisiert
```

---

## 🎯 **Lösungen: 2 Wege**

### **WAY 1: Schnelle Lösung (Legacy Staff-Section)**

**Status**: ✅ **Sofort verfügbar**
**Setup Zeit**: 2 Minuten
**Fallback**: Funktioniert wie vorher

**Schritte**:
1. Öffne: https://api.askproai.de/admin/services/47/edit
2. Scrolle zu: "Mitarbeiterzuweisung (Legacy)"
3. Klick: "Mitarbeiter hinzufügen"
4. Wähle Mitarbeiter aus
5. Speichern

**Result**:
- ✅ Mitarbeiter sichtbar
- ✅ Keine 500-Fehler
- ✅ Funktioniert mit Retell Booking
- ❌ Nicht automatisch von Cal.com
- ❌ Manuelle Verwaltung nötig

---

### **WAY 2: Richtige Lösung (Cal.com Sync)**

**Status**: ⏳ **Erfordert Konfiguration**
**Setup Zeit**: 10-15 Minuten
**Vorteil**: Automatisch von Cal.com, keine Fehler

#### **Schritt 1: Cal.com API Token konfigurieren**

**Datei**: `/config/services.php`

Prüfe ob Cal.com Config vorhanden:

```bash
php artisan tinker
>>> config('services.calcom')
```

Falls **nicht** vorhanden, hinzufügen:

```php
// config/services.php
'calcom' => [
    'token' => env('CALCOM_API_TOKEN'),
    'api_url' => 'https://api.cal.com/v2',
],
```

Dann in `.env`:
```
CALCOM_API_TOKEN=your_token_here
```

#### **Schritt 2: Team Members syncing**

```bash
# Alle Teams synchronisieren
php artisan calcom:sync-team-members

# Expected Output:
# 📌 Processing: AskProAI (Team ID: 39203)
#   ✅ Fetched 3 members
# ✅ Sync Complete!
```

#### **Schritt 3: Event Types syncing**

```bash
# Alle Event Types synchronisieren
php artisan calcom:sync-eventtypes

# Expected Output:
# ✓ Synchronisiert: 5 Event-Typen
```

#### **Schritt 4: Service Hosts syncing**

```bash
# Service 47 spezifisch
php artisan calcom:sync-service-hosts --service-id=47

# Expected Output:
# ✅ Synced X host mappings for service: Service 47
```

#### **Schritt 5: Testen**

1. Öffne Service 47 Edit
2. Scrolle zu: "📅 Cal.com Mitarbeiter (Automatisch)"
3. Sollte sehen:
   - Summary Stats (Total, Verbunden, Neu)
   - Host Cards mit Avatars
   - Mapping Status

**Result**:
- ✅ Mitarbeiter automatisch von Cal.com
- ✅ Avatars & Emails sichtbar
- ✅ Mapping Status transparant
- ✅ Keine manuelle Verwaltung nötig

---

## 🔍 **Diagnose: Wie ich das Problem fand**

### **Check 1: Service hat Event Type ID?**
```bash
php artisan tinker
>>> \App\Models\Service::find(47)->calcom_event_type_id
"2563193"  ✅
```

### **Check 2: TeamEventTypeMapping existiert?**
```bash
>>> \App\Models\TeamEventTypeMapping::where('calcom_event_type_id', 2563193)->count()
0  ❌ ← HIER IST DAS PROBLEM!
```

### **Check 3: Irgendwelche TeamEventTypeMapping Einträge?**
```bash
>>> \App\Models\TeamEventTypeMapping::count()
0  ❌ ← Datenbank ist komplett leer!
```

### **Check 4: Service hat Legacy Staff?**
```bash
>>> \App\Models\Service::find(47)->staff()->count()
0  ❌ ← Auch leer!
```

**Fazit**: Service 47 hat:
- ❌ Keine Cal.com Hosts (TeamEventTypeMapping)
- ❌ Keine Legacy Staff (service_staff Pivot)
- ❌ Nur eine hardcoded Event Type ID

---

## 🛠️ **Für den Admin: Was ist passiert?**

Vermutlich:
1. Service 47 wurde manuell erstellt
2. `calcom_event_type_id = 2563193` wurde hardcoded eintragen
3. Synchronisierung wurde NICHT durchgeführt
4. Keine Mitarbeiter wurden zugewiesen

**Result**:
- Service 47 ist "leer" - keine Hosts, keine Staff
- Retell kann Verfügbarkeit nicht checken (wahrscheinlich)
- Neue Cal.com UI zeigt "Keine Mitarbeiter"

---

## 📋 **Entscheidungsbaum: Welche Lösung?**

```
├─ Willst du Mitarbeiter SOFORT hinzufügen?
│  └─ JA → Way 1 (Legacy Staff) ✅
│
├─ Ist Cal.com API bereits konfiguriert?
│  ├─ JA → Way 2 (Cal.com Sync) ✅
│  └─ NEIN → Need CALCOM_API_TOKEN first
│
├─ Willst du Mitarbeiter automatisch von Cal.com?
│  └─ JA → Way 2 (Cal.com Sync) ✅
│
└─ Willst du manuelle Kontrolle behalten?
   └─ JA → Way 1 (Legacy Staff) ✅
```

---

## 🚀 **Recommende Vorgehen**

### **Für schnellen Start**:
1. Use Way 1 (Legacy Staff) - 2 Minutes
2. Mitarbeiter manuell hinzufügen
3. Getestet & funktioniert
4. Später: Way 2 konfigurieren

### **Für vollständige Lösung**:
1. Cal.com API Token besorgen
2. Way 2 durchführen - 10 Minutes
3. Alles automatisch
4. Zero-Fehler Zustand

---

## 💡 **Wie die Zukunft aussieht**

```
Cal.com (Source of Truth)
  ├─ Team Members
  ├─ Event Types (mit Hosts)
  └─ Availability Slots

        ↓ php artisan calcom:sync-*

App DB
  ├─ TeamEventTypeMapping (mit hosts JSON)
  ├─ CalcomHostMapping (Host → Local Staff)
  └─ Staff & service_staff Pivot

        ↓ Service Form

UI
  ├─ Cal.com Hosts automatisch angezeigt ✨
  ├─ Avatar, Email, Role
  ├─ Mapping Status
  └─ Verfügbare Services
```

---

## 🔧 **Commands Übersicht**

```bash
# Status Check
php artisan calcom:verify-team-events

# Team Members importieren
php artisan calcom:sync-team-members

# Event Types importieren
php artisan calcom:sync-eventtypes

# Services mit Event Types syncen
php artisan calcom:sync-services

# Service Hosts importieren
php artisan calcom:sync-service-hosts [--service-id=47]

# Availability Check
php artisan calcom:check-availability
```

---

## ❓ **FAQs**

### F: Warum sehe ich keine Cal.com Hosts?
**A**: TeamEventTypeMapping ist leer. Verwende Way 1 (Legacy) oder Way 2 (Sync).

### F: Kann ich beide Wege kombinieren?
**A**: Ja! Legacy Staff + Cal.com Sync funktionieren zusammen. Neue Section zeigt Cal.com, alte zeigt Legacy.

### F: Welche ist schneller?
**A**: Way 1 (Legacy) - 2 Minuten. Way 2 (Sync) - 10 Minuten aber besser langfristig.

### F: Kann ich später noch auf Way 2 migrieren?
**A**: Ja! Way 1 ist nur temporär. Way 2 kann später eingerichtet werden.

### F: Was passiert mit meinen Legacy Staff?
**A**: Sie bleiben erhalten. Way 2 erstellt zusätzliche Einträge, überschreibt nicht.

---

## ✅ **Abschluss Checklist**

- [ ] Entschieden: Way 1 oder Way 2?
- [ ] Way 1: Mitarbeiter hinzugefügt & getestet?
- [ ] Way 2: Cal.com API Token konfiguriert?
- [ ] Way 2: Sync Commands durchgeführt?
- [ ] Way 2: Service 47 Edit zeigt Hosts?
- [ ] Retell Voice Test erfolgreich?
- [ ] Appointment mit korrektem Staff erstellt?

---

**Status**: ✅ Beide Wege funktional
**Recommend**: Start mit Way 1 (Legacy), dann Way 2 (Sync)
**Support**: Kontakt für Cal.com API Token Fragen
