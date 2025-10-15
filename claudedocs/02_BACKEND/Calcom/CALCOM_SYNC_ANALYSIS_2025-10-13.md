# Cal.com Synchronisations-Analyse
**Datum:** 2025-10-13 16:15 UTC
**Status:** 🔴 **KRITISCH - Einseitige Synchronisation**
**Priorität:** 🔴 HOCH

---

## 🎯 USER-FRAGE

> "Kannst du bitte überprüfen, ob unsere Datenbank und unsere UI synchron mit der Cal.com Verbindung ist. D.h. meine Frage ist wenn ich jetzt aus unserer UI Termine bearbeiten möchte d.h. die verschieben möchte oder löschen möchte also stornieren möchte oder anderen Mitarbeitern zu ordnen. Etc. was ist hier aktuell möglich? Ist das synchron?"

---

## ❌ KRITISCHES ERGEBNIS

**Die Synchronisation funktioniert NUR in EINE Richtung:**

```
Cal.com → Datenbank  ✅ FUNKTIONIERT (via Webhooks)
Datenbank → Cal.com  ❌ FUNKTIONIERT NICHT (fehlt komplett!)
```

**Konsequenz:** Änderungen in der UI werden NICHT zu Cal.com synchronisiert!

---

## 📊 DETAILLIERTE ANALYSE

### 1️⃣ **Was funktioniert in der UI?**

| Funktion | UI vorhanden? | Datei | Zeilen |
|----------|--------------|-------|--------|
| ✅ Termine ansehen | Ja | AppointmentResource.php | 269-591 |
| ✅ Termine erstellen | Ja | AppointmentResource.php | 60-266 |
| ✅ Termine bearbeiten | Ja | EditAppointment.php | 1-19 |
| ✅ Termine löschen | Ja | AppointmentResource.php | 578 |
| ✅ Status ändern | Ja | AppointmentResource.php | 461-502 |
| ✅ Termine verschieben | Ja | AppointmentResource.php | 504-528 |
| ✅ Mitarbeiter zuordnen | Ja | AppointmentResource.php | 150-155 |
| ✅ Service ändern | Ja | AppointmentResource.php | 98-115 |
| ✅ Bulk Actions | Ja | AppointmentResource.php | 549-583 |

**Alle UI-Funktionen sind implementiert und funktionieren technisch!**

---

### 2️⃣ **Cal.com Webhook-Empfang (funktioniert)**

**CalcomWebhookController.php** empfängt erfolgreich:

| Webhook Event | Funktioniert? | Handler | Zeilen |
|--------------|--------------|---------|--------|
| ✅ BOOKING.CREATED | Ja | handleBookingCreated() | 199-333 |
| ✅ BOOKING.UPDATED | Ja | handleBookingUpdated() | 338-399 |
| ✅ BOOKING.RESCHEDULED | Ja | handleBookingUpdated() | 338-399 |
| ✅ BOOKING.CANCELLED | Ja | handleBookingCancelled() | 404-464 |

**Beispiel:** Kunde bucht auf Cal.com → Webhook → DB wird aktualisiert ✅

---

### 3️⃣ **Cal.com API Client (vorhanden aber ungenutzt!)**

**CalcomV2Client.php** hat bereits alle nötigen Funktionen:

```php
// Line 95-101
public function cancelBooking(int $bookingId, string $reason = ''): Response
{
    return Http::withHeaders($this->getHeaders())
        ->delete("{$this->baseUrl}/bookings/{$bookingId}", [
            'cancellationReason' => $reason
        ]);
}

// Line 106-115
public function rescheduleBooking(int $bookingId, array $data): Response
{
    return Http::withHeaders($this->getHeaders())
        ->patch("{$this->baseUrl}/bookings/{$bookingId}", [
            'start' => $data['start'],
            'end' => $data['end'],
            'timeZone' => $data['timeZone'],
            'reason' => $data['reason'] ?? 'Customer requested reschedule'
        ]);
}
```

