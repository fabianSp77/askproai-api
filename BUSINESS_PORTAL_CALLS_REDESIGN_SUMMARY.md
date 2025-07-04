# Business Portal Calls Redesign - Implementierung

**Datum**: 2025-07-04
**Status**: ✅ Vollständig implementiert

## Übersicht

Die Call-Seiten im Business Portal wurden komplett überarbeitet mit Fokus auf professionelles Design, verbesserte UX und erweiterte Funktionalität.

## Implementierte Features

### 1. Call-Übersichtsseite (`/business/calls`)

#### Neue Features:
- **Zeit seit Anruf**: Zeigt wie lange der Anruf her ist (z.B. "2h 15m her" mit Tooltip für genaues Datum)
- **Erweiterte Filter**:
  - Nach Dringlichkeit (Hoch, Mittel, Niedrig)
  - Nach Alter (Letzte Stunde, 4h, 24h, 48h, 7 Tage)
  - Nach Anrufdauer (< 1 Min, 1-5 Min, 5-10 Min, > 10 Min)
  - Bestehende Filter (Status, Datum, Suche)
- **Statistik-Karten**: 
  - Anrufe heute
  - Neue Anrufe
  - Aktion erforderlich
  - Kosten heute (nur für Management/Admin)
- **Email-Aktionen** pro Anruf:
  - Daten kopieren
  - In Email-Programm öffnen
  - Über System versenden (geplant)
- **Bulk-Aktionen**:
  - Mehrere Anrufe mit Checkboxen auswählen
  - Bulk Export als CSV oder PDF
  - Status von mehreren ändern (geplant)
- **Tooltips** für lange Texte
- **Kostenberechnung** (nur für Benutzer mit `billing.view` Berechtigung)

#### Design:
- Moderne Statistik-Karten
- Farbcodierte Zeitanzeige (grün = frisch, rot = alt)
- Verbesserte Tabellenstruktur
- Responsive Design

### 2. Call-Detailseite (`/business/calls/{id}`)

#### Neues Layout:
- **Header-Bereich** mit allen wichtigen Infos auf einen Blick:
  - Kundenname groß
  - Zeit seit Anruf
  - Anrufdauer
  - Status als Badge
  - Quick Info Bar mit Dringlichkeit, Firma, Kosten, Bearbeiter

- **Hauptbereich (Links)**:
  - Kundenanliegen (prominent)
  - Erfasste Kundendaten (strukturiert)
  - Kostenberechnung (nur für Management)
  - Zusammenfassung
  - Gesprächsverlauf

- **Sidebar (Rechts)**:
  - Quick Actions (Status, Zuweisung)
  - Interne Notizen
  - Anrufhistorie

#### Features:
- Kostenberechnung mit Details (Dauer, Minutenpreis, Gesamtkosten)
- Bessere Darstellung der gesammelten Kundendaten
- Modals für Notizen und Rückruf-Planung
- Farbcodierte Badges für Dringlichkeit

### 3. Technische Implementierung

#### Neue Dateien:
- `/app/Helpers/TimeHelper.php` - Zeit-Formatierung
- `/resources/views/components/call-email-actions.blade.php` - Email-Aktionen Component
- `/resources/views/portal/calls/index-redesigned.blade.php` - Neue Übersichtsseite
- `/resources/views/portal/calls/show-redesigned.blade.php` - Neue Detailseite

#### Controller-Erweiterungen:
- Erweiterte Filter-Logik
- Kostenberechnung in Statistiken
- Bulk Export Funktionalität
- Rechteverwaltung für Kosten

#### Route-Erweiterungen:
```php
Route::post('/export/bulk', [CallController::class, 'exportBulk'])
    ->middleware('portal.permission:calls.export')
    ->name('export.bulk');
```

## Rechteverwaltung

### Kostenansicht:
- Kosten werden nur angezeigt für:
  - Benutzer mit `billing.view` Permission
  - Admin-Viewing Sessions
- Normale Mitarbeiter sehen keine Kosten

### Export:
- Erfordert `calls.export` Permission
- CSV Export vollständig implementiert
- PDF Export vorbereitet (Implementation folgt)

## Preisberechnung

Die Kosten werden berechnet basierend auf:
1. `CallCharge` (wenn bereits berechnet)
2. `CompanyPricing` (erweiterte Preismodelle)
3. `BillingRate` (Standard-Minutenpreise)

Für Krückeberg: Minutenpreis mit sekundengenauer Abrechnung

## Nächste Schritte

### Noch zu implementieren:
1. PDF Export Funktionalität
2. Bulk Status-Änderung
3. System-Email-Versand
4. Erweiterte Bulk-Aktionen

### Empfohlene Verbesserungen:
1. Real-time Updates mit Pusher
2. Erweiterte Suchfunktion
3. Favoriten/Merkliste
4. Export-Templates

## Testing

Zum Testen der neuen Seiten:
1. Als Portal-User einloggen
2. `/business/calls` für Übersicht
3. Auf einen Anruf klicken für Detailansicht
4. Filter und Bulk-Aktionen testen
5. Mit Admin-User Kosten prüfen

## Hinweise

- Versicherungsinformationen wurden entfernt (nicht relevant)
- Terminwunsch-Features sind vorbereitet für spätere Nutzung
- Email-Funktionen nutzen Browser-APIs (mailto: und clipboard)
- Tooltips funktionieren mit Alpine.js