# AskProAI: Kompletter Datenfluss von Anruf zu Termin

## Übersicht
Dieses Dokument beschreibt den vollständigen Datenfluss, wie bei einem eingehenden Anruf die richtige Firma und Filiale identifiziert wird, und wie anschließend der Termin im richtigen Kalender erstellt wird.

## 1. Telefonnummer-Zuordnung (Phone Number Resolution)

### 1.1 Datenbank-Struktur

#### Tabelle: `phone_numbers`
```sql
- id (UUID)
- branch_id (UUID) → Verweist auf branches.id
- number (String, unique) → Normalisierte Telefonnummer
- active (Boolean)
- agent_id (String, nullable) → Retell Agent ID (falls vorhanden)
```

#### Tabelle: `branches`
```sql
- id (UUID)
- company_id (UUID) → Verweist auf companies.id
- phone_number (String) → Haupttelefonnummer der Filiale
- retell_agent_id (String) → Retell.ai Agent ID für diese Filiale
- calcom_event_type_id (Integer) → Standard Event Type für Buchungen
- calcom_api_key (String) → Cal.com API Key (falls filialspezifisch)
```

#### Tabelle: `companies`
```sql
- id (UUID)
- retell_api_key (String) → Retell.ai API Key
- calcom_api_key (String) → Cal.com API Key (falls global)
- default_calcom_team_slug (String) → Cal.com Team
```

### 1.2 PhoneNumberResolver Service

Der `PhoneNumberResolver` Service identifiziert die Filiale in folgender Reihenfolge:

1. **Retell Metadata Check**: Prüft ob `askproai_branch_id` in den Webhook-Metadaten vorhanden ist
2. **Telefonnummer-Auflösung**: 
   - Normalisiert die angerufene Nummer (to_number)
   - Sucht in `phone_numbers` Tabelle
   - Sucht in `branches.phone_number`
3. **Agent ID Auflösung**:
   - Sucht über `retell_agent_id` in `agents` Tabelle
   - Sucht über `retell_agent_id` in `branches` Tabelle
4. **Fallback**: Erste Filiale der ersten Firma

## 2. Retell.ai Webhook Flow

### 2.1 Webhook Empfang
```
POST /api/retell/webhook
```

### 2.2 Webhook Verarbeitung
1. **Signature Verification**: `VerifyRetellSignature` Middleware
2. **Job Dispatch**: `ProcessRetellCallEndedJob` wird in Queue eingereiht
3. **Datenextraktion**:
   ```php
   $callData = [
       'call_id' => $webhookData['call']['call_id'],
       'agent_id' => $webhookData['call']['agent_id'],
       'from_number' => $webhookData['call']['from_number'],
       'to_number' => $webhookData['call']['to_number'],
       // ... weitere Felder
   ];
   ```

### 2.3 Branch Resolution
```php
$resolver = new PhoneNumberResolver();
$resolved = $resolver->resolveFromWebhook($callData);
// Ergebnis:
[
    'branch_id' => 'uuid-der-filiale',
    'company_id' => 'uuid-der-firma',
    'agent_id' => 'lokale-agent-id'
]
```

## 3. Terminbuchungs-Flow

### 3.1 Appointment Data Collection
Die Retell.ai Agents nutzen die `collect_appointment_data` Funktion, die folgende Felder sammelt:
- `datum` (Datum)
- `uhrzeit` (Uhrzeit)
- `name` (Kundenname)
- `telefonnummer` (Telefonnummer)
- `dienstleistung` (Service)
- `email` (E-Mail, optional)
- `mitarbeiter_wunsch` (Mitarbeiterwunsch, optional)

### 3.2 AppointmentBookingService
Der Service verarbeitet die Buchung:

1. **Customer Resolution/Creation**:
   ```php
   $customer = Customer::firstOrCreate([
       'phone' => $phoneNumber,
       'company_id' => $resolved['company_id']
   ]);
   ```

2. **Branch Assignment**:
   - Nutzt die aus PhoneNumberResolver ermittelte `branch_id`
   - Fallback: Erste Filiale der Firma

3. **Service/Staff Matching**:
   - Sucht passende Services basierend auf Kundenangabe
   - Optional: Sucht gewünschten Mitarbeiter

## 4. Cal.com Integration

### 4.1 Event Type Zuordnung

#### Hierarchie der Event Type Auflösung:
1. **Staff-spezifisch**: `staff_event_types` Tabelle
2. **Filial-Standard**: `branches.calcom_event_type_id`
3. **Service-Mapping**: `calcom_event_types.service_id`
4. **Firma-Standard**: `companies.default_event_type_id`

