# Admin-Panel Features - Vollständige Dokumentation

**Analysedatum**: 2025-10-04
**Analysierte Ressourcen**: 29 Filament Resources
**System**: API Gateway Admin Panel (Filament 3.x)

---

## 📊 Ressourcen-Übersicht

### Gruppierung nach Bereichen

#### 🏢 **System-Management** (5 Ressourcen)
- UserResource
- RoleResource
- PermissionResource
- SystemSettingsResource
- TenantResource

#### 📅 **Terminverwaltung** (2 Ressourcen)
- AppointmentResource (mit Kalender-Ansicht)
- CallLogResource

#### 👥 **Kundenverwaltung** (2 Ressourcen)
- CustomerResource
- CompanyResource

#### 📋 **Stammdaten** (4 Ressourcen)
- ServiceResource
- StaffResource
- BranchResource
- PhoneNumberResource

#### 🤖 **KI & Integration** (4 Ressourcen)
- RetellAgentResource
- IntegrationResource
- NotificationTemplateResource
- InvoiceResource

#### 💰 **Abrechnung & Finanzen** (4 Ressourcen)
- PricingPlanResource
- BalanceTopupResource
- CurrencyExchangeRateResource
- PlatformCostResource

---

## 🎯 Detaillierte Feature-Matrix

### 1. **UserResource** - Benutzerverwaltung
**Model**: User
**Navigation**: System → Benutzer
**Icon**: heroicon-o-users

#### Verfügbare Seiten
- ✅ List (index)
- ✅ Create
- ✅ View
- ✅ Edit

#### Form-Features (6 Tabs)
**Tab 1: 👤 Grunddaten**
- Name, E-Mail, Telefon
- Unternehmenszuordnung
- Geburtsdatum, Sprache, Zeitzone
- Profilbild (Avatar Upload)
- Adressdaten (Straße, PLZ, Stadt, Land)

**Tab 2: 🔐 Sicherheit**
- Passwort-Verwaltung
- Zwei-Faktor-Authentifizierung
- Passwort-Änderung erzwingen
- Fehlgeschlagene Login-Versuche
- API-Token mit Ablaufdatum
- Erlaubte IP-Adressen

**Tab 3: 🛡️ Berechtigungen**
- Mehrfach-Rollenzuweisung
- Direkte Berechtigungen (CheckboxList)
- Admin-Rechte
- Super-Admin (nur für Super-Admins sichtbar)
- API-Zugriff
- Zeitbeschränkte Zugriffsrechte

**Tab 4: ⚙️ Einstellungen**
- E-Mail/SMS/Push Benachrichtigungen
- Benachrichtigungstypen (Termine, Nachrichten, System, Marketing, Sicherheit)
- Theme (Hell/Dunkel/Auto)
- Datums-/Zeitformate
- Erster Wochentag
- Custom Preferences (KeyValue)

**Tab 5: 📊 Status**
- Aktiv/Inaktiv Toggle
- E-Mail Verifizierung
- Status (Aktiv, Inaktiv, Gesperrt, Ausstehend)
- Statusgrund
- Letzte Anmeldung
- Letzte Aktivität
- Login-Anzahl
- IP-Adresse & User Agent

#### Table-Columns
- Avatar (Circular Image)
- Name + E-Mail
- Unternehmen
- Rollen (Badges)
- Status (Badge mit Icon)
- Aktiv (Toggle)
- E-Mail verifiziert
- 2FA aktiviert
- Letzte Anmeldung
- Login-Anzahl
- Erstellt am

#### Filter
- Unternehmen (Searchable Select)
- Rolle (Multiple Select)
- Status (Select)
- Aktiv (Ternary)
- E-Mail verifiziert (Ternary)
- 2FA aktiviert (Ternary)
- Letzte Anmeldung (Select: Heute, Diese Woche, Diesen Monat, >1 Monat inaktiv, >3 Monate inaktiv)

#### Actions
- **Impersonate** - Als Benutzer anmelden (Admin only)
- **Reset Password** - Passwort zurücksetzen
- **Toggle Status** - Aktivieren/Deaktivieren
- View, Edit

#### Bulk-Actions
- Aktivieren
- Deaktivieren
- Rolle zuweisen
- Exportieren
- Löschen

#### Header-Actions
- **Import** - CSV-Import von Benutzern

#### Spezial-Features
- Scope-basierte Queries (Super-Admin sieht alle, andere nur eigenes Unternehmen)
- Filament-persisted Filters
- Real-time Polling (30s)

---

### 2. **RoleResource** - Rollen & Berechtigungen
**Model**: Role (Spatie Permission)
**Navigation**: System → Rollen & Rechte
**Icon**: heroicon-o-shield-check

