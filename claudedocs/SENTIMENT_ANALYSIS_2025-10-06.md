# 🎭 Sentiment-Analyse: Datenqualität & Herkunft

**Datum**: 2025-10-06 11:00 CEST
**Anfrage**: Überprüfung der Stimmung-Spalte in der Anrufübersicht
**Status**: ✅ ANALYSIERT

---

## 📊 Zusammenfassung

**Ergebnis**: Die Sentiment-Daten werden **korrekt** von Retell.ai empfangen und gespeichert, aber:
- ⚠️ **98% aller neuen Calls = "Neutral"** (seit Oktober 2025)
- ✅ Ältere Calls zeigen Variationen (Positive, Neutral, Negative)
- ✅ Datenquelle und Speicherung funktionieren technisch einwandfrei
- ❌ **Aussagekraft fraglich** - zu wenig Varianz in aktuellen Daten

---

## 🔍 Datenherkunft

### Quelle: Retell.ai Call Analysis
```
Webhook Event: call_analyzed
└─ Payload: call_analysis.user_sentiment
   └─ Werte: "Positive", "Neutral", "Negative"
```

### Datenfluss
```
1. Retell.ai analysiert Anruf (nach call_ended)
   └─ AI-gestützte Sentiment-Analyse des Gesprächs

2. Webhook call_analyzed Event
   └─ RetellWebhookController.php:248

3. RetellApiClient synchronisiert Daten
   └─ RetellApiClient.php:283
   └─ Speichert: $call->sentiment = $callData['call_analysis']['user_sentiment']

4. Anzeige in Filament
   └─ CallResource.php:531-547
   └─ Badge-Formatierung mit Emojis
```

---

## 📈 Aktuelle Datenqualität

### Verteilung (Letzte 30 Calls mit Sentiment)
```
Neutral:  30 calls (100%)
Positive:  0 calls (0%)
Negative:  0 calls (0%)
```

### Historische Verteilung (Calls < ID 600, ca. Juli-Sept 2025)
```
Neutral:   35 calls (70%)
neutral:   14 calls (28%)  ← Inkonsistente Großschreibung!
Positive:   1 call  (2%)
```

**Problem**: Case-Sensitivity
- Retell liefert manchmal "Neutral", manchmal "neutral"
- Filament-Filter erwartet exakte Matches
- Potentieller Datenverlust bei Filterung

### Beispiel-Analysen

**Call 685 (2025-10-06 10:08) - Neutral ✅**
```json
{
  "user_sentiment": "Neutral",
  "call_summary": "Agent helped user book consultation appointment...",
  "call_successful": true
}
```

**Call 257 (2025-07-04 11:04) - Positive ✅**
```json
{
  "user_sentiment": "Positive",
  "call_summary": "Hans Schuster von der Schuster GmbH, bat um einen Rückruf..."
}
```

---

## 🎯 Aussagekraft der Daten

### ✅ Was funktioniert
- **Technische Integration**: Daten werden korrekt empfangen und gespeichert
- **UI-Darstellung**: Badge-Formatierung mit Emojis ist klar und verständlich
- **Historische Varianz**: Ältere Daten zeigen, dass Retell.ai verschiedene Sentiments erkennen kann

### ❌ Was problematisch ist

**1. Fehlende Varianz in aktuellen Daten**
- 100% "Neutral" seit Oktober 2025
- Unwahrscheinlich bei echten Kundengesprächen
- Mögliche Ursachen:
  - Retell.ai AI-Modell zu konservativ
  - Agent-Konfiguration könnte Sentiment-Analyse beeinflussen
  - Gesprächsqualität tatsächlich sehr uniform (unwahrscheinlich)

**2. Case-Sensitivity Inkonsistenz**
```
"Neutral" vs "neutral" → Führt zu:
- Filtern funktioniert nur teilweise
- Statistiken ungenau
- Badge-Display kann inkonsistent sein
```

**3. Keine Sentiment-Score Nutzung**
```sql
sentiment_score: ALWAYS NULL (0 von 685 Calls haben Wert)
```
- Datenbank-Feld existiert (`sentiment_score FLOAT`)
- Wird von Retell.ai NICHT geliefert
- Könnte für Confidence-Threshold nützlich sein

---

## 🔧 Empfehlungen

### 1. **Sofortmaßnahme: Case Normalization**
**File**: `/var/www/api-gateway/app/Services/RetellApiClient.php:283`

```php
// BEFORE
'sentiment' => $callData['call_analysis']['user_sentiment'] ?? null,

// AFTER
'sentiment' => isset($callData['call_analysis']['user_sentiment'])
    ? ucfirst(strtolower($callData['call_analysis']['user_sentiment']))
    : null,
```

**Ergebnis**: "neutral" → "Neutral", "POSITIVE" → "Positive"