#### Tabelle: `calcom_event_types`
```sql
- id (Integer)
- company_id (UUID)
- branch_id (UUID, nullable)
- service_id (UUID, nullable)
- calcom_event_type_id (Integer) → Cal.com Event Type ID
- name (String)
```

#### Tabelle: `staff_event_types`
```sql
- staff_id (UUID)
- event_type_id (Integer) → calcom_event_types.id
- is_primary (Boolean)
- calcom_user_id (String) → Cal.com User ID
```

### 4.2 Booking Creation
```php
// CalcomV2Service
$booking = $calcomService->createBooking([
    'eventTypeId' => $eventType->calcom_event_type_id,
    'start' => $appointment->starts_at,
    'end' => $appointment->ends_at,
    'name' => $customer->name,
    'email' => $customer->email,
    'phone' => $customer->phone,
    'timeZone' => 'Europe/Berlin',
]);
```

## 5. Vollständiger Datenfluss-Diagramm

```
1. Kunde ruft Telefonnummer an
   ↓
2. Retell.ai Agent antwortet
   - Agent ID ist mit Filiale verknüpft
   - Sammelt Termindaten via collect_appointment_data
   ↓
3. Call beendet → Webhook an AskProAI
   - to_number: Angerufene Nummer
   - agent_id: Retell Agent ID
   - Termindaten in retell_llm_dynamic_variables
   ↓
4. PhoneNumberResolver identifiziert:
   - Company (Firma/Mandant)
   - Branch (Filiale)
   - Optional: Specific Agent
   ↓
5. ProcessRetellCallEndedJob:
   - Erstellt/Updated Call Record
   - Extrahiert Appointment Data
   - Ruft AppointmentBookingService auf
   ↓
6. AppointmentBookingService:
   - Erstellt/Findet Customer
   - Validiert Service & Staff
   - Prüft Verfügbarkeit
   - Erstellt Appointment Record
   ↓
7. Cal.com Sync:
   - Ermittelt richtigen Event Type
   - Erstellt Booking via API
   - Speichert calcom_booking_id
   ↓
8. Bestätigungen:
   - E-Mail an Kunde
   - SMS (wenn implementiert)
   - Benachrichtigung an Mitarbeiter
```

## 6. Kritische Konfigurationspunkte

### 6.1 Retell.ai Setup
- Jede Filiale benötigt eigenen Agent oder gemeinsamen Agent mit Metadata
- Agent muss `collect_appointment_data` Funktion haben
- Webhook URL muss konfiguriert sein: `https://domain.com/api/retell/webhook`

### 6.2 Telefonnummern-Zuordnung
- Entweder in `phone_numbers` Tabelle
- Oder als `phone_number` in `branches` Tabelle
- Nummern müssen normalisiert sein (+49...)

### 6.3 Cal.com Event Types
- Müssen via Sync importiert werden
- Zuordnung zu Services/Staff/Branches
- API Keys auf Company oder Branch Level

## 7. Fehlerbehandlung & Fallbacks

### Wenn keine Filiale gefunden wird:
1. Loggt Warnung
2. Nutzt erste Filiale der ersten Firma
3. Termin wird trotzdem erstellt

### Wenn kein Event Type gefunden wird:
1. Sucht Standard-Event-Type der Firma
2. Erstellt Termin ohne Cal.com Sync
3. Manuelle Nachbearbeitung erforderlich

### Wenn Cal.com Sync fehlschlägt:
1. Termin bleibt in lokaler DB
2. Retry-Mechanismus via Queue
3. Admin-Benachrichtigung bei wiederholtem Fehler

## 8. Monitoring & Debugging

### Log-Einträge prüfen:
```bash
tail -f storage/logs/laravel.log | grep -E "PhoneNumberResolver|ProcessRetellCallEndedJob|AppointmentBookingService"
```

### Wichtige Debug-Punkte:
1. `PhoneNumberResolver::resolveFromWebhook` - Branch-Auflösung
2. `ProcessRetellCallEndedJob::handle` - Webhook-Verarbeitung
3. `AppointmentBookingService::bookFromPhoneCall` - Buchungslogik
4. `CalcomV2Service::createBooking` - Cal.com API Calls

### Admin-Tools:
- `/admin/calls` - Alle Anrufe mit zugeordneten Branches
- `/admin/appointments` - Termine mit Cal.com Status
- `/admin/system-health` - Integration Status