#### Verfügbare Seiten
- ✅ List (index)
- ✅ Create
- ✅ View (mit Infolist)
- ✅ Edit

#### Form-Features (5 Tabs)
**Tab 1: 🎭 Grunddaten**
- Rollenname (eindeutig, lowercase, regex-validiert)
- Beschreibung
- Farbe (Primary, Secondary, Success, Warning, Danger, Info, Gray)
- Icon (Shield Variations, Briefcase, Cog, Eye, User Group, etc.)
- Priorität (1-999, niedrigere = höhere Priorität)
- Systemrolle (Toggle, disabled für Systemrollen)

**Tab 2: 🔐 Berechtigungen**
- CheckboxList mit gruppierten Berechtigungen
- Searchable
- Bulk-Togglable
- Vorkonfigurierte Sets:
  - 🔍 Nur Lesen
  - 📝 Content Manager
  - ⚙️ System Admin
  - 🗑️ Alle entfernen

**Tab 3: 👥 Benutzer**
- Zugewiesene Benutzer (Liste mit Avatar)
- Benutzer hinzufügen (Select)
- Statistiken:
  - Gesamtbenutzer
  - Aktive Benutzer
  - Zuletzt zugewiesen

**Tab 4: ⚙️ Erweitert**
- Metadaten (KeyValue)
- System-Informationen:
  - Rollen-ID
  - Guard
  - Erstellt am
  - Aktualisiert am

#### Table-Columns
- ID
- Rollenname (Badge mit Icon und Farbe)
- Beschreibung (mit Tooltip)
- Benutzer-Anzahl (Badge mit Icon)
- Berechtigungen-Anzahl (Badge mit Farbcodierung)
- System (Icon: Lock-Closed/Open)
- Priorität (Badge)
- Erstellt/Aktualisiert

#### Filter
- Typ (Systemrollen, Benutzerdefiniert)
- Mit Benutzern (Toggle)
- Ohne Benutzer (Toggle)
- Prioritätsbereich (Hoch 1-10, Mittel 11-50, Niedrig 51-100)

#### Actions
- View (mit IconButton)
- Edit (nur für nicht-System oder Super-Admin)
- **Duplicate** - Rolle duplizieren (mit Bestätigung)
- Delete (nur wenn can_delete = true)

#### Bulk-Actions
- **Berechtigungen zuweisen** - Bulk Permission Assignment
- Delete (nur Super-Admin)

#### Spezial-Features
- Reorderable (nach Priorität)
- Infolist mit Split-Layout
- Gruppierte Berechtigungsanzeige
- Rollenduplikation mit Permission-Sync
- Schutz von Systemrollen

---

### 3. **SystemSettingsResource** - Systemeinstellungen
**Model**: SystemSetting
**Navigation**: System → Systemeinstellungen
**Icon**: heroicon-o-cog-6-tooth

#### Verfügbare Seiten
- ✅ List (index)
- ✅ Create
- ✅ Edit

#### Form-Features (6 Tabs)
**Tab 1: ⚙️ Grundeinstellungen**
- Schlüssel (Eindeutig, lowercase_underscore, disabled nach Erstellung)
- Bezeichnung
- Kategorie (Kern-System, Funktionen, Integrationen, Experimentell)
- Gruppe (12 Gruppen: Allgemein, E-Mail, Sicherheit, Integrationen, Performance, etc.)
- Datentyp (18 Typen: String, Integer, Float, Boolean, JSON, Array, Textarea, Select, MultiSelect, Color, Date, Time, DateTime, Email, URL, Password, File, Encrypted)
- Beschreibung
- Priorität (0-4: Niedrig, Normal, Wichtig, Hoch, Kritisch)
- Tags

**Tab 2: 💾 Wert & Validierung**
- Dynamisches Wert-Feld (abhängig vom Typ)
- Options (für Select/MultiSelect)
- Standardwert
- Min/Max Werte
- Validierungsregeln (KeyValue)
- Custom Validierungsfehlermeldung
- Erlaubte Werte
- Pflichtfeld (Toggle)

**Tab 3: 🔒 Sicherheit & Zugriff**
- Öffentlich zugänglich
- Verschlüsselte Speicherung
- Sensible Daten (maskiert in Logs)
- Neustart erforderlich
- Erforderliche Berechtigung
- Umgebungen (Local, Development, Staging, Production)
- Erlaubte IPs
- Sicherheitshinweise
- Audit & Tracking:
  - Erstellt von/am
  - Aktualisiert von/am
  - Änderungsanzahl
  - Zuletzt abgerufen
  - Änderungsprotokoll

**Tab 4: ⚡ Performance & Cache**
- Cache TTL (0-86400 Sekunden)
- Cache-Treiber (File, Database, Redis, Memcached, Array)
- Cache aktiviert
- Eager Loading
- Cache-Status (Live-Anzeige)
- Cache leeren (Action)

