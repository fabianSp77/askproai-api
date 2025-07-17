# Customer Management Priority Implementation Plan

## 🎯 Ziel
Schnellstmögliche Implementierung der kritischen Features für erfolgreiche Kundenverwaltung im React Admin Portal.

## 📅 Timeline: 5 Tage Sprint

### Tag 1-2: Customer Detail View mit Timeline
**Priorität**: 🔴 KRITISCH

#### Komponenten zu erstellen:
```javascript
// 1. CustomerDetailView.jsx
- Vollständige Kundeninformationen
- Timeline mit allen Aktivitäten
- Notizen-System
- Portal-Zugang Management

// 2. CustomerTimeline.jsx
- Chronologische Aktivitätsliste
- Anrufe, Termine, Notizen
- Filterbare Timeline
- Echtzeit-Updates via WebSocket

// 3. CustomerNotes.jsx
- Notizen hinzufügen/bearbeiten
- Kategorisierung
- Anhänge
```

#### API Endpoints benötigt:
- `GET /api/admin/customers/{id}/timeline`
- `POST /api/admin/customers/{id}/notes`
- `PUT /api/admin/customers/{id}/portal-access`

### Tag 2-3: Appointment Create/Edit Modal
**Priorität**: 🔴 KRITISCH

#### Features:
```javascript
// 1. AppointmentModal.jsx
- Kunde auswählen (mit Suche)
- Service/Mitarbeiter/Filiale wählen
- Datum/Zeit mit Verfügbarkeitsprüfung
- Notizen und besondere Anforderungen

// 2. AppointmentCalendar.jsx
- Kalenderansicht
- Drag & Drop Terminverschiebung
- Verfügbarkeitsanzeige
- Multi-View (Tag/Woche/Monat)
```

#### Integration:
- Cal.com API für Verfügbarkeit
- Echtzeit-Validierung
- Konfliktprüfung

### Tag 3-4: Company Settings
**Priorität**: 🔴 KRITISCH

#### Tabs zu implementieren:
```javascript
// 1. GeneralSettings.jsx
- Unternehmensdetails
- Kontaktinformationen
- Öffnungszeiten

// 2. ApiKeySettings.jsx
- Retell.ai API Key
- Cal.com API Key
- Webhook URLs
- Test-Funktionen

// 3. NotificationSettings.jsx
- Email-Benachrichtigungen
- SMS-Einstellungen
- Webhook-Events
- Call Summary Settings

// 4. BillingSettings.jsx
- Billing Rate Configuration
- Prepaid Settings
- Auto-Topup Rules
```

### Tag 4-5: Integration & Testing
**Priorität**: 🔴 KRITISCH

#### Tasks:
1. WebSocket-Integration für Echtzeit-Updates
2. Error Handling & Loading States
3. Permission Checks
4. Mobile Responsiveness
5. End-to-End Testing

## 🛠️ Technische Implementierung

### 1. Customer Detail View Struktur
```javascript
const CustomerDetailView = ({ customerId }) => {
    const { t } = useTranslation();
    const [customer, setCustomer] = useState(null);
    const [timeline, setTimeline] = useState([]);
    const [activeTab, setActiveTab] = useState('overview');
    
    return (
        <div className="customer-detail-container">
            <CustomerHeader customer={customer} />
            
            <Tabs value={activeTab} onChange={setActiveTab}>
                <Tab value="overview" label={t('overview')} />
                <Tab value="timeline" label={t('timeline')} />
                <Tab value="appointments" label={t('appointments')} />
                <Tab value="calls" label={t('calls')} />
                <Tab value="notes" label={t('notes')} />
                <Tab value="documents" label={t('documents')} />
            </Tabs>
            
            <TabPanel value={activeTab}>
                {activeTab === 'overview' && <CustomerOverview />}
                {activeTab === 'timeline' && <CustomerTimeline />}
                {/* ... weitere Tabs */}
            </TabPanel>
        </div>
    );
};
```

