# AskProAI Konsistenz-Masterplan: 100% Klarheit
**Datum**: 22.06.2025  
**Ziel**: Ein absolut konsistentes Portal ohne Verwirrung

## 🎯 Die EINE klare Struktur

### Hierarchie (von oben nach unten):
```
Firma (Company)
  └── Filialen (Branches)
        ├── Telefonnummern (Phone Numbers) → mit Retell Agent
        ├── Mitarbeiter (Staff)
        ├── Services 
        └── Event Types (Cal.com)
```

## 📱 Telefon → Termin: Der EINE Weg

### Der klare Datenfluss:
```
1. Kunde ruft an: +49 30 123456
2. System findet: PhoneNumber → Branch → Company
3. PhoneNumber hat: retell_agent_id
4. Agent führt Gespräch
5. Agent sammelt Daten mit collect_appointment_data()
6. Webhook kommt an: /api/retell/webhook
7. System erstellt Termin in Cal.com
8. Kunde bekommt Bestätigung
```

**KEINE anderen Wege!** Keine Fallbacks, keine Alternative Flows.

## 🏢 Company Integration Portal: Die EINE Zentrale

### Was gehört ins Portal:

#### 1. **Firma-Einstellungen** (Oberste Ebene)
```
Company Settings
├── Basis-Daten (Name, Adresse, etc.)
├── API Keys (Retell, Cal.com)
├── Globale Einstellungen
└── Rechnungsdaten
```

#### 2. **Filial-Verwaltung** (Pro Filiale)
```
Branch Management
├── Basis-Daten (Name, Adresse, Öffnungszeiten)
├── Telefonnummern → Agent Zuordnung
├── Mitarbeiter
├── Services
└── Event Types (aus Cal.com)
```

#### 3. **Telefonnummer → Agent** (Kritischster Teil)
```
Phone Number Management
├── Nummer hinzufügen/bearbeiten
├── Retell Agent zuordnen
├── Agent-Einstellungen (Prompt, Voice, etc.)
└── Test-Anruf Funktion
```

## ❌ Was RAUS muss (Inkonsistenzen beseitigen)

### 1. **Doppelte Tabellen - LÖSCHEN:**
```sql
-- Diese bleiben:
✅ companies
✅ branches  
✅ phone_numbers
✅ staff
✅ services
✅ calcom_event_types
✅ appointments
✅ customers

-- Diese werden GELÖSCHT:
❌ unified_event_types
❌ calendar_event_types
❌ event_type_mappings (nutze branch_event_types)
❌ service_event_type_mappings
❌ staff_service_assignments_backup
❌ staff_branches (nutze staff.branch_id)
❌ branch_staff
```

### 2. **Service-Chaos - KONSOLIDIEREN:**
```php
// NUR DIESE Services bleiben:
✅ CalcomService (nutzt intern V2 API)
✅ RetellService (einheitliche Implementation)
✅ AppointmentBookingService

// LÖSCHEN:
❌ CalcomV2Service
❌ CalcomService_v1_only
❌ CalcomMigrationService
❌ RetellV2Service
❌ UniversalBookingOrchestrator
❌ Alle anderen Duplikate
```

### 3. **Konfiguration - EINE Quelle:**
```php
// API Keys NUR hier:
companies.retell_api_key
companies.calcom_api_key

// NICHT mehr in:
❌ .env (nur für System-Settings)
❌ branches Tabelle
❌ Irgendwo anders
```

### 4. **Agent-Konfiguration - NUR bei Phone Numbers:**
```sql
-- Agent ID NUR hier:
phone_numbers.retell_agent_id

-- ENTFERNEN aus:
❌ branches.retell_agent_id
❌ companies.retell_agent_id
❌ Alle anderen Orte
```

## 🔧 Portal-Features: Klar strukturiert

### 1. **Dashboard** (Übersicht)
- Heutige Termine
- Aktive Anrufe
- System-Status
- Wichtige Metriken

### 2. **Filialen** (Branch Management)
```
Für jede Filiale:
├── Stammdaten bearbeiten
├── Öffnungszeiten
├── Telefonnummern
│   ├── Nummer hinzufügen
│   ├── Agent zuordnen
│   └── Agent-Prompt bearbeiten ⭐ NEU
├── Mitarbeiter
│   ├── Zuordnen/Entfernen  
│   └── Arbeitszeiten
├── Services
│   └── Aktivieren/Deaktivieren
└── Event Types
    ├── Von Cal.com synchronisieren
    ├── Primary setzen
    └── Zuordnungen verwalten
```