**Tab 5: 🔗 Abhängigkeiten**
- Abhängig von (Multi-Select)
- Beeinflusst (Multi-Select)
- Auswirkungsbeschreibung
- Bedingungen (KeyValue)

**Tab 6: 🚀 System-Aktionen**
- Cache leeren (mit Bestätigung)
- Optimieren (Config/Route/View Cache)
- Wartungsmodus EIN/AUS
- Backup erstellen
- Migrationen ausführen
- Einstellungen exportieren
- Standard-Einstellungen erstellen

#### Table-Columns
- Kategorie (Badge mit Farbcodierung)
- Gruppe (Badge)
- Schlüssel (mit Description Tooltip)
- Bezeichnung + Description
- Priorität (Badge mit Farbe)
- Typ (Badge mit Farbcodierung)
- Wert (maskiert wenn sensibel, formatiert nach Typ)
- Öffentlich (Globe/Lock Icon)
- Verschlüsselt (Lock Icon)
- Neustart (Arrow-Path Icon)
- Cache (Badge: Zeit oder "Aus")
- Tags (Badge Separator)
- Aktualisiert

#### Filter
- Kategorie (4 Optionen)
- Gruppe (12 Optionen, Multiple)
- Typ (Multiple)
- Priorität (5 Optionen)
- Öffentliche (Toggle)
- Verschlüsselte (Toggle)
- Sensible (Toggle)
- Neustart erforderlich (Toggle)

#### Actions
- Edit
- **Test** - Einstellung testen
- **Duplicate** - Mit neuem Schlüssel
- Delete (außer geschützte Keys)

#### Bulk-Actions
- **Export** - JSON-Export
- **Cache leeren** - Für ausgewählte
- Delete (Super-Admin only)

#### Spezial-Features
- Grouping (Kategorie, Gruppe, Typ, Priorität)
- createDefaults() Methode
- Global Searchable (Key, Label, Description, Group)
- Custom Empty State mit "Standards erstellen" Action
- Real-time Cache-Status
- 18 verschiedene Datentypen mit eigenen Inputs
- Artisan-Command Integration

---

### 4. **TenantResource** - Mandantenverwaltung
**Model**: Tenant
**Navigation**: System → Mandanten
**Icon**: heroicon-o-building-office-2

#### Verfügbare Seiten
- ✅ List (index)
- ✅ Create
- ✅ View (mit Infolist)
- ✅ Edit

#### Form-Features (6 Tabs)
**Tab 1: 🏢 Grunddaten**
- Name + Slug (auto-generiert)
- Domain
- Typ (Hauptmandant, Standard, Testversion, Demo, Partner, Reseller)
- Beschreibung
- Aktiv/Verifiziert (Toggles)
- Kontaktdaten (Name, E-Mail, Telefon, Zeitzone)

**Tab 2: 💰 Abrechnung**
- Guthaben (Cent → EUR Display)
- Kreditlimit
- Abrechnungszyklus (Monatlich, Quartalsweise, Jährlich, Prepaid, Postpaid)
- Abrechnungsbeginn/Nächste Abrechnung
- Automatische Aufladung (Toggle + Betrag)
- Preismodell:
  - Tarif (Starter €49, Professional €149, Business €399, Enterprise, Individuell)
  - Monatliche Grundgebühr
  - Minutenpreis
  - Rabatt (%)

**Tab 3: 🔗 Integrationen**
- API-Schlüssel + Secret (mit Regenerieren-Action)
- Erlaubte IPs (TagsInput)
- Webhook URL + Events
- Cal.com Integration:
  - Team Slug
  - API Key
  - Aktiviert (Toggle)
  - Standard-Termindauer
- Service-Integrationen (KeyValue)

**Tab 4: 🚦 Limits & Quota**
- Nutzungslimits:
  - Max. Benutzer
  - Max. KI-Agenten
  - Max. Telefonnummern
  - Max. gleichzeitige Anrufe
  - Monatliche Minuten
  - Speicherplatz (GB)
  - Unbegrenzte Toggles
- Aktuelle Nutzung (Live-Anzeige):
  - Benutzer/Agenten/Telefonnummern
  - Minuten diesen Monat
  - Speicher verwendet
  - Quota-Warnung

**Tab 5: ⚙️ Einstellungen**
- Feature-Flags (12 Features):
  - KI-Agenten, Anrufaufzeichnung, Transkription, Sentiment-Analyse
  - Erweiterte Weiterleitung, Eigenes Branding, API-Zugang
  - Webhook-Support, White Label, Prioritäts-Support
  - SLA-Garantie, Datenexport
