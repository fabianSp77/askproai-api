# Implementierungsplan: Kundenverwaltung im React Admin Portal

## 🎯 Ziel
Kunden erfolgreich verwalten können - SOFORT!

## 📋 Priorisierte Aufgabenliste

### Phase 1: Customer Detail View (HÖCHSTE PRIORITÄT)

#### 1.1 Customer Detail Modal erweitern
```javascript
// Benötigte Tabs:
- Übersicht (Stammdaten, Status, Tags)
- Timeline (Alle Aktivitäten chronologisch)
- Termine (Alle Appointments des Kunden)
- Anrufe (Alle Calls des Kunden)  
- Notizen (Mit Hinzufügen-Funktion)
- Dokumente (Dateien hochladen/anzeigen)
- Kommunikation (E-Mails, SMS Historie)
```

#### 1.2 API Endpoints implementieren
```
GET /api/customers/{id}/timeline
GET /api/customers/{id}/appointments  
GET /api/customers/{id}/calls
GET /api/customers/{id}/notes
POST /api/customers/{id}/notes
GET /api/customers/{id}/documents
POST /api/customers/{id}/documents
```

#### 1.3 Timeline-Komponente
- Alle Events chronologisch
- Icons für verschiedene Event-Typen
- Filtermöglichkeiten
- Infinite Scroll

### Phase 2: Appointment Management

#### 2.1 Appointment Create/Edit Modal
```javascript
// Felder:
- Kunde auswählen (mit Suche)
- Service/Dienstleistung
- Mitarbeiter
- Datum & Uhrzeit
- Dauer
- Notizen
- Status
```

#### 2.2 Kalenderansicht
- Monats-/Wochen-/Tagesansicht
- Drag & Drop für Termine
- Verfügbarkeiten anzeigen
- Konflikte erkennen

#### 2.3 Status-Management
- Terminbestätigung senden
- Status ändern (confirmed, cancelled, no-show)
- Erinnerungen konfigurieren

### Phase 3: Basis Company Settings

#### 3.1 API-Key Management UI
```javascript
// Komponenten:
- Cal.com API Key (anzeigen/ändern/testen)
- Retell.ai API Key (anzeigen/ändern/testen)
- Webhook URLs anzeigen
- Connection Status
```

#### 3.2 Notification Settings
- E-Mail Benachrichtigungen konfigurieren
- SMS Einstellungen
- Webhook Konfiguration

#### 3.3 Billing Settings
- Billing Rate pro Minute
- Prepaid vs. Postpaid
- Auto-Topup Einstellungen

### Phase 4: Branch Management (Filialverwaltung)

#### 4.1 Branch List View
- Alle Filialen anzeigen
- Status (aktiv/inaktiv)
- Mitarbeiteranzahl
- Öffnungszeiten

#### 4.2 Branch Detail/Edit
- Stammdaten (Name, Adresse, Kontakt)
- Öffnungszeiten konfigurieren
- Mitarbeiter zuordnen
- Services zuordnen

### Phase 5: Team Management

#### 5.1 Staff List View
- Alle Mitarbeiter
- Filter nach Filiale
- Verfügbarkeiten

#### 5.2 Staff Create/Edit
- Persönliche Daten
- Filialzuordnung
- Services die angeboten werden
- Arbeitszeiten
- Cal.com Integration

## 🚀 Sofort-Maßnahmen (Diese Woche)

### Tag 1-2: Customer Detail View
1. CustomerDetailModal mit allen Tabs implementieren
2. Timeline API + Component
3. Notes hinzufügen Funktion

### Tag 3-4: Appointment Management  
1. Create/Edit Modal
2. Status ändern Funktionen
3. Basis-Kalenderansicht

### Tag 5: Company Settings
1. API-Key Management UI
2. Test-Funktionen für APIs
3. Basis Notification Settings

## 📊 Erfolgsmetriken

- [ ] Kunde kann vollständig eingesehen werden (alle Daten, Historie)
- [ ] Notizen können zu Kunden hinzugefügt werden
- [ ] Termine können erstellt und bearbeitet werden
- [ ] API-Keys können verwaltet und getestet werden
- [ ] Filialen können verwaltet werden
- [ ] Mitarbeiter können zugeordnet werden

## 🛠️ Technische Anforderungen

### API-Struktur
```javascript
// Customer Detail Response
{
  id: 1,
  first_name: "Max",
  last_name: "Mustermann",
  email: "max@example.com",
  phone: "+49123456789",
  company: { id: 1, name: "Musterfirma" },
  branch: { id: 1, name: "Hauptfiliale" },
  tags: ["VIP", "Stammkunde"],
  stats: {
    total_appointments: 25,
    completed_appointments: 20,
    no_shows: 2,
    total_spent: 1250.50,
    last_appointment: "2025-07-09",
    customer_since: "2024-01-15"
  },
  timeline: [...],
  notes: [...],
  preferred_contact_method: "email",
  marketing_consent: true,
  portal_access: true
}
```

### React Components Struktur
```
CustomerDetailModal/
├── CustomerOverviewTab.jsx
├── CustomerTimelineTab.jsx
├── CustomerAppointmentsTab.jsx
├── CustomerCallsTab.jsx
├── CustomerNotesTab.jsx
├── CustomerDocumentsTab.jsx
└── CustomerCommunicationTab.jsx

AppointmentManagement/
├── AppointmentCalendar.jsx
├── AppointmentCreateModal.jsx
├── AppointmentEditModal.jsx
└── AppointmentStatusManager.jsx
```

## ⚠️ Wichtige Hinweise

1. **Keine weiteren "Coming Soon" Platzhalter!** - Jede View muss funktional sein
2. **Error Handling** - Jeder API-Call muss Fehler abfangen
3. **Loading States** - Überall wo Daten geladen werden
4. **Validierung** - Alle Formulare müssen validiert werden
5. **Übersetzungen** - Alle neuen Texte müssen übersetzbar sein

## Nächste Schritte

1. Diesen Plan bestätigen
2. Mit Customer Detail View beginnen
3. Schritt für Schritt implementieren
4. Regelmäßig testen
5. Dokumentation aktualisieren