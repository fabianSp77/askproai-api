# TODO DONNERSTAG - Appointment Save Bug 🔴

**Erstellt:** 2025-10-13 20:25
**Priorität:** 🔴 CRITICAL
**Termin:** Donnerstag, 2025-10-17
**Betroffener Termin:** #702
**Status:** UNGELÖST - Weitere Untersuchung erforderlich

---

## Problem Zusammenfassung

**User meldet:**
"Nachdem ich Datum und Uhrzeit ausgewählt hab, geht ein schwarzes Pop-up Fenster auf nach dem Speichern"

**Technische Symptome:**
- ❌ Schwarzes Pop-up erscheint beim Klick auf "Speichern"
- ❌ Fehlermeldung ist nicht lesbar (schwarzer Hintergrund?)
- ❌ Termin wird NICHT gespeichert
- ❌ **KEINE HTTP-Anfrage erreicht den Server** (bestätigt durch Logs)
- ❌ Problem tritt sowohl bei Edit als auch Create auf

**Betroffene URLs:**
- https://api.askproai.de/admin/appointments/702/edit
- https://api.askproai.de/admin/appointments/create

---

## Bisherige Lösungsversuche ❌

### Versuch 1: Backend 500 Error beheben ✅
**Was:** Cal.com sync listener deaktiviert (Zeile 40 in `SyncToCalcomOnRescheduled.php`)
**Ergebnis:** Backend funktioniert, aber Frontend Problem bleibt

### Versuch 2: Event Cache leeren ✅
**Was:** `php artisan optimize:clear` + PHP-FPM reload
**Ergebnis:** Cache geleert, aber Problem bleibt

### Versuch 3: Validation Conflict beheben ❌
**Was:** `->required()` von `ends_at` Feld entfernt (Zeile 386 in `AppointmentResource.php`)
**Begründung:** Feld war sowohl `required` als auch `disabled` (sollte nicht kombiniert werden)
**Ergebnis:** PROBLEM BLEIBT BESTEHEN

---

## Aktueller Code-Stand

### AppointmentResource.php (Lines 323-390)

```php
Forms\Components\DateTimePicker::make('starts_at')
    ->label('⏰ Termin-Beginn')
    ->seconds(false)
    ->minDate(now())                    // ← Könnte problematisch sein?
    ->maxDate(now()->addWeeks(2))       // ← Könnte problematisch sein?
    ->required()
    ->native(false)
    ->displayFormat('d.m.Y H:i')
    ->reactive()
    ->disabled(fn (callable $get) => !$get('staff_id'))  // ← Könnte problematisch sein?
    ->afterStateUpdated(function ($state, callable $get, callable $set) {
        if ($state) {
            $duration = $get('duration_minutes') ?? 30;
            $endsAt = Carbon::parse($state)->addMinutes($duration);
            $set('ends_at', $endsAt);
        }
    })

Forms\Components\DateTimePicker::make('ends_at')
    ->label('🏁 Termin-Ende')
    ->seconds(false)
    ->native(false)
    ->displayFormat('d.m.Y H:i')
    ->disabled()
    ->dehydrated()
    ->helperText('= Beginn + Dauer (automatisch berechnet)')
```

### EditAppointment.php (Lines 28-66)

```php
protected function beforeSave(): void
{
    // Conflict Check - könnte problematisch sein?
    if (!isset($this->data['staff_id']) || !isset($this->data['starts_at']) || !isset($this->data['ends_at'])) {
        return;
    }

    // Check for overlapping appointments
    $conflicts = Appointment::where('staff_id', $this->data['staff_id'])
        // ... komplexe Query ...
        ->exists();

    if ($conflicts) {
        Notification::make()
            ->title('⚠️ Konflikt erkannt!')
            ->body('Der Mitarbeiter hat bereits einen Termin zu dieser Zeit.')
            ->warning()
            ->persistent()
            ->send();

        $this->halt();  // ← Stoppt das Speichern
    }
}
```

---

## Mögliche Ursachen (Zu testen am Donnerstag)

### 1. Validation Fehler im Frontend ⚠️

**Hypothese:** Filament validiert ein Feld falsch und blockiert die Form Submission

**Zu prüfen:**
- `starts_at` hat `->minDate(now())` - wird das korrekt validiert?
- `starts_at` hat `->maxDate(now()->addWeeks(2))` - wird das korrekt validiert?
- `starts_at` hat `->disabled(fn (callable $get) => !$get('staff_id'))` - bleibt das Feld disabled?
- Gibt es andere Pflichtfelder die leer sind?