- Custom Einstellungen (KeyValue)
- Metadaten (KeyValue)
- Compliance:
  - Datenspeicherort (EU, DE, CH, US)
  - DSGVO-konform (Toggle)
  - AVV unterschrieben am
  - Datenaufbewahrung (Tage)

**Tab 6: 📊 Statistik**
- Gesamtanrufe, Minuten, Kosten
- Ø Anrufdauer
- Letzte Aktivität
- Erstellt am
- Benutzerwachstum (%)
- Gesundheitswert (0-100)
- Monatliche Übersicht (Anrufe, Umsatz, Kosten, Marge)

#### Table-Columns
- ID
- Mandantenname (Icon + Slug-Description)
- Typ (Badge mit Icon)
- Status (Badge: Aktiv, Niedrig, Überzogen, Inaktiv)
- Guthaben (EUR mit Farbcodierung)
- Aktiv (ToggleColumn)
- Verifiziert (Shield Icon)
- Benutzer-Anzahl (Badge mit Icon)
- Tarif (Badge)
- Cal.com Team
- Letzte Aktivität
- Erstellt

#### Filter
- Typ (Multiple)
- Tarif (Select)
- Nur aktive (Toggle, default true)
- Nur verifizierte (Toggle)
- Niedriges Guthaben (Toggle, <10 EUR)
- Negativer Saldo (Toggle)
- Mit Cal.com (Toggle)

#### Actions
- View, Edit
- **Impersonate** - Als Mandant anmelden (Super-Admin only, mit Session)
- **Add Balance** - Guthaben aufladen (Form mit Betrag)
- Delete (außer Hauptmandanten)

#### Bulk-Actions
- Aktivieren/Deaktivieren
- Verifizieren
- **Export** - CSV-Export
- Delete (Super-Admin only)

#### Spezial-Features
- Infolist mit Split-Layout
- API & Integration Section
- Limits & Nutzung Grid
- Real-time Polling (30s)
- Custom Health Score Berechnung
- Monatliche Statistiken
- Session-basierte Impersonation

---

### 5. **AppointmentResource** - Terminverwaltung
**Model**: Appointment
**Navigation**: CRM → Termine
**Icon**: heroicon-o-calendar-days
**Badge**: Anzahl Termine mit starts_at (cached)

#### Verfügbare Seiten
- ✅ List (index)
- ✅ Calendar (Kalenderansicht!)
- ✅ Create
- ✅ View (mit Infolist)
- ✅ Edit

#### Widgets
- AppointmentStats
- UpcomingAppointments
- AppointmentCalendar

#### Form-Features (2 Sections)
**Section 1: Termindetails**
- Kunde (Select mit Create-Option + Auto-Fill Branch)
- Dienstleistung (Select mit Auto-Duration + Price)
- Beginn/Ende (DateTimePicker mit Auto-Calc, 15-Min-Steps)
- Mitarbeiter + Filiale (Required Selects)
- Status (6 Status: Ausstehend, Bestätigt, In Bearbeitung, Abgeschlossen, Storniert, Nicht erschienen)
- Notizen (RichEditor)

**Section 2: Zusätzliche Informationen**
- Buchungsquelle (Telefon, Online, Walk-In, App, KI-Assistent)
- Preis
- Buchungstyp (Einzeltermin, Serie, Gruppe, Paket)
- Erinnerung/Bestätigung senden (Toggles)
- Paket-Felder (bei booking_type=package):
  - Sitzungen Gesamt/Verbraucht
  - Ablaufdatum

#### Table-Columns
- Zeit (H:i + Wochentag + diffForHumans)
- Kunde (mit Phone-Description + Link zu CustomerResource)
- Service (Badge)
- Mitarbeiter
- Filiale (Badge)
- Status (Badge mit Icon + Farbe)
- Dauer (Berechnet, Badge)
- Preis (EUR, aligned End)
- Erinnerung (Bell Icon)
- Quelle (Badge mit Emoji)
- Erstellt

#### Filter
- Zeitraum (Ternary: Alle, Heute, Diese Woche)
- Status (Multiple)
- Mitarbeiter (Searchable Select)
- Service (Searchable Select)
- Filiale (Searchable Select)
- Bevorstehend (Filter, default active)
- Vergangen (Filter)

#### Actions (ActionGroup)
- **Confirm** - Status → confirmed (nur bei pending)
- **Complete** - Status → completed (nur bei confirmed/in_progress)
- **Cancel** - Status → cancelled (mit Bestätigung)
- **Reschedule** - Neuer Starttermin (mit Dauer-Berechnung)
- **Send Reminder** - Erinnerung senden
- View, Edit

#### Bulk-Actions
- Bulk Confirm
- Bulk Cancel
- Delete

