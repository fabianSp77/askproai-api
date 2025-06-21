# Event-Type Datenfluss Erklärung

## Übersicht: Woher kommen die Event-Types?

Die Event-Types im Setup Wizard kommen **NICHT direkt von Cal.com**, sondern aus der **lokalen AskProAI Datenbank**.

## Datenfluss-Diagramm

```
┌─────────────────┐          ┌─────────────────┐          ┌─────────────────┐
│                 │          │                 │          │                 │
│    Cal.com      │  Import  │  AskProAI DB   │  Anzeige │  Setup Wizard   │
│  (Externe API)  │ -------> │ (calcom_event_  │ -------> │   (Dropdown)    │
│                 │          │     types)      │          │                 │
└─────────────────┘          └─────────────────┘          └─────────────────┘
      Quelle                   Lokale Kopie               Benutzer-Interface
```

## Detaillierte Erklärung

### 1. **Ursprung: Cal.com**
- Event-Types werden in Cal.com erstellt und verwaltet
- Jeder Event-Type hat eine eindeutige ID (z.B. 2563193)
- Cal.com ist die "Single Source of Truth"

### 2. **Import nach AskProAI**
- Event-Types müssen zunächst importiert werden:
  - Manuell über "Event-Type Import Wizard"
  - Automatisch bei der Einrichtung
  - Via API-Synchronisation
- Beim Import werden folgende Daten gespeichert:
  - `calcom_numeric_event_type_id`: Die Cal.com ID
  - `name`: Der Name des Event-Types
  - `company_id`: Zuordnung zum Unternehmen
  - `branch_id`: Zuordnung zur Filiale (optional)

### 3. **Lokale Datenbank (calcom_event_types)**
```sql
-- So sehen die Daten in der Datenbank aus:
SELECT 
    id,                              -- Lokale AskProAI ID
    company_id,                      -- Unternehmen in AskProAI
    name,                            -- Event-Type Name
    calcom_numeric_event_type_id,    -- Cal.com ID
    setup_status,                    -- Konfigurationsstatus
    last_synced_at                   -- Letzte Synchronisation
FROM calcom_event_types;
```

### 4. **Anzeige im Setup Wizard**
- Der Wizard zeigt NUR Event-Types aus der lokalen DB
- Filtert nach ausgewähltem Unternehmen (`company_id`)
- Zeigt zusätzliche Informationen:
  - ✅ = Vollständig konfiguriert
  - ⚠️ = Teilweise konfiguriert  
  - ❌ = Nicht konfiguriert
  - Filialname (falls zugeordnet)
  - Cal.com ID zur Identifikation

## Warum dieser Ansatz?

### Vorteile:
1. **Performance**: Keine API-Calls bei jedem Dropdown-Öffnen
2. **Offline-Fähigkeit**: Funktioniert auch ohne Cal.com Verbindung
3. **Zusätzliche Metadaten**: Wir können lokale Informationen speichern
4. **Multi-Tenancy**: Saubere Trennung nach Unternehmen

### Nachteile:
1. **Synchronisation nötig**: Daten müssen aktuell gehalten werden
2. **Keine Echtzeit-Updates**: Änderungen in Cal.com werden nicht sofort sichtbar

## Synchronisations-Prozess

```
1. Initial-Import
   └─> Event-Types aus Cal.com abrufen
   └─> In lokale DB speichern
   └─> Metadaten initialisieren

2. Konfiguration (Setup Wizard)
   └─> Lokale Daten bearbeiten
   └─> Änderungen zu Cal.com senden
   └─> Status aktualisieren

3. Regelmäßige Sync (geplant)
   └─> Änderungen aus Cal.com holen
   └─> Lokale Daten aktualisieren
   └─> Konflikte auflösen
```

## Zusammenfassung für Benutzer

**"Die Event-Types im Dropdown sind eine lokale Kopie Ihrer Cal.com Event-Types. Sie müssen zuerst importiert werden, bevor sie hier erscheinen."**