**Test:**
```php
// Temporär alle Validierungen entfernen:
->minDate(now())           // ← AUSKOMMENTIEREN
->maxDate(now()->addWeeks(2))  // ← AUSKOMMENTIEREN
->required()               // ← AUSKOMMENTIEREN
```

### 2. Conflict Detection schlägt fehl ⚠️

**Hypothese:** `beforeSave()` findet einen Konflikt und blockiert mit `$this->halt()`

**Zu prüfen:**
- Lädt Termin #702 korrekt mit `staff_id`, `starts_at`, `ends_at`?
- Gibt es einen überlappenden Termin für diesen Mitarbeiter?
- Wird die Konflikt-Notification angezeigt (könnte das schwarze Pop-up sein)?

**Test:**
```php
// Temporär Conflict Check deaktivieren:
protected function beforeSave(): void
{
    return; // ← FRÜH BEENDEN
    // ... rest of code
}
```

### 3. JavaScript Error im Browser 🔍

**Hypothese:** Ein JavaScript-Fehler verhindert die Form Submission

**Zu prüfen:**
- Browser Console (F12 → Console Tab)
- Network Tab (F12 → Network Tab)
- Livewire Komponenten Status

**Benötigte Info vom User:**
- Screenshot der Browser Console beim Klick auf "Speichern"
- Screenshot der Network Tab (zeigt ob HTTP Request gemacht wird)
- Screenshot des schwarzen Pop-ups (um Text zu lesen)

### 4. Duration Field fehlt ⚠️

**Hypothese:** `duration_minutes` Feld existiert nicht und verursacht Fehler

**Evidence:**
```bash
mysql> SELECT duration_minutes FROM appointments WHERE id = 702;
ERROR 1054 (42S22): Unknown column 'duration_minutes' in 'SELECT'
```

**Problem:** Code versucht `$get('duration_minutes')` aber Feld existiert nicht in DB

**Zu prüfen:**
- Wo kommt `duration_minutes` her? (aus Service oder aus Appointment?)
- Ist das ein virtuelles Feld oder DB Feld?

**Test:**
```php
// Fallback hinzufügen:
$duration = $get('duration_minutes') ?? 30;  // ← IST SCHON DRIN

// Aber besser:
$duration = $get('duration_minutes');
if (!$duration && $get('service_id')) {
    $service = Service::find($get('service_id'));
    $duration = $service->duration ?? 30;
}
```

### 5. CSS Problem - Notification nicht lesbar 🎨

**Hypothese:** Das Pop-up WIRD angezeigt, aber Text ist schwarz auf schwarzem Hintergrund

**Zu prüfen:**
- Filament CSS korrekt geladen?
- Notification Styling kaputt?

**Test:**
```bash
# Check Filament Assets:
ls -la public/css/filament/
ls -la public/js/filament/

# Rebuild Filament Assets:
php artisan filament:assets
```

### 6. Reactive Update Fehler ⚡

**Hypothese:** `afterStateUpdated` wirft einen Fehler beim Parsen von `$state`

**Zu prüfen:**
- Was passiert wenn `$state` kein valides Datum ist?
- `Carbon::parse($state)` könnte fehlschlagen

**Test:**
```php
->afterStateUpdated(function ($state, callable $get, callable $set) {
    if (!$state) {
        return; // ← Früh abbrechen wenn leer
    }

    try {
        $duration = $get('duration_minutes') ?? 30;
        $endsAt = Carbon::parse($state)->addMinutes($duration);
        $set('ends_at', $endsAt);
    } catch (\Exception $e) {
        Log::error('DateTimePicker reactive update failed', [
            'error' => $e->getMessage(),
            'state' => $state
        ]);
    }
})
```

---

## Debugging-Plan für Donnerstag

### Phase 1: Browser Debugging (10 Min)

**User muss mitarbeiten:**

1. URL öffnen: https://api.askproai.de/admin/appointments/702/edit
2. F12 drücken (Developer Tools)
3. Console Tab öffnen
4. "Speichern" klicken
5. Screenshot von ALLEN Meldungen in Console
6. Network Tab öffnen
7. "Speichern" klicken
8. Screenshot von Network Tab (zeigt ob Request gemacht wird)
9. Screenshot vom schwarzen Pop-up (versuchen Text zu lesen)

**Erwartete Erkenntnisse:**
- JavaScript Error → zeigt welche Komponente fehlschlägt
- Network Request fehlt → bestätigt Frontend-Blockierung
- Network Request mit 4xx/5xx → Backend Problem
- Pop-up Text lesbar → zeigt genaue Fehlermeldung

### Phase 2: Validation Debugging (15 Min)