#### Infolist (7 Sections)
1. **Terminübersicht** (ID, Status, Buchungsart, Beginn/Ende)
2. **Teilnehmer** (Kunde, Mitarbeiter, Service, Filiale, Unternehmen - mit Links)
3. **Service & Preise** (Dauer, Preis, Anfahrtszeit, Notizen)
4. **Cal.com Integration** (Booking ID, Event Type ID, Quelle)
5. **Serie & Pakete** (Serie-ID, Gruppen-ID, Paket-Sitzungen, Wiederholungsregel)
6. **Erinnerungen & System** (24h-Erinnerung, Erstellt, Externe ID, Metadaten)

#### Spezial-Features
- **Calendar Page** - Dedizierte Kalenderansicht
- Smart Auto-Calculations (Dauer, Ende-Zeit, Preis)
- Customer Auto-Fill (preferred_branch)
- Real-time Polling (60s)
- Defer Loading
- Global Search (customer.name, service.name, staff.name, notes)
- Eager Loading (customer, service, staff, branch, company)

---

### 6. **ServiceResource** - Dienstleistungsverwaltung
**Model**: Service
**Navigation**: Stammdaten → Dienstleistungen
**Icon**: heroicon-o-briefcase

#### Verfügbare Seiten
- ✅ List (index)
- ✅ Edit
- ✅ View (mit umfangreichem Infolist)
- ❌ Create (deaktiviert - nur via Cal.com!)

#### Relation-Managers
- AppointmentsRelationManager
- StaffRelationManager

#### Form-Features (6 Sections)
**Section 1: Service-Informationen**
- Unternehmen + Filiale (reactive)
- Name (von Cal.com, disabled wenn synced)
- Display Name (optional override)
- Kategorie (Consultation, Treatment, Diagnostic, Therapy, Training, Other)
- Beschreibung

**Section 2: Service-Einstellungen**
- Dauer (Minuten)
- Pufferzeit (Minuten)
- Preis (EUR)
- Max. Buchungen pro Tag
- Aktiv (Toggle)
- Online-Buchung (Toggle)

**Section 3: Komposite Dienstleistung** (MEGA-FEATURE!)
- Komposite Toggle
- **Template Selector** (5 vorgefertigte Templates):
  - 🎨 Friseur Premium (2h 40min mit Pausen)
  - ✂️ Friseur Express (90min ohne Pausen)
  - 💆 Spa Wellness (3h mit Pausen)
  - ⚕️ Medizinische Behandlung (2h mit Nachsorge)
  - 💅 Beauty Komplett (4h mit mehreren Pausen)
- **Segments Repeater** (2-10 Segmente):
  - Segment-Schlüssel (A-J, auto-generiert)
  - Name (eindeutig, required)
  - Dauer (5-480 Min)
  - Lücke danach (0-240 Min)
- Pause Bookable Policy (free, blocked, flexible)
- **Total Duration Info** (Live-Berechnung mit Warnungen)

**Section 4: Mitarbeiterzuweisung** (Repeater)
- Mitarbeiter (Select mit aktiven Staff)
- Primary Staff (Toggle)
- Kann buchen (Toggle)
- Allowed Segments (nur bei Komposite)
- Skill Level (Junior, Regular, Senior, Expert)
- Weight (0-9.99)
- Custom Duration/Price/Commission
- Spezialisierungsnotizen

**Section 5: Cal.com Integration**
- Event Type ID (disabled)
- Sync-Status (Live-Anzeige)

**Section 6: Zuweisungsinformationen**
- Zuweisungsstatus
- Konfidenz (%)
- Zuweisungsnotizen
- Zugewiesen am/von

**Section 7: Richtlinien** (Policy System!)
- Stornierungsrichtlinie
- Umbuchungsrichtlinie
- Wiederholungsrichtlinie
- Jeweils mit:
  - Überschreiben Toggle
  - KeyValue Config
  - Vererbungsanzeige (von Filiale/Unternehmen)
  - Hierarchie-Info

#### Table-Columns
- Unternehmen (mit Assignment-Method Description)
- Dienstleistung (Display Name + Cal.com Name Tooltip)
- Konfidenz (Badge mit Farbe)
- Sync-Status (Badge mit Icon)
- Letzte Sync (mit Error-Description)
- Dauer (mit Komposite-Info: "160 min (📋 140+20)")
- Komposit (Icon)
- Preis (EUR)
- Aktiv (Icon)
- Online (Icon)
- Anstehende Termine (Badge)
- Gesamt Termine (Badge)

#### Filter
- **Advanced Search** (Intelligente Fuzzy-Suche):
  - Text (Name, Description, Category, Company/Branch)
  - Soundex-Matching
  - Preis-Suche (±10 EUR Toleranz)
  - Dauer-Suche (±10 Min Toleranz)