### 3. **Retell Agent Management** ⭐ NEU
```
Agent Editor:
├── Prompt bearbeiten (direkt im Portal!)
├── Voice-Einstellungen
├── Test-Anruf starten
├── Performance-Metriken
└── A/B Testing
```

### 4. **Einstellungen**
```
Globale Einstellungen:
├── API Keys
├── Webhook URLs  
├── Email-Templates
└── System-Konfiguration
```

## 📊 Klare Datenbank-Struktur

### Phone Number Resolution (Der EINZIGE Weg):
```php
// So und NUR so:
public function resolvePhoneNumber($number) 
{
    $phone = PhoneNumber::where('number', $number)
        ->where('is_active', true)
        ->with(['branch.company'])
        ->first();
        
    if (!$phone) {
        throw new PhoneNotFoundException();
    }
    
    return [
        'phone_id' => $phone->id,
        'branch_id' => $phone->branch_id,
        'company_id' => $phone->branch->company_id,
        'agent_id' => $phone->retell_agent_id
    ];
}
```

### Event Type Zuordnung (Der EINZIGE Weg):
```php
// Service Name → Event Type
public function matchServiceToEventType($serviceName, $branchId)
{
    // 1. Exakte Übereinstimmung
    $eventType = DB::table('branch_event_types')
        ->join('calcom_event_types', 'calcom_event_types.id', '=', 'branch_event_types.calcom_event_type_id')
        ->where('branch_id', $branchId)
        ->where('calcom_event_types.name', $serviceName)
        ->first();
        
    if ($eventType) return $eventType;
    
    // 2. Intelligente Zuordnung
    return EventTypeMatchingService::findBestMatch($serviceName, $branchId);
}
```

## 🚀 Migrations-Plan

### Woche 1: Datenbank-Bereinigung
```sql
-- 1. Backup aller Daten
-- 2. Daten konsolidieren
-- 3. Alte Tabellen löschen
-- 4. Foreign Keys neu setzen
```

### Woche 2: Service-Konsolidierung  
```php
// 1. Alle Calls auf neue Services umleiten
// 2. Alte Services als deprecated markieren
// 3. Tests anpassen
// 4. Alte Services löschen
```

### Woche 3: Portal-Vereinfachung
```php
// 1. Company Integration Portal als Hauptzentrale
// 2. Alle anderen Wizards entfernen
// 3. Navigation vereinfachen
// 4. Konsistente UI/UX
```

## ✅ Checkliste für 100% Konsistenz

### Datenbank:
- [ ] Nur 25 Kern-Tabellen (statt 119)
- [ ] Klare Foreign Key Beziehungen
- [ ] Keine Duplikate oder Redundanzen
- [ ] Single Source of Truth für alles

### Services:
- [ ] Ein Service pro Integration
- [ ] Keine V1/V2 Verwirrung
- [ ] Klare Service-Verantwortlichkeiten
- [ ] Konsistente Fehlerbehandlung

### Portal:
- [ ] Eine zentrale Verwaltung
- [ ] Klare Navigation
- [ ] Keine doppelten Features
- [ ] Alles an einem Ort

### Datenfluss:
- [ ] Ein Weg für Phone → Branch
- [ ] Ein Weg für Booking
- [ ] Ein Weg für Konfiguration
- [ ] Keine verwirrenden Fallbacks

## 🎯 Endergebnis

Ein Portal wo:
1. **Jeder sofort weiß** wo was eingestellt wird
2. **Keine Verwirrung** über mehrere Wege existiert
3. **Alles konsistent** an einem Ort ist
4. **Keine Legacy-Reste** mehr vorhanden sind
5. **100% Klarheit** herrscht

## 💡 Nächster Schritt

1. **Heute**: Diesen Plan absegnen
2. **Morgen**: Mit Datenbank-Migration beginnen
3. **Diese Woche**: Service-Konsolidierung
4. **Nächste Woche**: Portal-Bereinigung
5. **In 2 Wochen**: Sauberes, konsistentes System!

---

**Das ist der Weg zu einem konsistenten System ohne Chaos!**