### 2. Appointment Modal mit Validierung
```javascript
const AppointmentModal = ({ onClose, onSave, customerId }) => {
    const [formData, setFormData] = useState({
        customerId: customerId || null,
        serviceId: null,
        staffId: null,
        branchId: null,
        startsAt: null,
        duration: 30,
        notes: ''
    });
    
    const checkAvailability = async () => {
        // Cal.com Verfügbarkeitsprüfung
        const available = await api.checkAvailability({
            staffId: formData.staffId,
            startsAt: formData.startsAt,
            duration: formData.duration
        });
        
        return available;
    };
    
    // ... Rest der Implementierung
};
```

### 3. Company Settings mit Live-Test
```javascript
const ApiKeySettings = () => {
    const [testing, setTesting] = useState(false);
    const [testResults, setTestResults] = useState({});
    
    const testRetellConnection = async () => {
        setTesting(true);
        try {
            const result = await api.testRetellApi();
            setTestResults({ retell: result });
        } catch (error) {
            setTestResults({ retell: { error: error.message } });
        }
        setTesting(false);
    };
    
    // ... Rest der Implementierung
};
```

## 🔧 MCP Server Nutzung

### Für Customer Timeline:
```bash
# DatabaseMCPServer für effiziente Queries
php artisan mcp:execute database "
SELECT * FROM (
    SELECT 'call' as type, created_at, from_number as detail FROM calls WHERE customer_id = ?
    UNION
    SELECT 'appointment' as type, created_at, service_id as detail FROM appointments WHERE customer_id = ?
    UNION
    SELECT 'note' as type, created_at, content as detail FROM customer_notes WHERE customer_id = ?
) timeline ORDER BY created_at DESC
"
```

### Für Appointment Availability:
```bash
# CalcomMCPServer für Verfügbarkeitsprüfung
php artisan mcp:execute calcom "check_availability" \
  --staff_id=123 \
  --date="2025-01-15" \
  --duration=30
```

## 📊 Erfolgs-Metriken

### Sofort messbar:
- Customer Detail View Load Time < 500ms
- Appointment Creation Success Rate > 95%
- API Key Test Success Rate = 100%

### Nach 1 Woche:
- Support-Tickets für Kundenverwaltung -50%
- Admin-Effizienz bei Kundenverwaltung +40%
- Fehlerrate bei Terminbuchungen -80%

## 🚀 Quick Wins

### Tag 1:
1. Customer Detail View Grundstruktur
2. Timeline API Integration
3. Basis-Navigation

### Tag 2:
1. Appointment Modal UI
2. Verfügbarkeitsprüfung
3. Customer Notes

### Tag 3:
1. Company Settings Tabs
2. API Key Management
3. Test-Funktionen

### Tag 4:
1. WebSocket Integration
2. Error Handling
3. Loading States

### Tag 5:
1. Testing & Bugfixing
2. Mobile Optimierung
3. Deployment

## ⚡ Sofort-Start Befehle

```bash
# 1. Neue Komponenten erstellen
mkdir -p resources/js/components/features/customers
mkdir -p resources/js/components/features/appointments
mkdir -p resources/js/components/features/settings

# 2. API Routes hinzufügen
php artisan make:controller Admin/Api/CustomerTimelineController
php artisan make:controller Admin/Api/AppointmentAvailabilityController
php artisan make:controller Admin/Api/CompanySettingsController

# 3. MCP Server für schnelle Implementierung nutzen
php artisan mcp:discover "create customer timeline api"
php artisan mcp:discover "implement appointment booking with cal.com"
```

## 🎯 Definition of Done

### Customer Detail View:
- [ ] Alle Kundendaten werden angezeigt
- [ ] Timeline zeigt alle Aktivitäten chronologisch
- [ ] Notizen können hinzugefügt/bearbeitet werden
- [ ] Portal-Zugang kann aktiviert/deaktiviert werden
- [ ] Mobile responsive
- [ ] Loading States implementiert
- [ ] Error Handling funktioniert

### Appointment Management:
- [ ] Termine können erstellt werden
- [ ] Verfügbarkeit wird live geprüft
- [ ] Termine können bearbeitet werden
- [ ] Kalenderansicht funktioniert
- [ ] Drag & Drop implementiert
- [ ] Notifications werden gesendet

### Company Settings:
- [ ] API Keys können verwaltet werden
- [ ] Test-Funktionen arbeiten
- [ ] Notifications konfigurierbar
- [ ] Billing Settings anpassbar
- [ ] Änderungen werden validiert
- [ ] Erfolgs-/Fehlermeldungen