- Unternehmen (Searchable)
- Sync-Status (synced/not_synced)
- Aktiv (Ternary)
- Online-Buchung (Ternary)
- Kategorie (Select)
- Zuweisungsmethode (Manual, Auto, Suggested, Import)
- Konfidenzniveau (Hoch ≥80%, Mittel 60-79%, Niedrig <60%)

#### Actions
- View, Edit
- **Sync** - Mit Cal.com synchronisieren (mit Auto-Retry 3x!)
- **Unsync** - Sync aufheben
- **Assign Company** - Manuell zuweisen (mit Suggestion-Hint)
- **Auto Assign** - Automatische Zuweisung (nur wenn Konfidenz ≥70%)

#### Bulk-Actions
- **Bulk Sync** - Mit Auto-Retry für jeden Service
- Aktivieren/Deaktivieren
- **Bulk Edit** (Mega-Feature!):
  - Kategorie aktualisieren
  - Preis (absolut oder prozentual!)
  - Dauer
  - Pufferzeit
  - Online-Buchung
  - Max. Buchungen pro Tag
  - Pause Bookable Policy
  - Notizen
- **Bulk Auto Assign** - Automatische Zuweisung
- **Bulk Assign to Company** - Zu Unternehmen zuweisen
- Delete

#### Header-Actions
- **Create in Cal.com** - Link zu Cal.com (new tab)
- **Import from Cal.com** - Manuelle Import-Queue
- **Sync All for Company** - Alle Services eines Unternehmens synchen

#### Infolist (8 Sections)
1. **Serviceübersicht** (Name, Kategorie, Status, Beschreibung)
2. **Unternehmenszuweisung** (Company, Branch, Assignment-Method, Konfidenz, Datum, Notizen, Assigned By)
3. **Preis & Zeiteinstellungen** (Preis, Dauer, Puffer, Max Buchungen, Stundensatz, Gesamtzeit)
4. **Cal.com Integration** (Event Type ID, Sync-Status, Last Sync, Error, External ID)
5. **Terminstatistiken** (Gesamt, Anstehend, Abgeschlossen, Storniert, Umsatz, Ø pro Monat, Beliebte Zeiten)
6. **System-Informationen** (ID, Erstellt, Aktualisiert, Metadaten)

#### Spezial-Features
- **canCreate() = false** - Nur via Cal.com!
- ServiceMatcher Integration (Auto-Assignment mit Konfidenz)
- Policy Configuration System (Hierarchische Vererbung)
- Composite Services (Segmente mit Lücken)
- Template System (5 vorgefertigte Service-Typen)
- Intelligente Suche mit Fuzzy-Matching
- Sync mit Auto-Retry (bis 3x mit Exponential Backoff)
- Bulk Operations mit Percentage-Pricing
- Grouping (Company, Category)
- Real-time Polling (60s)

---

### 7. **StaffResource** - Personalverwaltung
**Model**: Staff
**Navigation**: Stammdaten → Personal
**Icon**: heroicon-o-user-group

#### Verfügbare Seiten
- ✅ List (index)
- ✅ Create
- ✅ View
- ✅ Edit

#### Relation-Managers
- AppointmentsRelationManager
- WorkingHoursRelationManager

#### Form-Features (4 Tabs)
**Tab 1: Person**
- Name, Stamm-Filiale (Required)
- E-Mail, Telefon
- Erfahrungslevel (1-5: Anfänger → Expert)
- Aktiv/Verfügbar/Buchbar (3 Toggles)
- Unternehmen

**Tab 2: Qualifikationen**
- Mobilitätsradius (km)
- Durchschnittsbewertung (disabled, auto)
- Fähigkeiten (Textarea, kommagetrennt)
- Sprachen (Textarea mit Kenntnisstand)
- Spezialisierungen
- Zertifikate

**Tab 3: Arbeitszeit**
- Arbeitszeiten (Textarea, pro Wochentag)
- Notizen (Verfügbarkeit, Präferenzen)
- Home Branch ID
- Cal.com Benutzername

**Tab 4: Integration** (Admin only)
- Cal.com User ID
- Cal.com Kalender Link
- Externe Kalender ID
- Kalender Anbieter (Google, Outlook, Cal.com, Other)

#### Table-Columns
- Mitarbeiter (Name + Experience-Icon + Branch + Level)
- Kontakt (E-Mail • Telefon)
- Level (Badge 🌱🌿🌳🏆👑)
- Verfügbarkeit (Badge: 🟢Verfügbar, 🟡Eingeschränkt, 🔴Nicht verfügbar)
- Fähigkeiten (Preview, Badge, Tooltip)
- Performance (⭐ Rating • 📅 Termine-Anzahl, Badge)
- Mobilität (🚗 Radius oder 🏢 Nur Filiale)
- Kalender-Integration (Icon mit Tooltip)
- Aktualisiert

