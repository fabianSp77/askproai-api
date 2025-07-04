# System-Restrukturierungsplan für AskProAI

## 🎯 Ziel
Ein konsistentes, logisches System mit klarem Setup-Flow und zusammenhängenden Funktionen.

## 📊 Aktuelle Probleme

### Navigation (40+ Seiten, viele Duplikate)
- **System** erscheint 3x in verschiedenen Gruppen
- **Billing** einmal auf Deutsch, einmal auf Englisch
- 19 Seiten unter "Einstellungen"
- Viele experimentelle/Debug-Seiten noch aktiv

### Konfiguration
- Retell-Konfiguration über 5+ Seiten verstreut
- Cal.com Setup in mehreren Wizards
- Keine klare Reihenfolge der Einrichtung

## 🏗️ Neue Struktur

### 1. Vereinfachte Navigation
```
Dashboard
├── Übersicht (OperationalDashboard)

Täglicher Betrieb
├── Termine (Appointments)
├── Anrufe (Calls)
├── Kunden (Customers)

Verwaltung
├── Unternehmen (Companies)
├── Filialen (Branches)
├── Mitarbeiter (Staff)
├── Dienstleistungen (Services)

Integration & Setup
├── Integration Hub (NEU - Alles-in-einem)
├── Onboarding Wizard (NEU - Geführter Setup)
├── Webhook Monitor

Abrechnung
├── Abonnements (Subscriptions)
├── Rechnungen (Invoices)

System
├── Benutzer (Users)
├── Systemstatus (Health Monitor)
├── Einstellungen (Settings)
```

### 2. Integration Hub (Neue zentrale Seite)
Ersetzt alle verstreuten Konfigurationen:

```
Integration Hub
├── Cal.com Tab
│   ├── API-Schlüssel
│   ├── Team & Event Types
│   ├── Mitarbeiter-Zuordnung
│   └── Sync-Status
│
├── Retell.ai Tab
│   ├── API-Schlüssel
│   ├── Agent-Konfiguration
│   ├── Telefonnummern
│   └── Webhook-Status
│
└── Status-Übersicht
    ├── Verbindungsstatus
    ├── Letzte Synchronisation
    └── Fehlerlog
```

### 3. Onboarding Wizard (Geführter Setup)
Ein einziger Wizard für neue Unternehmen:

```
Schritt 1: Unternehmensdaten
├── Name, Adresse, Kontakt
└── Branche & Zeitzone

Schritt 2: Erste Filiale
├── Filialname & Adresse
└── Öffnungszeiten

Schritt 3: Integrationen
├── Cal.com API-Key
├── Retell.ai API-Key
└── Verbindung testen

Schritt 4: Telefon-Setup
├── Telefonnummer anlegen
├── Retell-Agent zuweisen
└── Test-Anruf

Schritt 5: Mitarbeiter & Services
├── Ersten Mitarbeiter anlegen
├── Dienstleistungen definieren
└── Cal.com Event Types verknüpfen
```

## 🗑️ Zu entfernende Seiten (30+)

### Retell Duplikate
- RetellDashboard.php
- RetellDashboardImproved.php
- RetellDashboardUltra.php
- RetellUltimateDashboard.php
→ Alles in **Integration Hub**

### System Monitoring Duplikate
- SystemCockpit.php
- SystemCockpitSimple.php
- UltimateSystemCockpitMinimal.php
- UltimateSystemCockpitOptimized.php
→ Nur **SystemHealthMonitor.php** behalten

### Setup Wizards
- CompanySetupWizard.php
- EventTypeSetupWizard.php
- OnboardingWizard.php (alt)
→ Neuer **UnifiedOnboardingWizard.php**

### Debug/Test Seiten
- TableDebug.php
- TestLivewirePage.php
- TestMinimalPage.php
- Alle .disabled und .backup Dateien

## 🔧 Implementierungsschritte

### Phase 1: Navigation aufräumen (Sofort)
1. AdminPanelProvider.php anpassen
2. Navigationgruppen konsolidieren
3. Sortierung logisch gestalten

### Phase 2: Integration Hub erstellen (Diese Woche)
1. Neue IntegrationHub.php Seite
2. Alle Konfigurationen migrieren
3. Alte Seiten deaktivieren

### Phase 3: Onboarding vereinfachen (Nächste Woche)
1. UnifiedOnboardingWizard.php erstellen
2. Abhängigkeiten prüfen
3. Validierung implementieren

## 📈 Erwartete Verbesserungen

1. **Klarheit**: Von 40+ auf ~15 Seiten
2. **Konsistenz**: Alle Integrationen an einem Ort
3. **Benutzerfreundlichkeit**: Geführter Setup-Prozess
4. **Wartbarkeit**: Keine Duplikate mehr
5. **Logik**: Klare Abhängigkeiten und Reihenfolge