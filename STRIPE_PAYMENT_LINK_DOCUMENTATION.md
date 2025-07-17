# Stripe Payment Links für AskProAI

## Übersicht

Das System unterstützt zwei Arten von Stripe-basierten Aufladungen:

1. **Öffentliche Topup Links** (Bereits implementiert und funktionsfähig)
2. **Stripe Payment Links API** (Neu implementiert, benötigt weitere Tests)

## 1. Öffentliche Topup Links (EMPFOHLEN)

### Funktionsweise
- Jede Company hat einen permanenten öffentlichen Link
- Kunden müssen sich nicht anmelden
- Verwendet Stripe Checkout Sessions
- Automatische Guthabenbuchung nach erfolgreicher Zahlung

### Link-Format
```
https://api.askproai.de/topup/{company_id}
https://api.askproai.de/topup/{company_id}?amount=100
```

### Beispiel für Krückeberg GmbH
- **Standard Link**: https://api.askproai.de/topup/1
- **Mit 100€ voreingestellt**: https://api.askproai.de/topup/1?amount=100
- **Mit 250€ voreingestellt**: https://api.askproai.de/topup/1?amount=250

### Features
- ✅ Keine Authentifizierung erforderlich
- ✅ Kunde gibt Name und E-Mail ein
- ✅ Automatische Rechnungserstellung
- ✅ E-Mail-Bestätigung
- ✅ Bonus-Berechnung (falls konfiguriert)
- ✅ Unterstützt Kreditkarte und SEPA

### QR-Code generieren
```
https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=https://api.askproai.de/topup/1
```

## 2. Neu implementierte Features

### processTopup() Methode
Die fehlende `processTopup()` Methode wurde im `StripeTopupService` implementiert:
- Verarbeitet direkte Zahlungen mit gespeicherten Payment Methods
- Unterstützt 3D Secure Authentifizierung
- Automatische Guthabenbuchung

### createPaymentLink() Methode
Neue Methode für Stripe Payment Links API:
- Erstellt dauerhafte, wiederverwendbare Links
- Unterstützt feste und variable Beträge
- Speichert Link-Referenz in Company-Metadata

### API Endpoints
```
POST /api/stripe/payment-link/create
GET  /api/stripe/payment-link/{companyId}
GET  /api/stripe/payment-link/{companyId}/qr-code
```

### Admin Interface
Neue Filament-Seite unter `/admin/stripe-payment-links`:
- Übersicht aller Companies mit Payment Links
- Link-Erstellung und Verwaltung
- QR-Code Generation

## Test-Kreditkarten

Für Tests im Stripe Test-Modus:
- **Erfolg**: 4242 4242 4242 4242
- **3D Secure**: 4000 0025 0000 3155
- **Ablehnung**: 4000 0000 0000 0002

## Problemlösung

### "Failed to process topup" Fehler
Ursache: Die `processTopup()` Methode fehlte im `StripeTopupService`.
Lösung: Methode wurde implementiert.

### Payment Link API Fehler
Die Stripe Payment Links API erfordert:
1. Erst einen Price/Product zu erstellen
2. Dann den Payment Link mit der Price ID

## Empfehlung

Für die Krückeberg GmbH empfehlen wir die Nutzung des **öffentlichen Topup Links**:
- Bereits getestet und funktionsfähig
- Einfacher zu verwenden
- Keine zusätzliche Konfiguration erforderlich

Link zum Teilen: **https://api.askproai.de/topup/1**

## Nächste Schritte

1. Testen Sie den öffentlichen Link im Browser
2. Optional: Erstellen Sie QR-Codes für physische Standorte
3. Integrieren Sie den Link in E-Mail-Signaturen oder Websites
4. Überwachen Sie eingehende Zahlungen im Admin-Panel