#### Filter
- Filiale (Searchable)
- Erfahrungslevel (1-5)
- Aktuell verfügbar (default active!)
- Mobile Mitarbeiter (Radius > 0)
- Top Bewertung (≥4.0)
- Mit Kalender-Integration
- Zertifiziert
- Unternehmen (Searchable)

#### Actions (ActionGroup)
- **Schedule Appointment** - Termin planen (Link zu Create mit pre-filled staff_id)
- **Update Skills** - Qualifikationen bearbeiten (Form)
- **Update Schedule** - Arbeitszeiten/Buchbarkeit (Form)
- **Toggle Availability** - Verfügbarkeit (3 Toggles + Notiz)
- **Transfer Branch** - Filiale wechseln (mit Grund, Bestätigung)
- View, Edit

#### Bulk-Actions
- **Bulk Availability Update** - Verfügbarkeit für mehrere (mit Grund)
- **Bulk Branch Transfer** - Massen-Versetzung
- **Bulk Experience Update** - Level anpassen
- Export
- Delete

#### Spezial-Features
- Eager Loading (company, branch, appointments count)
- Experience-Level Icons (Academic-Cap → Trophy)
- Performance-Badges mit Farb-Codierung
- Working Hours Relation Manager
- Global Search (name, email, phone, skills, specializations)
- Real-time Polling (60s)
- Defer Loading
- Admin-only Integration Tab

---

### 8. **CompanyResource** - Unternehmensverwaltung
**Model**: Company
**Navigation**: CRM → Unternehmen

#### Verfügbare Features
- List, Create, View, Edit
- Relation-Managers (Branches, Services, Staff, Customers)
- Kontaktdaten
- Adressinformationen
- Cal.com Team Konfiguration
- Logo Upload
- Aktiv-Status

---

### 9. **BranchResource** - Filialen
**Model**: Branch
**Navigation**: Stammdaten → Filialen

#### Verfügbare Features
- List, Create, View, Edit
- Unternehmenszuordnung
- Adresse mit Geo-Koordinaten
- Öffnungszeiten
- Kontaktinformationen
- Relation-Managers (Services, Staff, Appointments)

---

### 10. **RetellAgentResource** - KI-Agenten
**Model**: RetellAgent
**Navigation**: KI & Integration

#### Verfügbare Features
- List, Create, View, Edit
- Retell.AI Integration
- Agent-Konfiguration (Voice, Language, Prompt)
- Telefonnummern-Zuweisung
- Call Logging
- Performance-Metriken

---

## 🎨 Spezial-Features Highlights

### 🗓️ **Kalender-Funktionen**
- **AppointmentResource**: Dedizierte Calendar-Page
- CalendarWidget Integration
- Drag & Drop Termine (wahrscheinlich)
- Termin-Serien & Pakete

### 🔄 **Cal.com Integration**
- **ServiceResource**: Vollständige Sync mit Auto-Retry
- Event Type Import
- Booking ID Tracking
- Webhook Support (in TenantResource)

### 🎯 **Composite Services**
- **Segment-System** mit Lücken (A-J Keys)
- Template-basierte Erstellung (5 Templates)
- Live-Berechnung (Aktiv + Lücken = Total)
- Pause Bookable Policy
- Staff-Segment-Zuweisung

### 🛡️ **Policy System**
- **Hierarchische Vererbung** (Company → Branch → Service)
- 3 Policy-Typen (Cancellation, Reschedule, Recurring)
- Override-Mechanismus
- KeyValue-Konfiguration
- Visual Inheritance Display

### 🔐 **Permission System**
- **Spatie Permission** Integration
- Gruppierte Berechtigungen (Checkbox-Listen)
- Vorkonfigurierte Sets (Read-Only, Content Manager, System Admin)
- Role Priority System
- System-Rollen-Schutz

### 📊 **Advanced Features**
- **Smart Search** (Fuzzy-Matching, Soundex, Price/Duration Tolerance)
- **Auto-Assignment** (ServiceMatcher mit Confidence-Scoring)
- **Bulk Operations** (mit Percentage-Pricing, Status-Updates)
- **Real-time Features** (Polling, Badge-Caching, Live-Calculations)
- **Infolists** (7-8 Sections pro Ressource mit Split-Layouts)
- **Global Search** (auf definierten Attributen)

### 🎛️ **System Administration**
- **SystemSettings**: 18 Datentypen, 12 Gruppen, 4 Kategorien
- Artisan-Command Integration
- Maintenance Mode Control
- Cache Management
- Migration Runner
- Export/Import Functions