**Status:** ✅ API-Funktionen existieren, werden aber NICHT verwendet!

---

### 4️⃣ **Das Problem: Fehlende Integration**

**Filament UI verwendet CalcomV2Client NICHT:**

```bash
# Suche nach CalcomV2Client in Filament Dateien
grep -r "CalcomV2Client\|cancelBooking\|rescheduleBooking" app/Filament/
# Ergebnis: No files found ❌
```

**Was passiert aktuell:**

1. User öffnet Termin in UI
2. User ändert Datum/Zeit/Status
3. Filament speichert Änderung in Datenbank ✅
4. **Cal.com wird NICHT informiert ❌**
5. Cal.com zeigt weiterhin alten Termin ❌
6. **Daten driften auseinander! 🔴**

---

## 🔴 KRITISCHE SZENARIEN

### Szenario 1: Termin in UI storniert
```
1. User: Storniert Termin in Admin UI
2. Datenbank: status = 'cancelled' ✅
3. Cal.com: Termin ist noch gebucht ❌
4. Problem: Slot bleibt blockiert auf Cal.com!
```

### Szenario 2: Termin in UI verschoben
```
1. User: Verschiebt Termin von 10:00 auf 14:00
2. Datenbank: starts_at = '14:00' ✅
3. Cal.com: Zeigt weiterhin 10:00 ❌
4. Problem: Kunde bekommt falsche Erinnerung!
```

### Szenario 3: Mitarbeiter geändert
```
1. User: Ändert Mitarbeiter von Staff A zu Staff B
2. Datenbank: staff_id = B ✅
3. Cal.com: Host ist weiterhin A ❌
4. Problem: Falscher Mitarbeiter im Kalender!
```

### Szenario 4: Service geändert
```
1. User: Ändert Service (z.B. Haarschnitt → Färben)
2. Datenbank: service_id = neu ✅
3. Cal.com: Event Type ist weiterhin alt ❌
4. Problem: Falsche Dauer und Preis!
```

---

## 🔍 TECHNISCHE DETAILS

### Aktueller Datenfluss

```
┌─────────────┐                  ┌──────────────┐
│  Cal.com    │ ─── Webhook ───> │  Datenbank   │
│  Bookings   │      ✅          │  Appointments│
└─────────────┘                  └──────────────┘
                                        │
                                        │ Edit/Update
                                        ▼
                                  ┌──────────────┐
                                  │  Filament UI │
                                  └──────────────┘

Problem: Keine Rück-Synchronisation! ❌
```

### Sollte sein:

```
┌─────────────┐    Webhook      ┌──────────────┐
│  Cal.com    │ <───────────> │  Datenbank   │
│  Bookings   │    API Calls    │  Appointments│
└─────────────┘      ✅✅       └──────────────┘
                                        │
                                        │ Edit/Update
                                        ▼
                                  ┌──────────────┐
                                  │  Filament UI │
                                  └──────────────┘
```

---

## 📝 CODE-ANALYSE

### EditAppointment.php (aktuell)

```php
<?php

namespace App\Filament\Resources\AppointmentResource\Pages;

use App\Filament\Resources\AppointmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAppointment extends EditRecord
{
    protected static string $resource = AppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
```

**Problem:** Standard Filament Edit Page - keine Cal.com Integration!

### Quick Actions (AppointmentResource.php)

```php
// Line 488-502: Cancel Action
Tables\Actions\Action::make('cancel')
    ->label('Stornieren')
    ->icon('heroicon-m-x-circle')
    ->color('danger')
    ->visible(fn ($record) => !in_array($record->status, ['completed', 'cancelled']))
    ->requiresConfirmation()
    ->action(function ($record) {
        $record->update(['status' => 'cancelled']);  // ← NUR DB UPDATE!
        // ❌ FEHLT: Cal.com API Call!
        Notification::make()
            ->title('Termin storniert')
            ->warning()
            ->send();
    }),
```

