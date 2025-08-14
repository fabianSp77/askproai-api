# DSGVO-Compliance (GDPR) - AskProAI System

## Übersicht

Dieses Dokument beschreibt die Datenschutz-Grundverordnung (DSGVO) Compliance-Maßnahmen des AskProAI-Systems zur Gewährleistung des Schutzes personenbezogener Daten.

## 1. Datenverarbeitung und Rechtsgrundlagen

### 1.1 Verarbeitete Personendaten
Das AskProAI-System verarbeitet folgende Kategorien personenbezogener Daten:

#### Kundendaten
- **Name und Kontaktdaten**: Name, E-Mail-Adresse, Telefonnummer
- **Termindaten**: Buchungszeitpunkte, Dienstleistungsart
- **Kommunikationsdaten**: Gesprächsaufzeichnungen (KI-Telefonate)

#### Technische Daten
- **Log-Daten**: IP-Adressen, Browser-Informationen, Zugriffszeitpunkte
- **Session-Daten**: Authentifizierungs-Tokens, Sitzungsidentifikatoren
- **Performance-Daten**: Systemmetriken, Fehlerprotokolle

### 1.2 Rechtsgrundlagen (Art. 6 DSGVO)
- **Art. 6 (1) b) DSGVO**: Vertragserfüllung (Terminbuchungen)
- **Art. 6 (1) f) DSGVO**: Berechtigte Interessen (Systemsicherheit, Betrieb)
- **Art. 6 (1) a) DSGVO**: Einwilligung (Marketing, erweiterte Services)

## 2. Datenschutz durch Technikgestaltung (Privacy by Design)

### 2.1 Minimierung der Datenverarbeitung
```php
// Beispiel: Nur notwendige Felder in der Kundentabelle
protected $fillable = [
    'name',           // Erforderlich für Terminbuchung
    'email',          // Erforderlich für Bestätigung
    'phone',          // Erforderlich für Rückfragen
    // 'address' - NICHT erforderlich, daher nicht gespeichert
];
```

### 2.2 Pseudonymisierung und Verschlüsselung
- **Datenbank-Verschlüsselung**: MySQL mit TLS-Verbindungen
- **Session-Verschlüsselung**: Laravel encrypted sessions
- **API-Token**: Hashed storage, keine Plaintext-Speicherung

```php
// Beispiel: Gehashte API-Keys
'api_key_hash' => Hash::make($api_key),
```

### 2.3 Zugriffskontrolle
- **Role-based Access Control**: Admin, User, Guest Rollen
- **Two-Factor Authentication**: Für Admin-Zugriffe
- **API Rate Limiting**: Schutz vor Missbrauch

## 3. Betroffenenrechte (Kapitel III DSGVO)

### 3.1 Auskunftsrecht (Art. 15 DSGVO)
Implementierte Funktion zur Datenauskunft:

```php
// Beispiel-Implementierung Datenauskunft
public function getPersonalData($customerId) {
    return [
        'personal_info' => Customer::find($customerId)->only(['name', 'email', 'phone']),
        'appointments' => Appointment::where('customer_id', $customerId)->get(),
        'calls' => Call::where('customer_id', $customerId)->get(['created_at', 'duration']),
        'processing_purposes' => 'Terminbuchung, Kundenkommunikation',
        'retention_period' => '3 Jahre nach letztem Termin'
    ];
}
```

### 3.2 Recht auf Berichtigung (Art. 16 DSGVO)
- **Online-Portal**: Kunden können Daten selbst korrigieren
- **API-Endpoint**: `/api/customers/{id}` für Datenaktualisierung
- **Validierung**: Automatische Prüfung der Datenintegrität

### 3.3 Recht auf Löschung (Art. 17 DSGVO)
```php
// Beispiel: Implementierung "Recht auf Vergessenwerden"
public function deletePersonalData($customerId) {
    DB::transaction(function() use ($customerId) {
        // Anonymisierung statt Löschung für rechtliche Dokumentation
        Customer::find($customerId)->update([
            'name' => 'Anonymisiert',
            'email' => 'deleted@example.com',
            'phone' => null,
            'deleted_at' => now()
        ]);
        
        // Gesprächsaufzeichnungen löschen
        Call::where('customer_id', $customerId)->delete();
    });
}
```

### 3.4 Recht auf Datenübertragbarkeit (Art. 20 DSGVO)
Export-Funktion für strukturierte Datenausgabe:

```php
// JSON/XML Export der Kundendaten
public function exportPersonalData($customerId) {
    $data = $this->getPersonalData($customerId);
    return response()->json($data, 200, [
        'Content-Disposition' => 'attachment; filename=personal_data.json'
    ]);
}
```

## 4. Technische und organisatorische Maßnahmen (Art. 32 DSGVO)

### 4.1 Technische Maßnahmen
- **Verschlüsselung**: TLS 1.3 für alle Verbindungen
- **Backup-Verschlüsselung**: Alle Backups sind verschlüsselt
- **Zugriffskontrolle**: Multi-Factor Authentication
- **Logging**: Audit-Trail aller Datenzugriffe

