# 🚀 Aktionsplan für Morgen - AskProAI Vereinfachung

## 📅 Datum: [Morgen eintragen]
## ⏰ Geschätzte Dauer: 6-8 Stunden

---

## 🎯 ZIEL DES TAGES
Das System vereinfachen und stabilisieren, damit der Kern-Flow (Anruf → Termin) zu 100% funktioniert.

---

## 📋 VORMITTAG (3-4 Stunden)

### 1. Status-Check (30 Min)
```bash
# Führen Sie diese Scripts aus:
php check_askproai_berlin.php
php check_calcom_structure.php

# Dokumentieren Sie:
- [ ] Funktioniert Test-Anruf?
- [ ] Werden Termine in Cal.com erstellt?
- [ ] Gibt es Fehler in den Logs?
```

### 2. Aufräumen - Phase 1 (2 Stunden)

#### A. Test-Dateien löschen:
```bash
# Alle test_*.php Dateien entfernen
rm test_*.php
rm check_*.php  # Nach Dokumentation

# Git aufräumen
git add -A
git commit -m "cleanup: Remove test files"
```

#### B. Redundante Services zusammenführen:
```bash
# BEHALTEN:
- app/Services/CalcomV2Service.php
- app/Services/RetellService.php (oder RetellV2Service.php)

# LÖSCHEN:
- app/Services/CalcomService.php
- app/Services/CalcomUnifiedService.php
- app/Services/CalcomSyncService.php (beide!)
- app/Services/RetellV1Service.php
- app/Services/RetellAIService.php
```

#### C. Ungenutzte Migrations archivieren:
```bash
# Neuen Ordner erstellen
mkdir database/migrations/_archive

# Alle "fix_" und "add_missing_" migrations verschieben
mv database/migrations/*fix_*.php database/migrations/_archive/
mv database/migrations/*add_missing*.php database/migrations/_archive/
```

### 3. Datenbank bereinigen (1 Stunde)

#### A. Backup erstellen:
```bash
mysqldump -u root -p askproai > backup_before_cleanup_$(date +%Y%m%d).sql
```

#### B. Redundante Tabellen entfernen:
```sql
-- Diese Tabellen können weg:
DROP TABLE IF EXISTS kunden;  -- use customers
DROP TABLE IF EXISTS tenants;  -- use companies
DROP TABLE IF EXISTS dummy_companies;
DROP TABLE IF EXISTS master_services;
DROP TABLE IF EXISTS service_staff;
DROP TABLE IF EXISTS staff_services;
DROP TABLE IF EXISTS unified_event_types;
DROP TABLE IF EXISTS calendar_event_types;

-- OAuth Tabellen (nicht genutzt)
DROP TABLE IF EXISTS oauth_access_tokens;
DROP TABLE IF EXISTS oauth_auth_codes;
DROP TABLE IF EXISTS oauth_clients;
DROP TABLE IF EXISTS oauth_personal_access_clients;
DROP TABLE IF EXISTS oauth_refresh_tokens;

-- Alte Booked-System Tabellen
DROP TABLE IF EXISTS booked_accessories;
DROP TABLE IF EXISTS booked_accessory_resources;
-- ... (alle 28 booked_* Tabellen)
```

---

## 🍕 MITTAGSPAUSE (1 Stunde)

---

## 📋 NACHMITTAG (3-4 Stunden)

### 4. Core-Flow stabilisieren (2 Stunden)

#### A. PhoneNumberResolver fixen:
```php
// app/Services/PhoneNumberResolver.php
// Zeile 98 - Nur aktive Branches:
$branch = Branch::where('phone_number', $normalizedNumber)
    ->where('active', true)  // HINZUFÜGEN!
    ->first();
```

#### B. Vereinfachter Appointment Flow:
Erstellen Sie eine neue Datei:
```php
// app/Services/SimpleBookingService.php
class SimpleBookingService 
{
    public function bookFromCall($callData) 
    {
        // 1. Customer finden/erstellen
        // 2. Nächsten freien Slot finden
        // 3. Appointment erstellen
        // 4. Cal.com Booking erstellen
        // 5. Email senden
        // FERTIG - nicht komplizierter!
    }
}
```

### 5. Customer Portal Grundstruktur (1 Stunde)

#### A. Neue Portal-Struktur anlegen:
```bash
# Filament Resource für Customer Portal
php artisan make:filament-resource CustomerDashboard --simple

# Basis-Views:
- Termine-Übersicht
- Anruf-Historie  
- Rechnungen
- Einstellungen
```

#### B. Portal-Routes definieren:
```php
// routes/customer.php
Route::prefix('portal')->middleware('auth:customer')->group(function () {
    Route::get('/', [CustomerPortalController::class, 'dashboard']);
    Route::get('/appointments', [CustomerPortalController::class, 'appointments']);
    Route::get('/calls', [CustomerPortalController::class, 'calls']);
    Route::get('/invoices', [CustomerPortalController::class, 'invoices']);
});
```

### 6. Dokumentation & Commit (30 Min)

#### A. Änderungen dokumentieren:
```markdown
# CHANGELOG.md
## [Datum] - Cleanup & Simplification
### Removed
- 56 redundante Tabellen
- 5 doppelte Services
- Test-Dateien

### Added
- Customer Portal Grundstruktur
- SimpleBookingService

### Fixed
- PhoneNumberResolver active check
```

#### B. Git Commit:
```bash
git add -A
git commit -m "refactor: Major cleanup and simplification

- Remove redundant tables and services
- Add customer portal foundation
- Fix branch active validation
- Simplify booking flow"

git push
```

---

## 🎯 ERFOLGSKRITERIEN

Am Ende des Tages sollten Sie haben:

### ✅ Weniger Komplexität:
- [ ] ~60 statt 119 Tabellen
- [ ] 2 statt 12 Service-Klassen für APIs
- [ ] Saubere Migration-Historie

### ✅ Funktionierende Features:
- [ ] Test-Anruf → Termin funktioniert
- [ ] Keine Fehler in den Logs
- [ ] Customer Portal Basis vorhanden

### ✅ Bessere Struktur:
- [ ] Ein Service pro Integration
- [ ] Klare Namensgebung
- [ ] Dokumentierte Änderungen

---

## 💡 WICHTIGE HINWEISE

### Was Sie NICHT machen sollten:
- ❌ Neue Features hinzufügen
- ❌ Perfektionismus (80% ist gut genug)
- ❌ Große Refactorings (Schritt für Schritt)

### Bei Problemen:
1. Backup einspielen
2. Einen Schritt zurück
3. Kleinere Änderungen

### Fragen für morgen notieren:
- [ ] _____________________
- [ ] _____________________
- [ ] _____________________

---

## 🚦 NÄCHSTE SCHRITTE (Nach morgen)

### Tag 2: Automation
- Retell Agent Auto-Setup
- Cal.com Event Import vereinfachen

### Tag 3: Billing
- Stripe Integration
- Usage Tracking
- Erste Rechnung

### Tag 4: Customer Portal
- Login/Auth
- Termine-Ansicht
- Anruf-Historie

### Tag 5: Testing & Launch
- End-to-End Tests
- Performance-Optimierung
- Soft-Launch mit 1 Kunden

---

## 📞 SUPPORT

Wenn Sie nicht weiterkommen:
1. Machen Sie einen Screenshot
2. Kopieren Sie die Fehlermeldung
3. Notieren Sie was Sie versucht haben
4. Wir lösen es gemeinsam!

**Denken Sie daran**: Fortschritt > Perfektion

Bis morgen! 🎯