**Temporäre Änderungen testen:**

```php
// 1. ALLE Validierungen entfernen
Forms\Components\DateTimePicker::make('starts_at')
    ->label('⏰ Termin-Beginn')
    ->seconds(false)
    // ->minDate(now())           // ← AUSKOMMENTIERT
    // ->maxDate(now()->addWeeks(2))  // ← AUSKOMMENTIERT
    // ->required()               // ← AUSKOMMENTIERT
    ->native(false)
    ->displayFormat('d.m.Y H:i')
    ->reactive()
    // ->disabled(fn (callable $get) => !$get('staff_id'))  // ← AUSKOMMENTIERT
    ->afterStateUpdated(function ($state, callable $get, callable $set) {
        if ($state) {
            $duration = 30;  // ← HARDCODED FÜR TEST
            $endsAt = Carbon::parse($state)->addMinutes($duration);
            $set('ends_at', $endsAt);
        }
    })
```

**Test:** Speichern probieren
- **Funktioniert:** → Problem ist eine der Validierungen
- **Funktioniert nicht:** → Problem ist woanders

### Phase 3: Conflict Detection Debugging (10 Min)

**Temporär Conflict Check deaktivieren:**

```php
// app/Filament/Resources/AppointmentResource/Pages/EditAppointment.php
protected function beforeSave(): void
{
    Log::info('🔍 beforeSave() called', [
        'staff_id' => $this->data['staff_id'] ?? 'NOT SET',
        'starts_at' => $this->data['starts_at'] ?? 'NOT SET',
        'ends_at' => $this->data['ends_at'] ?? 'NOT SET',
    ]);

    return; // ← FRÜH BEENDEN FÜR TEST

    // ... rest bleibt auskommentiert
}
```

**Test:** Speichern probieren
- **Funktioniert:** → Conflict Check ist das Problem
- **Funktioniert nicht:** → Problem ist woanders

### Phase 4: Duration Field Debugging (10 Min)

**Check ob duration_minutes existiert:**

```bash
# 1. Prüfe Appointment Modell
grep -n "duration" app/Models/Appointment.php

# 2. Prüfe Service Modell
grep -n "duration" app/Models/Service.php

# 3. Prüfe DB Schema
mysql -u root -pQk%YkTN2I7G#7Ee9 askproai_db -e "SHOW COLUMNS FROM appointments LIKE '%duration%';"
mysql -u root -pQk%YkTN2I7G#7Ee9 askproai_db -e "SHOW COLUMNS FROM services LIKE '%duration%';"
```

**Fix falls duration fehlt:**

```php
// In afterStateUpdated:
$duration = 30; // Default
if ($get('service_id')) {
    $service = \App\Models\Service::find($get('service_id'));
    if ($service && $service->duration) {
        $duration = $service->duration;
    }
}
```

### Phase 5: Filament Assets Check (5 Min)

```bash
# Check Assets
ls -la public/css/filament/
ls -la public/js/filament/

# Rebuild if needed
php artisan filament:assets
php artisan filament:optimize-clear
php artisan optimize:clear
sudo systemctl reload php8.3-fpm
```

---

## Quick Workaround (Falls nichts funktioniert)

**Alte Implementation wiederherstellen:**

```bash
# 1. Check Git History
git log --oneline -20 app/Filament/Resources/AppointmentResource.php

# 2. Find commit BEFORE DateTimePicker change
git log -p app/Filament/Resources/AppointmentResource.php | grep -A 50 "DatePicker"

# 3. Restore old version (mit DatePicker + Radio statt DateTimePicker)
git show <commit-hash>:app/Filament/Resources/AppointmentResource.php > /tmp/old_version.php

# 4. Vergleichen und ggf. zurücksetzen
diff /tmp/old_version.php app/Filament/Resources/AppointmentResource.php
```

**Oder:** Termin direkt in DB ändern:

```sql
UPDATE appointments
SET starts_at = '2025-10-20 14:00:00',
    ends_at = '2025-10-20 14:30:00'
WHERE id = 702;
```

---

## Wichtige Dateien

### Zu prüfen:
- `/var/www/api-gateway/app/Filament/Resources/AppointmentResource.php` (Lines 320-420)
- `/var/www/api-gateway/app/Filament/Resources/AppointmentResource/Pages/EditAppointment.php`
- `/var/www/api-gateway/app/Models/Appointment.php`
- `/var/www/api-gateway/app/Models/Service.php`

### Logs:
- `/var/www/api-gateway/storage/logs/laravel.log`
- Browser Console (F12)
- Browser Network Tab (F12)