### 4.2 Organisatorische Maßnahmen
- **Mitarbeiterschulung**: Regelmäßige DSGVO-Trainings
- **Incident Response**: 72h Meldefrist bei Datenschutzverletzungen
- **Datenschutz-Folgenabschätzung**: Für neue Features
- **Auftragsverarbeitung**: Verträge mit externen Anbietern

## 5. Drittanbieter und Auftragsverarbeitung (Art. 28 DSGVO)

### 5.1 Cal.com Integration
- **Rechtsgrundlage**: Auftragsverarbeitervertrag
- **Datentransfer**: Nur terminrelevante Daten
- **Standort**: EU-Server (DSGVO-konform)

```env
# DSGVO-konforme Cal.com Konfiguration
CALCOM_BASE_URL=https://api.cal.com/v2
CALCOM_DATA_REGION=EU
```

### 5.2 RetellAI Integration
- **Rechtsgrundlage**: Auftragsverarbeitervertrag
- **Datenminimierung**: Nur notwendige Gesprächsdaten
- **Aufbewahrung**: Automatische Löschung nach 30 Tagen

### 5.3 Hosting und Infrastruktur
- **Serverstandort**: Deutschland/EU
- **Cloud-Anbieter**: DSGVO-zertifiziert
- **Datenresidenzen**: Keine Übertragung in Drittländer

## 6. Datenschutzverletzungen (Art. 33/34 DSGVO)

### 6.1 Incident Response Prozess
1. **Erkennung**: Automatische Monitoring-Systeme
2. **Bewertung**: Risiko für betroffene Personen
3. **Meldung**: Aufsichtsbehörde binnen 72h
4. **Benachrichtigung**: Betroffene bei hohem Risiko

### 6.2 Technische Präventionsmaßnahmen
```php
// Beispiel: Datenschutzverletzung-Detektion
class DataBreachDetection {
    public function detectUnauthorizedAccess($userId, $ipAddress) {
        if ($this->isAbnormalAccess($userId, $ipAddress)) {
            $this->triggerSecurityAlert();
            $this->logIncident($userId, $ipAddress);
        }
    }
}
```

## 7. Aufbewahrungsfristen und Löschkonzept

### 7.1 Automatische Löschung
```php
// Beispiel: Automatische Datenbereinigung
Schedule::command('personal-data:cleanup')
    ->daily()
    ->description('Löscht personenbezogene Daten nach Aufbewahrungsfristen');
```

### 7.2 Aufbewahrungsmatrix
| Datentyp | Aufbewahrungsdauer | Rechtsgrundlage |
|----------|-------------------|-----------------|
| Kundenstammdaten | 3 Jahre nach letztem Kontakt | Geschäftszweck |
| Gesprächsaufzeichnungen | 30 Tage | Qualitätssicherung |
| System-Logs | 90 Tage | IT-Sicherheit |
| Backup-Daten | 14 Tage | Betriebssicherheit |

## 8. Internationale Datenübertragung

### 8.1 Angemessenheitsbeschlüsse
- **EU/EWR**: Uneingeschränkter Transfer
- **Drittländer**: Nur mit angemessenen Garantien

### 8.2 Standardvertragsklauseln
Für notwendige Transfers außerhalb der EU werden EU-Standardvertragsklauseln verwendet.

## 9. Compliance-Monitoring

### 9.1 Automatisierte Prüfungen
```php
// Beispiel: DSGVO-Compliance Check
class GDPRComplianceChecker {
    public function runDailyCheck() {
        $this->checkDataRetentionCompliance();
        $this->validateConsentRecords();
        $this->auditDataProcessingActivities();
    }
}
```

### 9.2 Audit-Trail
Alle datenschutzrelevanten Aktionen werden protokolliert:
- Datenzugriffe
- Datenänderungen
- Löschvorgänge
- Einwilligungsstatus

## 10. Dokumentationspflichten (Art. 30 DSGVO)

### 10.1 Verzeichnis der Verarbeitungstätigkeiten
- **Zwecke der Verarbeitung**: Terminbuchung, Kundenkommunikation
- **Kategorien betroffener Personen**: Kunden, potenzielle Kunden
- **Übertragungen an Drittländer**: Keine
- **Löschfristen**: Siehe Aufbewahrungsmatrix

### 10.2 Regelmäßige Reviews
- **Quartalsweise**: Überprüfung der Verarbeitungszwecke
- **Jährlich**: Datenschutz-Folgenabschätzung
- **Bei Änderungen**: Sofortige Anpassung der Dokumentation

## Kontakt Datenschutzbeauftragter

Bei Fragen zum Datenschutz:
- **E-Mail**: datenschutz@askproai.de
- **Datenschutzbeauftragter**: [Name einsetzen]
- **Aufsichtsbehörde**: Zuständige Landesdatenschutzbehörde