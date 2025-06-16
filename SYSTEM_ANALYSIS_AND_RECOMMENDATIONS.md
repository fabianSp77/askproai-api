# System-Analyse und Empfehlungen

## Aktuelle Menüstruktur

### 1. **System Control** 🛡️
- System Status
- System Cockpit
- Validation Dashboard (?)

### 2. **Buchungen** 📅
- Termine (Appointments)
- Bookings (Duplikat?)

### 3. **Event Management** ✨
- Event Types (CalcomEventTypeResource)
- Event Analytics Dashboard
- Staff Event Assignment
- Event Type Import Wizard

### 4. **Stammdaten**
- Unternehmen (Companies)
- Filialen (Branches)
- Mitarbeiter (Staff)
- Kunden (Customers)
- Leistungen (Services)
- Master Services (Duplikat?)

### 5. **Kommunikation** 💬
- Anrufe (Calls)

### 6. **Integrationen** 🔗
- Integrations

### 7. **Verwaltung** ⚙️
- Users
- Working Hours
- Phone Numbers
- Tenants

## Probleme und Inkonsistenzen

### 1. **Duplikate**
- AppointmentResource UND BookingResource (beide für Termine?)
- ServiceResource UND MasterServiceResource
- WorkingHourResource UND WorkingHoursResource
- UnifiedEventTypeResource (ungenutzt?)
- DummyCompanyResource (Test-Resource?)

### 2. **Sprach-Mix**
- Teilweise Deutsch (Termine, Anrufe, Filialen)
- Teilweise Englisch (Event Types, Staff Event Assignment)
- Inkonsistente Benennung

### 3. **Unklare Zuordnungen**
- ValidationDashboardResource - was macht das?
- PhoneNumberResource - eigene Resource nötig?
- TenantResource - für Multi-Tenancy?

## Empfohlene Struktur

### 1. **Dashboard** 🏠
- Übersicht (Dashboard mit KPIs)
- System Status (nur für Admins)

### 2. **Terminverwaltung** 📅
- Termine (Appointments - EINE Resource)
- Verfügbarkeiten (Working Hours)
- Event-Typen (Services/Event Types kombiniert)

### 3. **Kontakte** 👥
- Kunden (Customers)
- Mitarbeiter (Staff)
- Unternehmen (Companies + Branches kombiniert)

### 4. **Kommunikation** 💬
- Anrufe (Calls)
- Benachrichtigungen (Notification Log)

### 5. **Berichte** 📊
- Analytics Dashboard
- Auslastung
- Umsätze

### 6. **Einstellungen** ⚙️
- Benutzer (Users)
- Integrationen (Cal.com, Retell, etc.)
- System-Konfiguration

## Zu entfernende/konsolidierende Resources

1. **Entfernen:**
   - DummyCompanyResource
   - ValidationDashboardResource (unklar)
   - BookingResource (nutze AppointmentResource)
   - MasterServiceResource (nutze ServiceResource)
   - UnifiedEventTypeResource (ungenutzt)
   - Einer der WorkingHour Resources

2. **Konsolidieren:**
   - Companies + Branches → Unternehmensstruktur
   - Services + Event Types → Leistungskatalog
   - PhoneNumbers in Customer/Staff integrieren

3. **Umbenennen (konsistent Deutsch):**
   - Event Types → Veranstaltungstypen
   - Staff → Mitarbeiter
   - Services → Leistungen
   - Calls → Anrufe

## Implementierte Features - Status

### ✅ **Funktionierende Features**
1. **Event Analytics Dashboard** - Unter "Event Management"
2. **Mobile API** - `/api/mobile/*` Endpoints
3. **Notification System** - Queue-basiert
4. **Availability Service** - Mit Caching
5. **Conflict Detection** - Doppelbuchungs-Prävention

### ⚠️ **Zu prüfende Features**
1. **Staff Event Assignment** - Modern vs. Normal Version?
2. **Event Type Import Wizard** - Funktioniert das?
3. **System Cockpit** - Was zeigt das an?

### 🔧 **Fehlende Integration**
1. Analytics Dashboard nicht im Haupt-Dashboard verlinkt
2. Mobile API Dokumentation fehlt
3. Notification Log nicht im Menü

## Nächste Schritte

1. **Bereinigung:**
   - Duplikate entfernen
   - Ungenutzte Resources löschen
   - Konsistente Benennung (Deutsch)

2. **Konsolidierung:**
   - Eine Termin-Resource
   - Eine Service/Event-Type Resource
   - Unternehmensstruktur vereinfachen

3. **Verbesserung:**
   - Dashboard mit wichtigsten KPIs
   - Direkte Links zu Analytics
   - Mobile API Dokumentation

4. **Testing:**
   - Alle Features durchgehen
   - Berechtigungen prüfen
   - Performance testen

Soll ich mit der Bereinigung und Konsolidierung beginnen?