### 👥 **Multi-Tenancy**
- **Tenant Resource** mit Features, Limits, Billing
- Impersonation (Session-basiert)
- Quota-Tracking
- Health Scoring
- Monthly Statistics

---

## 📋 Feature-Matrix Zusammenfassung

| Feature | UserResource | RoleResource | SystemSettings | TenantResource | AppointmentResource | ServiceResource | StaffResource |
|---------|:------------:|:------------:|:--------------:|:--------------:|:-------------------:|:---------------:|:-------------:|
| **List** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Create** | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ |
| **View** | ✅ | ✅ | ❌ | ✅ | ✅ | ✅ | ✅ |
| **Edit** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Calendar** | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ |
| **Infolist** | ❌ | ✅ | ❌ | ✅ | ✅ | ✅ | ❌ |
| **Export** | ✅ | ❌ | ✅ | ✅ | ❌ | ✅ | ✅ |
| **Import** | ✅ | ❌ | ✅ | ❌ | ❌ | ✅ | ❌ |
| **Bulk Edit** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Widgets** | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ |
| **Relation Managers** | ❌ | ❌ | ❌ | ❌ | ✅ | ✅ | ✅ |
| **Global Search** | ❌ | ✅ | ✅ | ❌ | ✅ | ❌ | ✅ |
| **Polling** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Filters** | ✅✅✅ | ✅✅ | ✅✅✅✅ | ✅✅✅ | ✅✅✅ | ✅✅✅✅✅ | ✅✅✅✅ |
| **Actions** | ✅✅✅ | ✅✅ | ✅✅✅ | ✅✅✅ | ✅✅✅✅✅ | ✅✅✅✅ | ✅✅✅✅✅ |
| **Special Features** | Impersonation, 2FA, API Tokens | Permission Sets, Reorderable | 18 Types, Artisan Integration | Impersonation, Billing, Limits | Calendar, Packages, Series | Composite, Policies, Auto-Assign | Skills, Availability, Mobility |

---

## 🏆 Top-Feature-Highlights

### 1. **Composite Services** (ServiceResource)
- Segmente (A-J) mit individuellen Dauern und Lücken
- 5 vorgefertigte Templates (Friseur, Spa, Medizin, Beauty)
- Live-Berechnung mit Warnungen
- Staff-Segment-Zuweisung
- Pause Bookable Policy

### 2. **Policy System** (ServiceResource)
- Hierarchische Vererbung (Company → Branch → Service)
- 3 Policy-Typen mit KeyValue-Configs
- Visual Inheritance Display
- Override-Mechanismus

### 3. **Kalender-Integration** (AppointmentResource)
- Dedizierte Calendar-Page
- Termin-Serien & Pakete
- Smart Auto-Calculations
- Multiple Widgets

### 4. **System Settings** (SystemSettingsResource)
- 18 Datentypen
- 12 Gruppen, 4 Kategorien, 5 Prioritäten
- Cache-Management
- Artisan-Integration
- Dependency-Tracking

### 5. **Auto-Assignment** (ServiceResource)
- ServiceMatcher mit Confidence-Scoring
- Intelligente Suggestions
- Bulk Auto-Assign
- Company-Matching

### 6. **Smart Search** (ServiceResource)
- Fuzzy-Matching
- Soundex
- Price/Duration-Toleranz
- Multi-Field-Search

### 7. **Multi-Tenancy** (TenantResource)
- Feature-Flags (12 Features)
- Quota-Tracking
- Health-Scoring
- Impersonation

### 8. **Bulk Operations**
- Percentage-Pricing (ServiceResource)
- Status-Updates
- Branch-Transfers (StaffResource)
- Permission-Assignment (RoleResource)

---

## 📝 Zusammenfassung

**Gesamte analysierte Ressourcen**: 29
**Vollständig dokumentiert**: 8 Haupt-Ressourcen
**Gesamt Form-Tabs**: ~40
**Gesamt Filter**: ~80
**Gesamt Actions**: ~60
**Gesamt Bulk-Actions**: ~35
**Spezial-Features**: 15+
**Relation-Managers**: 10+
**Widgets**: 5+

Das Admin-Panel ist ein **hochmodernes, feature-reiches** Filament 3.x System mit:
- Umfassender Termin- und Kalenderverwaltung
- KI-Integration (Retell.AI)
- Cal.com-Synchronisation
- Multi-Tenancy Support
- Komplexem Permission-System
- Advanced Bulk-Operations
- Policy-Management
- Composite Services

**Code-Qualität**: Professionell, gut strukturiert, umfangreiche Validierung
**UX**: Exzellent mit Icons, Badges, Farb-Codierung, Live-Updates
**Performance**: Optimiert mit Eager Loading, Caching, Polling, Defer Loading
