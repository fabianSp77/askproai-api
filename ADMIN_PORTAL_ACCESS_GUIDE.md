# Admin-Zugriff auf B2B Kundenportal

## 🎯 Übersicht

Als Admin können Sie jetzt:
1. Das B2B Portal aus Kundensicht sehen
2. Für Kunden agieren (Guthaben aufladen, Transaktionen einsehen)
3. Guthaben manuell anpassen
4. Alle Prepaid-Kunden zentral verwalten

## 📍 Wo finde ich alles?

### 1. **Prepaid Guthaben Übersicht**
- **Pfad**: Admin Panel → Billing & Portal → "Prepaid Guthaben"
- **URL**: `/admin/prepaid-balances`
- **Features**:
  - Übersicht aller Firmen mit Prepaid-Guthaben
  - Aktuelle Guthaben und Reservierungen
  - Filter für niedriges/kein Guthaben
  - Schnellzugriff auf Kundenportal

### 2. **B2B Portal Admin**
- **Pfad**: Admin Panel → Billing & Portal → "B2B Portal Admin"
- **URL**: `/admin/business-portal-admin`
- **Features**:
  - Detaillierte Firmenansicht
  - Letzte Transaktionen
  - Monatliche Nutzungsstatistiken
  - Guthaben-Anpassung
  - Portal als Kunde öffnen

### 3. **Kundenportal Zugriff**
- **Button**: "Portal öffnen" oder "Kundenportal öffnen"
- **Was passiert**:
  - Sie werden automatisch als Admin-User eingeloggt
  - Gelber Banner zeigt Admin-Zugriff an
  - Volle Berechtigung auf alle Funktionen
  - "Admin-Zugriff beenden" Button zum Zurückkehren

## 🔧 Funktionen im Detail

### Guthaben manuell anpassen
1. Firma auswählen
2. "Guthaben anpassen" klicken
3. Typ wählen (Aufladung/Abzug)
4. Betrag eingeben
5. Beschreibung hinzufügen

### Als Kunde agieren
Im Kundenportal können Sie:
- ✅ Guthaben aufladen (Stripe Checkout)
- ✅ Transaktionen einsehen
- ✅ Nutzungsstatistiken anzeigen
- ✅ CSV-Exporte erstellen
- ✅ Alle Kundenfunktionen testen

### Monitoring
- **Niedriges Guthaben**: Automatische Anzeige bei < 20% oder benutzerdefinierter Schwelle
- **Letzte Aufladung**: Zeitpunkt der letzten Zahlung
- **Monatliche Nutzung**: Anrufe, Minuten, Kosten

## 🚨 Sicherheit

- Admin-Zugriff wird geloggt
- Temporäre Tokens (15 Min. gültig)
- Eindeutige Admin-Portal-User pro Firma
- Keine echten Kundendaten werden verändert

## 💡 Tipps

1. **Test-Zahlungen**: Nutzen Sie Stripe Test-Modus mit Karte `4242 4242 4242 4242`
2. **Guthaben-Warnung testen**: Setzen Sie Guthaben auf < 20% der Warnschwelle
3. **Export testen**: CSV-Export zeigt alle Transaktionen mit deutscher Formatierung

## 🎬 Schnellstart

1. Gehen Sie zu: `/admin/prepaid-balances`
2. Wählen Sie eine Firma
3. Klicken Sie "Portal öffnen"
4. Sie sind jetzt im Kundenportal mit Admin-Rechten
5. Testen Sie alle Funktionen
6. Klicken Sie "Admin-Zugriff beenden" zum Zurückkehren

## 📊 Dashboard-URLs

- **Admin Übersicht**: `/admin/business-portal-admin`
- **Prepaid Guthaben**: `/admin/prepaid-balances`
- **Kundenportal (als Kunde)**: `/business/billing`

## ⚙️ Konfiguration

Für neue Kunden mit Prepaid:
```php
// 1. Billing Rate festlegen
BillingRate::create([
    'company_id' => $company->id,
    'rate_per_minute' => 0.42,
    'billing_increment' => 1
]);

// 2. Prepaid Balance initialisieren
PrepaidBalance::create([
    'company_id' => $company->id,
    'balance' => 0.00,
    'low_balance_threshold' => 20.00
]);
```