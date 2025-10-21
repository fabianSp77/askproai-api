# Cal.com Service Hosts - Deployment & Fehlerbehandlung

**Datum**: 2025-10-21
**Status**: ✅ Behoben und Bereit zum Testen
**Problem**: View not found Error (gelöst)

---

## 🔧 Behoben Fehler

### Fehler 1: View [filament.fields.calcom-hosts-display] not found

**Symptom**:
```
InvalidArgumentException
View [filament.fields.calcom-hosts-display] not found.
```

**Ursache**:
- File Permission Problem (root statt www-data)
- View Namespace nicht korrekt für ViewField

**Lösung**:
1. ✅ Permissions gefixt: `chown www-data:www-data /resources/views/filament/`
2. ✅ View-Datei zu `resources/views/components/` verschoben
3. ✅ ViewField mit korrektem Namespace konfiguriert

---

## 📁 Neue/Geänderte Dateien

### 1. View-Dateien (Fixed)
```
resources/views/components/calcom-hosts-display-form.blade.php
    ✅ Rendert Cal.com Hosts in Service Edit Form
    ✅ Nutzt $this->getRecord() für Service-Zugriff
    ✅ Permissions: www-data readable

resources/views/components/calcom-hosts-test.blade.php
    ✅ Test-View (für Debugging)
```

### 2. Service-Dateien (Neu)
```
app/Services/CalcomServiceHostsResolver.php
    ✅ Hauptlogik für Host-Abrufung
    ✅ Auto-Sync Funktionalität

app/Services/CalcomHostsHtmlGenerator.php
    ✅ HTML-String Generierung
    ✅ Error Handling mit Logging

app/View/Components/CalcomHostsDisplay.php
    ✅ Blade Component (optional)
```

### 3. Commands (Neu)
```
app/Console/Commands/SyncCalcomServiceHosts.php
    ✅ Artisan Command für Manual Sync
```

### 4. Form-Änderungen (Updated)
```
app/Filament/Resources/ServiceResource.php
    ✅ Neue Cal.com Section eingefügt
    ✅ ViewField mit components.calcom-hosts-display-form
    ✅ Legacy Staff-Section versteckt
```

---

## 🚀 Deployment Checklist

### Phase 1: Pre-Deployment ✅
- [x] PHP Syntax validiert für alle neuen Dateien
- [x] File Permissions korrekt (www-data readable)
- [x] View-Namespace korrekt konfiguriert
- [x] Cache geleert
- [x] Keine aktiven Fehler in ServiceResource

### Phase 2: Deployment
```bash
# 1. Alle neuen Dateien sind bereits deployed
# 2. Run:
php artisan view:clear
php artisan cache:clear
php artisan config:clear

# 3. Testen
php artisan calcom:sync-service-hosts --service-id=47

# 4. UI Test
# Öffne: https://api.askproai.de/admin/services/47/edit
# Sollte neue Section "📅 Cal.com Mitarbeiter" sehen
```

### Phase 3: Testing
- [ ] Service 47 Edit-Seite lädt ohne Fehler
- [ ] Cal.com Hosts Section angezeigt
- [ ] Host Cards mit Avatars & Status sichtbar
- [ ] Kein 500-Fehler
- [ ] Auto-Sync Command funktioniert
- [ ] Retell Voice Test erfolgreich

---

## 🔍 Technische Details

### ViewField Namespace
```php
// ✅ RICHTIG - View wird unter resources/views/components/ gesucht
ViewField::make('calcom_hosts_info')
    ->view('components.calcom-hosts-display-form')

// ❌ FALSCH - Wurde ursprünglich versucht:
ViewField::make('calcom_hosts_info')
    ->view('filament.fields.calcom-hosts-display')
    // → Sucht nach resources/views/filament/fields/calcom-hosts-display.blade.php
```

### View Data Passing
```php
// In der View:
$this->getRecord()  // ← Gibt Livewire Record zurück
$record             // ← Muss ExplicitlyPassed werden, oder $this->getRecord() verwenden
```

### File Permissions
```bash
# Muss sein:
chown -R www-data:www-data /resources/views/
chmod -R 755 /resources/views/

# ❌ NICHT root:root (was das Problem war)
```

---

## 📊 Was wird angezeigt

