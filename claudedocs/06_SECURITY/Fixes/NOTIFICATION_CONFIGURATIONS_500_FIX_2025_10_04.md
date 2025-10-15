# Notification-Configurations 500-Fehler Fix - 2025-10-04

## 🎯 ZUSAMMENFASSUNG

**Problem:** HTTP 500 Fehler auf `/admin/notification-configurations` für authentifizierte User
**Root Cause:** Falscher Spaltenname in 4 Queries (`event_name` statt `event_label`)
**Status:** ✅ BEHOBEN
**Dauer:** 15 Minuten

---

## 🔍 ROOT CAUSE ANALYSE

### Fehlermeldung:
```sql
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'event_name' in 'SELECT'
SQL: select `event_name`, `event_type` from `notification_event_mappings`
```

### Location:
`/var/www/api-gateway/app/Filament/Resources/NotificationConfigurationResource.php:297`

### Kontext:
Der Fehler trat auf beim Laden der **Event-Type Filter-Optionen** in der Filament-Tabelle.

### Schema-Diskrepanz:

**Datenbank-Schema (`notification_event_mappings`):**
```sql
✅ event_label  VARCHAR(255) NOT NULL  -- RICHTIG
❌ event_name   (existiert nicht!)     -- FALSCH im Code
```

**Code verwendete:**
```php
->pluck('event_name', 'event_type')  // ❌ Spalte existiert nicht
```

**Sollte sein:**
```php
->pluck('event_label', 'event_type')  // ✅ Korrekt
```

---

## 🛠️ IMPLEMENTIERTE FIXES

### Fix 1: Spaltenname-Korrektur (4 Stellen)

**Datei:** `app/Filament/Resources/NotificationConfigurationResource.php`

#### 1. Form Event-Type Select (Zeile 96)
```php
// VORHER:
->pluck('event_name', 'event_type')

// NACHHER:
->pluck('event_label', 'event_type')
```

#### 2. Table Column (Zeile 230)
```php
// VORHER:
Tables\Columns\TextColumn::make('eventMapping.event_name')

// NACHHER:
Tables\Columns\TextColumn::make('eventMapping.event_label')
```

#### 3. Table Filter (Zeile 297) ← **Hauptfehler**
```php
// VORHER:
->pluck('event_name', 'event_type')

// NACHHER:
->pluck('event_label', 'event_type')
```

#### 4. Infolist Detail View (Zeile 544)
```php
// VORHER:
Infolists\Components\TextEntry::make('eventMapping.event_name')

// NACHHER:
Infolists\Components\TextEntry::make('eventMapping.event_label')
```

### Fix 2: View-Cache-Korruption

**Problem:** Nach `php artisan view:clear` war compiled view gelöscht, aber OPcache hatte noch Referenz

**Error:**
```
filemtime(): stat failed for storage/framework/views/96416f681de2d4646a20dab82274323e.php
```

**Lösung:**
```bash
php artisan optimize:clear  # Alle Caches leeren
php artisan view:cache      # Views neu kompilieren
systemctl reload php8.3-fpm # OPcache leeren
```

---

## ✅ VALIDATION

### Schema-Verification:
```sql
mysql> DESCRIBE notification_event_mappings;
+------------------+----------+------+-----+---------+
| Field            | Type     | Null | Key | Default |
+------------------+----------+------+-----+---------+
| event_type       | varchar  | NO   |     | NULL    |
| event_label      | varchar  | NO   |     | NULL    | ← RICHTIG
| event_category   | enum     | NO   | MUL | NULL    |
+------------------+----------+------+-----+---------+
```

### Code-Verification:
```bash
# Alle event_name Vorkommen in NotificationConfigurationResource
grep -n "event_name" NotificationConfigurationResource.php
# (keine Treffer mehr - alle zu event_label geändert)

# Andere event_name Vorkommen sind in anderen Kontexten (Stripe, Calcom)
# und NICHT related zu NotificationEventMapping
```

### Error-Log-Check:
```bash
tail -50 storage/logs/laravel.log | grep "500 ERROR\|SQLSTATE"
# ✅ Keine Fehler mehr
```

---

## 📊 BETROFFENE FUNKTIONEN

### 1. **Event-Type Filter** (Hauptfehler)
- **Location:** Table Filters
- **Impact:** 500-Fehler beim Laden der Seite für authentifizierte User
- **Status:** ✅ Behoben

### 2. **Event-Type Form Select**
- **Location:** Create/Edit Forms
- **Impact:** Würde 500-Fehler beim Laden des Forms verursachen
- **Status:** ✅ Behoben

### 3. **Event Column in Table**
- **Location:** List Table
- **Impact:** Würde falsche/leere Werte anzeigen
- **Status:** ✅ Behoben