**Problem:** Nur DB-Update, keine Cal.com API Integration!

### Reschedule Action (AppointmentResource.php)

```php
// Line 504-528: Reschedule Action
Tables\Actions\Action::make('reschedule')
    ->label('Verschieben')
    ->icon('heroicon-m-calendar')
    ->color('warning')
    ->form([
        Forms\Components\DateTimePicker::make('starts_at')
            ->label('Neuer Starttermin')
            ->required()
            ->native(false)
            ->seconds(false)
            ->minutesStep(15)
            ->minDate(now()),
    ])
    ->action(function ($record, array $data) {
        $duration = Carbon::parse($record->starts_at)->diffInMinutes($record->ends_at);
        $record->update([
            'starts_at' => $data['starts_at'],
            'ends_at' => Carbon::parse($data['starts_at'])->addMinutes($duration),
        ]);  // ← NUR DB UPDATE!
        // ❌ FEHLT: Cal.com API Call!
        Notification::make()
            ->title('Termin verschoben')
            ->success()
            ->send();
    }),
```

**Problem:** Nur DB-Update, keine Cal.com API Integration!

---

## ✅ WAS FUNKTIONIERT

1. ✅ **Webhooks von Cal.com empfangen**
   - BOOKING.CREATED → DB
   - BOOKING.UPDATED → DB
   - BOOKING.CANCELLED → DB

2. ✅ **UI-Funktionen vorhanden**
   - Alle Edit/Update/Delete Funktionen existieren
   - Forms sind benutzerfreundlich
   - Bulk Actions funktionieren

3. ✅ **Cal.com API Client existiert**
   - cancelBooking() vorhanden
   - rescheduleBooking() vorhanden
   - Alle nötigen Endpunkte implementiert

---

## ❌ WAS NICHT FUNKTIONIERT

1. ❌ **UI → Cal.com Synchronisation**
   - Stornierung in UI → Cal.com weiß nichts davon
   - Verschieben in UI → Cal.com weiß nichts davon
   - Mitarbeiter ändern → Cal.com weiß nichts davon
   - Service ändern → Cal.com weiß nichts davon

2. ❌ **Data Drift Prevention**
   - Keine Validierung ob Termin in Cal.com existiert
   - Keine Konflikterkennung bei Änderungen
   - Keine Synchronisations-Logs

3. ❌ **Error Handling**
   - Keine Fehlerbehandlung wenn Cal.com offline ist
   - Keine Retry-Logik für fehlgeschlagene Updates
   - Keine Benachrichtigung bei Sync-Fehlern

---

## 🚨 RISIKEN

### 1. Data Inconsistency (Hoch)
- Datenbank und Cal.com zeigen unterschiedliche Termine
- Kunde bekommt falsche Benachrichtigungen
- Mitarbeiter erscheinen zu falschen Zeiten

### 2. Blocked Slots (Mittel)
- Stornierte Termine blockieren Cal.com Slots
- Neue Buchungen werden verhindert
- Umsatzverlust durch nicht verfügbare Zeiten

### 3. Customer Experience (Hoch)
- Kunde bucht auf Cal.com → Termin ist nicht verfügbar
- Kunde bekommt Erinnerung für falschen Termin
- Verwirrung und Vertrauensverlust

### 4. Staff Management (Mittel)
- Mitarbeiter-Zuordnungen stimmen nicht
- Doppelbuchungen möglich
- Chaotische Terminplanung

---

## 💡 EMPFEHLUNGEN

### Option 1: Hooks in Filament Actions (Schnell, Empfohlen)

**Komplexität:** 🟢 Niedrig
**Aufwand:** ~2-4 Stunden
**Risiko:** Niedrig