Nach dem Fix sollte Service 47 Edit zeigen:

```
📅 Cal.com Mitarbeiter (Automatisch)
Mitarbeiter aus Cal.com - Automatisch abgerufen...

┌─────────────────────────────────────────────┐
│  3 Gesamt │ ✅ 1 Verbunden │ ⚠️ 2 Neu │ 📋 1 Für Service │
└─────────────────────────────────────────────┘

┌─ Host 1: Karl Meyer ────────────────────────┐
│  📧 karl@askpro.de                          │
│  ✅ Verbunden → Local Staff                 │
│  📋 Verfügbar für: Service 47 (30min)      │
│                  Service 48 (60min)         │
└─────────────────────────────────────────────┘

┌─ Host 2: Julia Schmidt ─────────────────────┐
│  📧 julia@askpro.de                         │
│  ⚠️ Nicht verbunden                         │
│  Bitte zuordnen                             │
└─────────────────────────────────────────────┘

[Info Box mit Erklärung...]
```

---

## 🛠️ Debugging Falls Fehler Auftritt

### Fehler: "View not found"
```php
// Lösung:
php artisan view:clear
php artisan cache:clear

// Check: View existiert wirklich?
ls -la resources/views/components/calcom-hosts-display-form.blade.php
// Should output: -rw-r--r-- www-data:www-data ...

// Check: Permissions richtig?
stat resources/views/components/calcom-hosts-display-form.blade.php | grep Access
// Should be: Access: (0755/-rwxr-xr-x)  Uid: (   33/ www-data)
```

### Fehler: "Blank Section (Nothing Renders)"
```php
// Check: Service hat Cal.com Event Type?
mysql> SELECT id, calcom_event_type_id FROM services WHERE id=47;

// Check: TeamEventTypeMapping existiert?
mysql> SELECT * FROM team_event_type_mappings
       WHERE calcom_event_type_id=2563193;

// Check: Hosts Array ist nicht null?
mysql> SELECT JSON_ARRAY_LENGTH(hosts) FROM team_event_type_mappings
       WHERE calcom_event_type_id=2563193;
// Should be > 0

// Wenn alle ja, dann Log prüfen:
tail -f storage/logs/laravel.log | grep -i calcom
```

### Fehler: "Exception in View"
```php
// Die View catcht Exceptions, aber loggt sie:
tail -f storage/logs/laravel.log

// Oder direct in CalcomServiceHostsResolver.php::generate()
// Suche nach "CalcomHostsHtmlGenerator error"
```

---

## 🎯 Nächste Schritte nach Deployment

1. **Service 47 öffnen**
   - Neue Section sichtbar?
   - Hosts angezeigt?

2. **Auto-Sync durchführen**
   ```bash
   php artisan calcom:sync-service-hosts --service-id=47
   ```

3. **CalcomHostMapping checken**
   ```bash
   mysql> SELECT * FROM calcom_host_mappings
          WHERE company_id=15 AND calcom_host_id IN (...);
   ```

4. **Retell Voice Test**
   - Buchung durchführen
   - Correct Staff Creation prüfen
   - Appointment mit staff_id verknüpft?

5. **Production Monitoring**
   - Logs monitoren: `tail -f storage/logs/laravel.log`
   - Error Rate tracken
   - Performance impactieren?

---

## 📝 Command Reference

```bash
# Alle Services syncing
php artisan calcom:sync-service-hosts

# Nur Service 47
php artisan calcom:sync-service-hosts --service-id=47

# Nur AskProAI (Company 15)
php artisan calcom:sync-service-hosts --company-id=15

# With Logging
php artisan calcom:sync-service-hosts --service-id=47 -v
```

---

## ✅ Abschluss-Checklist

- [x] View-Namespace korrekt
- [x] File-Permissions gefixt
- [x] PHP-Syntax validiert
- [x] Cache geleert
- [x] Keine Critical Errors
- [x] Dokumentation aktualisiert
- [ ] Production Test durchgeführt (USER)
- [ ] Monitoring aktiviert (USER)
- [ ] Team informiert (USER)

---

**Status**: ✅ Ready für Production Deployment
**Next Step**: User testet Service 47 Edit-Seite
**Fallback**: Legacy Staff-Section ist immer noch verfügbar (versteckt)