### 2. **Retell.ai Konfiguration prüfen**
- Agent-Einstellungen reviewen
- Möglicherweise gibt es Sentiment-Analyse Settings
- Prüfen ob neuere Agent-Versionen bessere Sentiment-Erkennung haben

### 3. **Alternative Sentiment-Quellen erwägen**
Falls Retell.ai Sentiment unzureichend:
- Eigene AI-Analyse via OpenAI/Anthropic auf Transcript
- Keyword-basierte Heuristiken (Schimpfwörter → Negative, Danke → Positive)
- Kombination: Retell.ai + eigene Analyse → höhere Confidence

### 4. **Monitoring & Alerts**
```php
// Warnung bei zu einseitiger Sentiment-Verteilung
if (last_100_calls_sentiment_variance < 0.1) {
    Log::warning('Sentiment analysis may be broken - too little variance');
}
```

---

## 🎨 UI-Darstellung (Aktuell)

### Filament Badge-Formatierung
**File**: `CallResource.php:531-547`

```php
Tables\Columns\TextColumn::make('sentiment')
    ->label('Stimmung')
    ->badge()
    ->formatStateUsing(fn (?string $state): string => match ($state) {
        'positive' => '😊 Positiv',   // ❌ Wird nie matchen (sollte 'Positive' sein)
        'neutral' => '😐 Neutral',     // ❌ Wird nie matchen (sollte 'Neutral' sein)
        'negative' => '😟 Negativ',    // ❌ Wird nie matchen (sollte 'Negative' sein)
        default => '❓ Unbekannt',
    })
    ->color(fn (?string $state): string => match ($state) {
        'positive' => 'success',
        'neutral' => 'gray',
        'negative' => 'danger',
        default => 'warning',
    }),
```

**Problem**: Match-Strings stimmen nicht mit Retell.ai Werten überein!
- Retell liefert: `Positive`, `Neutral`, `Negative` (Großgeschrieben)
- Badge erwartet: `positive`, `neutral`, `negative` (Kleingeschrieben)
- **Ergebnis**: Alle Calls zeigen "❓ Unbekannt" Badge

**Fix erforderlich**:
```php
->formatStateUsing(fn (?string $state): string => match ($state) {
    'Positive' => '😊 Positiv',
    'Neutral' => '😐 Neutral',
    'Negative' => '😟 Negativ',
    default => '❓ Unbekannt',
})
->color(fn (?string $state): string => match ($state) {
    'Positive' => 'success',
    'Neutral' => 'gray',
    'Negative' => 'danger',
    default => 'warning',
}),
```

---

## 📊 Datenbank-Schema

### calls Tabelle - Sentiment Felder
```sql
sentiment            VARCHAR(50)  NULL  -- "Positive", "Neutral", "Negative"
sentiment_score      FLOAT        NULL  -- UNUSED (always NULL)
```

### Nutzung
- `sentiment`: ✅ Befüllt bei allen analysierten Calls
- `sentiment_score`: ❌ Nie befüllt (Retell liefert kein Score)

---

## 🧪 Testfall: Call 685

```
DB-Werte:
├─ sentiment: "Neutral"
├─ sentiment_score: NULL
├─ raw.call_analysis.user_sentiment: "Neutral" ✅
└─ raw.call_analysis.call_successful: true

Gesprächsinhalt:
├─ Termin erfolgreich gebucht
├─ Kunde: Hansi Schulze
├─ Datum: 2025-10-10 09:30
└─ Zusammenfassung: "Agent helped user book consultation..."

Erwartete Stimmung: Positive (Kunde hat bekommen was er wollte)
Tatsächliche Stimmung: Neutral
→ Retell.ai Sentiment-Erkennung möglicherweise zu konservativ
```

---

## ✅ Fazit

### Technische Qualität: ✅ GUT
- Daten werden korrekt empfangen und gespeichert
- Integration funktioniert zuverlässig
- UI-Darstellung vorhanden (aber fehlerhaft)

### Inhaltliche Qualität: ⚠️ FRAGLICH
- 100% "Neutral" → keine Aussagekraft
- Retell.ai Sentiment-Analyse zu konservativ
- UI-Bug: Badge-Matching funktioniert nicht

### Empfohlene Maßnahmen:
1. 🔥 **SOFORT**: UI-Bug fixen (Badge-Matching auf Großschreibung anpassen)
2. 🔥 **SOFORT**: Case Normalization in RetellApiClient (vermeidet "neutral" vs "Neutral")
3. ⏳ **Diese Woche**: Retell.ai Agent-Konfiguration prüfen
4. ⏳ **Optional**: Eigene Sentiment-Analyse als Fallback implementieren

---

## 🎯 Nächste Schritte

**User Feedback erforderlich**:
- Soll UI-Bug sofort gefixt werden?
- Ist Sentiment-Analyse wichtig oder nur "nice to have"?
- Sollen wir alternative Sentiment-Quellen evaluieren?