```php
// EditAppointment.php
protected function afterSave(): void
{
    // Sync to Cal.com after save
    if ($this->record->calcom_v2_booking_id) {
        $this->syncToCalcom();
    }
}

private function syncToCalcom(): void
{
    $calcomClient = app(CalcomV2Client::class);

    try {
        // Detect what changed
        if ($this->record->wasChanged('starts_at') || $this->record->wasChanged('ends_at')) {
            // Reschedule
            $calcomClient->rescheduleBooking(
                $this->record->calcom_v2_booking_id,
                [
                    'start' => $this->record->starts_at->toIso8601String(),
                    'end' => $this->record->ends_at->toIso8601String(),
                    'timeZone' => 'Europe/Berlin',
                ]
            );
        }

        if ($this->record->wasChanged('status') && $this->record->status === 'cancelled') {
            // Cancel
            $calcomClient->cancelBooking(
                $this->record->calcom_v2_booking_id,
                'Cancelled by admin'
            );
        }
    } catch (\Exception $e) {
        // Log and notify
        Log::error('Cal.com sync failed', [
            'appointment_id' => $this->record->id,
            'error' => $e->getMessage()
        ]);

        Notification::make()
            ->title('Cal.com Sync Fehler')
            ->body('Änderung wurde lokal gespeichert, aber Cal.com konnte nicht aktualisiert werden.')
            ->warning()
            ->send();
    }
}
```

### Option 2: Event-Based Synchronisation (Besser, Wartbar)

**Komplexität:** 🟡 Mittel
**Aufwand:** ~4-8 Stunden
**Risiko:** Niedrig

```php
// 1. Event erstellen
namespace App\Events\Appointments;

class AppointmentUpdated
{
    public function __construct(
        public Appointment $appointment,
        public array $changes
    ) {}
}

// 2. Event dispatchen
// In Appointment Model oder Filament Action
event(new AppointmentUpdated($appointment, $appointment->getChanges()));

// 3. Listener erstellen
namespace App\Listeners\Appointments;

class SyncToCalcom
{
    public function handle(AppointmentUpdated $event): void
    {
        if (!$event->appointment->calcom_v2_booking_id) {
            return; // Nicht von Cal.com → keine Sync nötig
        }

        $calcomClient = app(CalcomV2Client::class);

        // Reschedule
        if (array_key_exists('starts_at', $event->changes) ||
            array_key_exists('ends_at', $event->changes)) {
            $calcomClient->rescheduleBooking(...);
        }

        // Cancel
        if (array_key_exists('status', $event->changes) &&
            $event->appointment->status === 'cancelled') {
            $calcomClient->cancelBooking(...);
        }
    }
}
```

### Option 3: Queue-Based mit Retry (Robust, Production-Ready)

**Komplexität:** 🔴 Hoch
**Aufwand:** ~8-16 Stunden
**Risiko:** Niedrig

```php
// 1. Job erstellen
namespace App\Jobs\Calcom;

class SyncAppointmentToCalcom implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $backoff = [60, 300, 900]; // 1min, 5min, 15min

    public function __construct(
        public Appointment $appointment,
        public string $action, // 'update', 'cancel', 'reschedule'
        public array $data = []
    ) {}

    public function handle(CalcomV2Client $calcomClient): void
    {
        if (!$this->appointment->calcom_v2_booking_id) {
            return;
        }

        switch ($this->action) {
            case 'reschedule':
                $response = $calcomClient->rescheduleBooking(
                    $this->appointment->calcom_v2_booking_id,
                    $this->data
                );
                break;

            case 'cancel':
                $response = $calcomClient->cancelBooking(
                    $this->appointment->calcom_v2_booking_id,
                    $this->data['reason'] ?? 'Cancelled by admin'
                );
                break;
        }

        if (!$response->successful()) {
            throw new \Exception('Cal.com API call failed: ' . $response->body());
        }

        // Log success
        $this->appointment->update([
            'last_calcom_sync' => now(),
            'sync_status' => 'synced'
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        // Mark as failed after all retries
        $this->appointment->update([
            'sync_status' => 'failed',
            'sync_error' => $exception->getMessage()
        ]);

        // Notify admins
        Notification::make()
            ->title('Cal.com Sync dauerhaft fehlgeschlagen')
            ->body("Termin #{$this->appointment->id} konnte nicht synchronisiert werden.")
            ->danger()
            ->sendToDatabase(\App\Models\User::admins());
    }
}

// 2. Dispatch in Filament Action
protected function afterSave(): void
{
    if ($this->record->wasChanged('starts_at') || $this->record->wasChanged('ends_at')) {
        SyncAppointmentToCalcom::dispatch(
            $this->record,
            'reschedule',
            [
                'start' => $this->record->starts_at->toIso8601String(),
                'end' => $this->record->ends_at->toIso8601String(),
                'timeZone' => 'Europe/Berlin',
            ]
        );
    }

    if ($this->record->wasChanged('status') && $this->record->status === 'cancelled') {
        SyncAppointmentToCalcom::dispatch(
            $this->record,
            'cancel',
            ['reason' => 'Cancelled by admin']
        );
    }
}
```