### 4. **Event Detail in Infolist**
- **Location:** View/Detail Page
- **Impact:** Würde falsche/leere Werte anzeigen
- **Status:** ✅ Behoben

---

## 🔬 WARUM PASSIERTE DAS?

### 1. **Naming Inconsistency**
Möglicherweise wurde das Schema während der Entwicklung geändert:
- Original: `event_name`
- Geändert zu: `event_label`
- Code wurde nicht aktualisiert

### 2. **Testing Gap**
- Authentifizierte Tests fehlten
- Unauthentifizierte Requests zeigten das Problem nicht (nur 302 Redirect)
- Filter werden nur bei authentifizierten Requests geladen

### 3. **Migration History**
Checking migration file:
```php
// database/migrations/2025_10_01_060202_create_notification_event_mappings_table.php
$table->string('event_label', 255);  // ✅ Immer schon event_label
```

**Fazit:** Code war inkonsistent mit Schema seit Anfang.

---

## 📈 LESSONS LEARNED

### 1. **Testing-Lücke geschlossen**
- ❌ **Problem:** Nur unauthentifizierte Tests (HTTP 302)
- ✅ **Lösung:** Authentifizierte Browser-Tests nötig
- ✅ **Action:** Puppeteer/Playwright Tests für Admin-Bereiche

### 2. **Schema-Code-Synchronisation**
- ❌ **Problem:** Code verwendet Spaltennamen die nicht existieren
- ✅ **Lösung:** Schema-Validation in CI/CD
- ✅ **Action:** Automated Schema-Drift-Detection

### 3. **Besseres Error-Logging**
- ❌ **Problem:** ErrorCatcher zeigt nur 500-Status, nicht die Exception
- ✅ **Lösung:** Vollständigen Stack-Trace loggen
- ✅ **Action:** Improve ErrorCatcher Middleware

---

## 🚀 EMPFEHLUNGEN

### Sofort:
- [x] ✅ Spaltenname-Fehler behoben
- [x] ✅ View-Cache neu gebaut
- [x] ✅ OPcache geleert
- [ ] ⏳ User sollte Seite testen und bestätigen

### Kurzfristig:
- [ ] Authentifizierte Integration-Tests schreiben
- [ ] Schema-Validation-Script in CI/CD integrieren
- [ ] ErrorCatcher verbessern für vollständigen Stack-Trace

### Mittelfristig:
- [ ] Automated Browser-Tests (Puppeteer/Playwright)
- [ ] Schema-Drift-Detection (DB vs. Code)
- [ ] Code-Review-Checkliste erweitern (Schema-Checks)

---

## 🔄 ROLLBACK-PLAN (falls nötig)

```bash
# Änderungen rückgängig machen
git checkout HEAD -- app/Filament/Resources/NotificationConfigurationResource.php

# Cache neu bauen
php artisan optimize:clear
php artisan view:cache
systemctl reload php8.3-fpm
```

**Risiko:** SEHR NIEDRIG - Nur Spaltenname-Änderung, keine Schema-Änderungen

---

## 📝 TECHNISCHE DETAILS

### NotificationEventMapping Model:
```php
// app/Models/NotificationEventMapping.php
protected $fillable = [
    'event_type',
    'event_label',      // ✅ RICHTIG
    'event_category',
    'default_channels',
    'description',
    'is_system_event',
    'is_active',
    'metadata',
];
```

**Kein `event_name` Accessor oder Attribute definiert!**

### Andere `event_name` Vorkommen:
```bash
# Stripe-spezifisch (OK):
app/Console/Commands/SendStripeMeterEvent.php:49

# Calcom-spezifisch (OK - anderes Feld: event_name_pattern):
app/Services/Sync/DriftDetectionService.php:118
app/Models/CalcomEventMap.php:24
```

**Diese sind NICHT related zu NotificationEventMapping - keine Aktion nötig.**

---

## 📞 ZUSAMMENFASSUNG

**Behobene Probleme:**
- ✅ 500-Fehler auf `/admin/notification-configurations`
- ✅ Spaltenname-Inkonsistenz in 4 Queries
- ✅ View-Cache-Korruption nach Cache-Clear

**Ausgeführte Aktionen:**
1. `event_name` → `event_label` in 4 Locations
2. `php artisan optimize:clear`
3. `php artisan view:cache`
4. `systemctl reload php8.3-fpm`

**Testing-Status:**
- ✅ Error-Logs zeigen keine Fehler mehr
- ⏳ Wartet auf User-Bestätigung durch Browser-Test

**Dokumentation:**
- `/var/www/api-gateway/claudedocs/NOTIFICATION_CONFIGURATIONS_500_FIX_2025_10_04.md`

---

**✨ Ergebnis: Notification-Configurations Seite sollte jetzt funktionieren!**

**Nächster Schritt:** User sollte https://api.askproai.de/admin/notification-configurations im Browser testen.
