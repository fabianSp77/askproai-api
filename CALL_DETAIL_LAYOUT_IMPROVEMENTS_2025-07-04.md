# Call Detail Layout Improvements - 2025-07-04

## Umgesetzte Änderungen

### 1. ✅ Layout-Entzerrung im Header
**Problem**: Metriken waren zu eng zusammengequetscht
**Lösung**: 
- Grid von 4 Spalten auf 2x2 reduziert für mehr Platz
- Bessere Verteilung der Elemente
- Mehr Whitespace zwischen den Metriken

### 2. ✅ Kundenname vollständig anzeigen
**Problem**: "Hans Schuster von der Schuster GmbH Bad" wurde abgeschnitten
**Lösung**:
- `Str::limit()` entfernt - zeigt jetzt vollständigen Namen
- Interest/Anrufgrund ebenfalls ohne Limit
- Schriftgröße angepasst für bessere Lesbarkeit

### 3. ✅ Kostenberechnung komplett überarbeitet
**Neue Features**:
- **Aktuelle Wechselkurse**: ExchangeRateService mit 24h Cache
- **Gesamtkosten prominent**: Große rote Zahl zeigt EUR-Betrag
- **Detailliertes Mouseover**: 
  - Retell-Kosten in USD und EUR
  - Aktueller Wechselkurs
  - Produktaufschlüsselung (ElevenLabs TTS, Gemini, etc.)
  - Berechnung: Minuten × Rate = Umsatz
  - Marge in Prozent
- **Übersichtliche Darstellung**: Umsatz und Gewinn darunter

### 4. ✅ Kundeninformationen optimiert
**Problem**: "Kunde anlegen" Button bei Bestandskunden
**Lösung**: Button wird nur angezeigt wenn `!$record->customer`

## Technische Implementierung

### ExchangeRateService
```php
// Neuer Service für Wechselkurse
app/Services/ExchangeRateService.php
- getUsdToEur(): Aktueller Kurs mit 24h Cache
- convertCentsToEur(): Direkte Konvertierung
- formatProductCosts(): Produktnamen übersetzt
```

### Kosten-Tooltip Details
```
Retell Kosten: $0.1458 (0.1339€)
Wechselkurs: 1 USD = 0.9184 EUR

Kostenaufschlüsselung:
elevenlabs_tts: $0.1260
gemini_2_0_flash: $0.0108
background_voice_cancellation: $0.0090

Berechnung:
1.80 Min × 0.10€/Min = 0.18€ Umsatz
Marge: 25.5%
```

## API für Wechselkurse
- Primär: exchangerate-api.com (kostenlos, kein Key)
- Fallback: 0.92 EUR/USD wenn API nicht erreichbar
- Cache: 24 Stunden

## Testing
1. Cache geleert mit `php artisan optimize:clear`
2. Browser Hard-Refresh (Ctrl+F5)
3. Mouseover auf Gesamtkosten testen

## Offene Punkte
- [ ] Wechselkurs-API Key für Production (für höhere Limits)
- [ ] Historische Wechselkurse für alte Calls
- [ ] Währungsauswahl pro Company (nicht nur EUR)