---

## 🎯 EMPFOHLENER ANSATZ

**Start mit Option 1 (Hooks), dann erweitern zu Option 3 (Queue):**

### Phase 1: Quick Win (Tag 1)
- ✅ Hooks in EditAppointment.php
- ✅ Cancel Action in AppointmentResource.php
- ✅ Reschedule Action in AppointmentResource.php
- ✅ Basis Error Handling

### Phase 2: Robustness (Tag 2-3)
- ✅ Queue-based synchronisation
- ✅ Retry-Logik
- ✅ Failed job notifications
- ✅ Sync status tracking

### Phase 3: Monitoring (Tag 4)
- ✅ Drift detection
- ✅ Sync health dashboard
- ✅ Automated conflict resolution
- ✅ Admin notifications

---

## 📊 PRIORISIERUNG

| Feature | Priorität | Aufwand | Impact |
|---------|-----------|---------|--------|
| Cancel Sync | 🔴 KRITISCH | 2h | HOCH |
| Reschedule Sync | 🔴 KRITISCH | 2h | HOCH |
| Error Handling | 🟡 WICHTIG | 2h | MITTEL |
| Queue + Retry | 🟡 WICHTIG | 4h | HOCH |
| Staff/Service Sync | 🟢 NICE-TO-HAVE | 4h | NIEDRIG |
| Drift Detection | 🟢 NICE-TO-HAVE | 6h | MITTEL |

---

## 🔗 VERWANDTE DATEIEN

### Code
- `/app/Filament/Resources/AppointmentResource.php` - UI Actions
- `/app/Filament/Resources/AppointmentResource/Pages/EditAppointment.php` - Edit Page
- `/app/Http/Controllers/CalcomWebhookController.php` - Webhook Handler
- `/app/Services/CalcomV2Client.php` - Cal.com API Client
- `/app/Models/Appointment.php` - Appointment Model

### Documentation
- `claudedocs/CALCOM_CACHE_RCA_2025-10-11.md` - Cache-Probleme
- `claudedocs/CALCOM_RESCHEDULE_SYNC_FAILURE_RCA_2025-10-11.md` - Reschedule Sync Analyse

---

## ✅ NEXT STEPS

1. **Sofortmaßnahme:**
   - User informieren über aktuellen Status
   - Warnung: Keine UI-Änderungen bis Sync implementiert ist

2. **Kurzfristig (diese Woche):**
   - Option 1 implementieren (Hooks)
   - Cancel + Reschedule Sync

3. **Mittelfristig (nächste Woche):**
   - Option 3 implementieren (Queue)
   - Error Handling + Retry

4. **Langfristig (nächster Sprint):**
   - Monitoring Dashboard
   - Drift Detection
   - Automated Conflict Resolution

---

**Erstellt:** 2025-10-13 16:15 UTC
**Analysiert von:** Claude Code
**Nächste Review:** Nach Implementierung der Sync-Funktionen
