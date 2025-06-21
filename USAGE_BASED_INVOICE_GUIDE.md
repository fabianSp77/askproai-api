# Anleitung: Nutzungsbasierte Rechnungserstellung

## Übersicht
Die nutzungsbasierte Rechnungserstellung ermöglicht es, automatisch Rechnungen basierend auf dem hinterlegten Preismodell und den tatsächlichen Anrufdaten zu erstellen.

## Schritt-für-Schritt Anleitung

### 1. Neue Rechnung erstellen
- Gehen Sie zu **Abrechnung → Rechnungen**
- Klicken Sie auf **Neue Rechnung**

### 2. Modus auswählen
- Wählen Sie **"Aus Nutzungsdaten generieren"** statt "Manuell erstellen"

### 3. Zeitraum konfigurieren
- **Unternehmen**: Wählen Sie das Unternehmen (z.B. AskProAI)
- **Von**: Startdatum des Abrechnungszeitraums (z.B. 01.03.2025)
- **Bis**: Enddatum des Abrechnungszeitraums (z.B. 26.06.2025)

### 4. Daten laden
- Klicken Sie auf **"Nutzungsdaten laden"**
- Die Vorschau zeigt:
  - Abrechnungszeitraum
  - Aktives Preismodell
  - Nutzungsstatistik
  - Kostenvorschau

### 5. Rechnung erstellen
- Überprüfen Sie die Vorschau
- Klicken Sie auf **"Erstellen"**
- Die Rechnung wird mit allen Positionen automatisch generiert

## Berechnungslogik

### Grundgebühr
- Wird anteilig für den gewählten Zeitraum berechnet
- Beispiel: Bei €49/Monat und 15 Tagen = €24,50

### Gesprächsminuten
- Alle Anrufe mit Dauer > 0 werden berücksichtigt
- Inklusiv-Minuten werden abgezogen
- Überschreitung wird mit dem Minutenpreis berechnet

### Einrichtungsgebühr
- Wird nur einmalig berechnet (beim ersten Mal)
- System prüft automatisch, ob bereits berechnet wurde

## Voraussetzungen

### Preismodell
- Das Unternehmen muss ein aktives Preismodell haben
- Das Preismodell muss für den gewählten Zeitraum gültig sein
- Konfigurierbare Werte:
  - Monatliche Grundgebühr
  - Inklusiv-Minuten
  - Preis pro Minute
  - Einrichtungsgebühr (optional)

### Anrufdaten
- Anrufe müssen im System erfasst sein
- Benötigte Felder: `duration_sec` oder `duration_minutes`
- Anrufe werden einbezogen wenn:
  - `call_successful = true` ODER
  - `duration_sec > 0` ODER
  - `duration_minutes > 0`

## Fehlerbehebung

### "Kein aktives Preismodell gefunden"
- Überprüfen Sie unter **Einstellungen → Preismodelle**
- Stellen Sie sicher, dass:
  - `is_active = true`
  - `valid_from` <= Startdatum
  - `valid_until` >= Enddatum (oder NULL)

### Keine Anrufe werden angezeigt
- Prüfen Sie, ob Anrufe im gewählten Zeitraum existieren
- Stellen Sie sicher, dass die Anrufe eine Dauer haben

### Seite lädt neu / Daten gehen verloren
- Browser-Cache leeren
- In einem neuen Tab/Fenster versuchen
- Sicherstellen, dass alle Pflichtfelder ausgefüllt sind

## Technische Details

### Datenquellen
- **Preismodell**: `company_pricing` Tabelle
- **Anrufdaten**: `calls` Tabelle
- **Steuerberechnung**: Automatisch basierend auf Unternehmenseinstellungen

### API Endpunkte (für Entwickler)
```php
// Nutzungsstatistik abrufen
$service = new EnhancedStripeInvoiceService();
$stats = $service->getUsageStatistics($company, $periodStart, $periodEnd);

// Rechnung erstellen
$invoice = $service->createUsageBasedInvoice($company, $periodStart, $periodEnd);
```

## Beispielrechnung

Für AskProAI vom 01.03.2025 - 26.06.2025:
- **Zeitraum**: 117 Tage (3,9 Monate)
- **Grundgebühr**: €49 × 3,9 = €191,10
- **Anrufe**: 98 Anrufe, 221,85 Minuten
- **Gesprächsgebühren**: 221,85 × €0,32 = €70,99
- **Einrichtungsgebühr**: €199,00 (einmalig)
- **Zwischensumme**: €461,09
- **MwSt (19%)**: €87,61
- **Gesamtbetrag**: €548,70