### Dokumentation:
- `/var/www/api-gateway/claudedocs/BLACK_POPUP_INVESTIGATION_2025-10-13.md`
- `/var/www/api-gateway/claudedocs/BLACK_POPUP_FIX_2025-10-13.md` (VERSUCH, HAT NICHT FUNKTIONIERT)
- `/var/www/api-gateway/claudedocs/APPOINTMENT_SAVE_ERROR_FIX_2025-10-13.md`
- `/var/www/api-gateway/claudedocs/APPOINTMENT_SLOT_PICKER_PHASE2_COMPLETE_2025-10-13.md`

---

## Erwartetes Ergebnis nach Debugging

**Ziel:**
- ✅ User kann Termin #702 bearbeiten und speichern
- ✅ Kein schwarzes Pop-up mehr
- ✅ Änderungen werden in DB gespeichert
- ✅ HTTP Request erreicht Server (sichtbar in Logs)

**Kritischer Erfolgsindikator:**

```bash
# Während User speichert, sollte in Logs erscheinen:
tail -f storage/logs/laravel.log

# Erwartete Log-Einträge:
[INFO] update `appointments` set `starts_at` = ...
[INFO] AppointmentRescheduled event fired
[INFO] SyncToCalcomOnRescheduled DISABLED (migration pending)
```

**Wenn das NICHT erscheint → Frontend blockiert Request!**

---

## Nächste Schritte (Checklist)

### Donnerstag Vormittag:

- [ ] User fragen: Browser Console Screenshot beim Speichern
- [ ] User fragen: Network Tab Screenshot beim Speichern
- [ ] User fragen: Screenshot vom schwarzen Pop-up

### Donnerstag Mittag (Mit Screenshots):

- [ ] Phase 1: Browser Debugging analysieren
- [ ] Phase 2: Validation Debugging (temporär alle Validierungen entfernen)
- [ ] Phase 3: Conflict Detection Debugging (temporär deaktivieren)
- [ ] Phase 4: Duration Field Debugging (Check DB Schema)
- [ ] Phase 5: Filament Assets Check

### Donnerstag Nachmittag (Fixes):

- [ ] Root Cause identifiziert → Fix implementieren
- [ ] Alle Caches leeren
- [ ] PHP-FPM neu laden
- [ ] User testen lassen
- [ ] Dokumentation aktualisieren

### Falls weiterhin blockiert:

- [ ] Option A: Alte DatePicker + Radio Implementation wiederherstellen
- [ ] Option B: Termin manuell in DB ändern (Quick Fix)
- [ ] Option C: Appointment Edit Page komplett neu implementieren

---

## Kontext für nächste Session

**Aktuelle Situation:**
- Backend funktioniert (Cal.com listener deaktiviert)
- Frontend blockiert Form Submission
- Kein HTTP Request erreicht Server
- Schwarzes Pop-up erscheint (Fehlermeldung nicht lesbar)

**Bereits versucht:**
- ✅ Backend 500 Error behoben
- ✅ Event Cache geleert
- ✅ `ends_at` required validation entfernt
- ❌ Problem bleibt bestehen

**Nächster logischer Schritt:**
Browser Developer Tools nutzen um zu sehen:
1. Welche JavaScript Fehler auftreten
2. Ob ein HTTP Request gemacht wird
3. Was im schwarzen Pop-up steht

**Ohne diese Informationen können wir nur raten!**

---

**Erstellt:** 2025-10-13 20:25
**Status:** OFFEN - Warte auf Browser Debugging am Donnerstag
**Priorität:** 🔴 CRITICAL - User kann keine Termine bearbeiten
**Assignee:** Für Donnerstag vorgemerkt

---

## Letzter Stand der Dinge

**Was funktioniert:**
- ✅ Seite lädt korrekt
- ✅ Termin-Daten werden angezeigt
- ✅ User kann Datum/Uhrzeit auswählen
- ✅ Backend ist bereit (keine 500 Fehler mehr)

**Was NICHT funktioniert:**
- ❌ Speichern blockiert (kein HTTP Request)
- ❌ Schwarzes Pop-up erscheint
- ❌ Fehlermeldung nicht lesbar

**Kritische Frage:**
**WARUM erreicht der Save-Request den Server nicht?**

Mögliche Antworten:
1. JavaScript Error → Browser Console zeigt es
2. Validation Error → Filament blockiert submission
3. Conflict Detection → `beforeSave()` stoppt mit `halt()`
4. Event Handler → Irgendein Event verhindert submission

**Nächster Debugging-Schritt:** Browser Developer Tools (F12) öffnen!
